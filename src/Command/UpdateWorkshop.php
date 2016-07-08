<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use PhpSchool\WorkshopManager\VersionChecker;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateWorkshop
 * @package PhpSchool\WorkshopManager\Command
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UpdateWorkshop
{
    /**
     * @var InstalledWorkshopRepository
     */
    private $installedWorkshopRepository;
    /**
     * @var VersionChecker
     */
    private $versionChecker;
    /**
     * @var Uninstaller
     */
    private $uninstaller;
    /**
     * @var Installer
     */
    private $installer;

    /**
     * @param InstalledWorkshopRepository $workshopRepository
     * @param VersionChecker $versionChecker
     * @param Uninstaller $uninstaller
     * @param Installer $installer
     */
    public function __construct(
        InstalledWorkshopRepository $workshopRepository,
        VersionChecker $versionChecker,
        Uninstaller $uninstaller,
        Installer $installer
    ) {
        $this->installedWorkshopRepository = $workshopRepository;
        $this->versionChecker = $versionChecker;
        $this->uninstaller = $uninstaller;
        $this->installer = $installer;
    }

    public function __invoke(OutputInterface $output, $workshopName)
    {
        $output->writeln('');

        try {
            $workshop = $this->installedWorkshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln([
                sprintf(
                    ' <fg=magenta> It doesn\'t look like "%s" is installed, did you spell it correctly? </>',
                    $workshopName
                ),
                ''
            ]);
            return;
        }

        $updated = $this->versionChecker->checkForUpdates($workshop, function ($version, $updated) {
            return $updated;
        });
        if (!$updated) {
            $output->writeln([
                '',
                sprintf(' <fg=magenta> There are no updates available for workshop "%s".</>', $workshopName),
                ''
            ]);
            return;
        }

        try {
            $this->uninstaller->uninstallWorkshop($workshop);
        } catch (IOException $e) {
            $output->writeln([
                '',
                sprintf(
                    ' <error> Failed to uninstall workshop "%s". Error: "%s" </error>',
                    $workshop->getName(),
                    $e->getMessage()
                ),
                ''
            ]);
            return;
        }

        $this->installedWorkshopRepository->removeWorkshop($workshop);
        $this->installedWorkshopRepository->save();

        try {
            $version = $this->installer->installWorkshop($workshop);
        } catch (WorkshopAlreadyInstalledException $e) {
            $output->writeln(
                sprintf(" <info>\"%s\" is already installed, you're ready to learn!</info>\n", $workshopName)
            );
        } catch (DownloadFailureException $e) {
            $output->writeln(
                sprintf(' <error> There was a problem downloading the workshop "%s"</error>\n', $workshopName)
            );
        } catch (FailedToMoveWorkshopException $e) {
            $output->writeln([
                sprintf(' <error> There was a problem moving downloaded files for "%s"   </error>', $workshopName),
                " Please check your file permissions for the following paths\n",
                sprintf(' <info>%s</info>', dirname($e->getSrcPath())),
                sprintf(' <info>%s</info>', dirname($e->getDestPath())),
            ]);
        } catch (ComposerFailureException $e) {
            $output->writeln(
                sprintf(' <error> There was a problem installing dependencies for "%s" </error>', $workshopName)
            );
        }

        if (isset($e) && $output->isVerbose()) {
            throw $e;
        } elseif (isset($e)) {
            return;
        }

        $this->installedWorkshopRepository->addWorkshop(InstalledWorkshop::fromWorkshop($workshop, $version));
        $this->installedWorkshopRepository->save();
        $output->writeln(
            sprintf(" <info>Successfully updated %s to version %s</info>\n", $workshop->getName(), $version)
        );
    }
}

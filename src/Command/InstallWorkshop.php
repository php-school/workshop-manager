<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\Exception;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallWorkshop
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class InstallWorkshop
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var Linker
     */
    private $linker;

    /**
     * @var InstalledWorkshopRepository
     */
    private $installedWorkshopRepository;

    /**
     * @var RemoteWorkshopRepository
     */
    private $remoteWorkshopRepository;

    /**
     * InstallWorkshop constructor.
     * @param Installer $installer
     * @param Linker $linker
     * @param InstalledWorkshopRepository $installedWorkshopRepository
     * @param RemoteWorkshopRepository $remoteWorkshopRepository
     */
    public function __construct(
        Installer $installer,
        Linker $linker,
        InstalledWorkshopRepository $installedWorkshopRepository,
        RemoteWorkshopRepository $remoteWorkshopRepository
    ) {

        $this->installer = $installer;
        $this->linker = $linker;
        $this->installedWorkshopRepository = $installedWorkshopRepository;
        $this->remoteWorkshopRepository = $remoteWorkshopRepository;
    }

    /**
     * @param OutputInterface $output
     * @param string $workshopName
     * @param bool $force
     *
     * @return void
     * @throws WorkshopAlreadyInstalledException
     * @throws DownloadFailureException
     * @throws ComposerFailureException
     */
    public function __invoke(OutputInterface $output, $workshopName, $force)
    {
        $output->writeln('');

        try {
            $workshop = $this->remoteWorkshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            return $output->writeln([
                  sprintf(
                      ' <fg=magenta> No workshops found matching "%s", did you spell it correctly? </>',
                      $workshopName
                  ),
                  ''
            ]);
        }

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

        try {
            $this->linker->symlink($workshop, $force);
        } catch (Exception $e) {
            if ($output->isVerbose()) {
                throw $e;
            }
        }

        $this->installedWorkshopRepository->add(InstalledWorkshop::fromWorkshop($workshop, $version));
        $this->installedWorkshopRepository->save();
        $output->writeln(sprintf(" <info>Successfully installed \"%s\"</info>\n", $workshop->getName()));
    }
}

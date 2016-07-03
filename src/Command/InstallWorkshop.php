<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallWorkshop
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class InstallWorkshop
{
    /**
     * @var WorkshopRepository
     */
    private $workshopRepository;

    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var Linker
     */
    private $linker;

    /**
     * @var ManagerState
     */
    private $managerState;

    /**
     * @param Installer $installer
     * @param Linker $linker
     * @param WorkshopRepository $workshopRepository
     * @param ManagerState $managerState
     */
    public function __construct(
        Installer $installer,
        Linker $linker,
        WorkshopRepository $workshopRepository,
        ManagerState $managerState
    ) {
        $this->installer          = $installer;
        $this->linker             = $linker;
        $this->workshopRepository = $workshopRepository;
        $this->managerState       = $managerState;
    }

    /**
     * @param OutputInterface $output
     * @param string $workshopName
     *
     * @return void
     * @throws WorkshopAlreadyInstalledException
     * @throws DownloadFailureException
     * @throws ComposerFailureException
     */
    public function __invoke(OutputInterface $output, $workshopName)
    {
        $output->writeln('');

        try {
            $workshop = $this->workshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(sprintf(' <error> No workshops found matching "%s" </error>', $workshopName));
            return;
        }

        try {
            $this->installer->installWorkshop($workshop);
            $this->linker->symlink($workshop, $input->getOption('force'));
        } catch (WorkshopAlreadyInstalledException $e) {
            $output->writeln(sprintf(' <info>"%s" is already installed, your ready to learn!</info>', $workshopName));
        } catch (DownloadFailureException $e) {
            $output->writeln(
                sprintf(' <error> There was a problem downloading the workshop "%s" </error>', $workshopName)
            );
        } catch (FailedToMoveWorkshopException $e) {
            $output->writeln([
                sprintf(' <error> There was a problem moving downloaded files for "%s" </error>', $workshopName),
                ' Please check your file permissions for the following paths',
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

        $this->managerState->addWorkshop($workshop);
        $output->writeln(sprintf(' <info>Successfully installed "%s"</info>', $workshop->getName()));
    }
}

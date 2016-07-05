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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallCommand
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class InstallCommand extends Command
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

        parent::__construct();
    }

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install a PHP School workshop')
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop would you like to install')
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Attempt to force the removal of blocking files');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws WorkshopAlreadyInstalledException
     * @throws DownloadFailureException
     * @throws ComposerFailureException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $workshopName = $input->getArgument('workshop');

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

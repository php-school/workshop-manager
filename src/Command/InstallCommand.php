<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\Linker;
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
     * @param Installer $installer
     * @param Linker $linker
     * @param WorkshopRepository $workshopRepository
     */
    public function __construct(
        Installer $installer,
        Linker $linker,
        WorkshopRepository $workshopRepository
    ) {
        $this->installer          = $installer;
        $this->linker             = $linker;
        $this->workshopRepository = $workshopRepository;

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
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $workshopName = $input->getArgument('workshop');

        try {
            $workshop = $this->workshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(sprintf(' <error>No workshops found matching "%s"</error>', $workshopName));
            return;
        }

        try {
            $this->installer->installWorkshop($workshop);
        } catch (WorkshopAlreadyInstalledException $e) {
            $output->writeln(sprintf(' <info>"%s" is already installed, your ready to learn!</info>', $workshopName));
            return;
        } catch (\Exception $e) {
            $output->writeln(sprintf(' <error>There was a problem installing "%s"</error>', $workshopName));
            return;
        }

        if ($this->linker->symlink($workshop, $input->hasOption('force'))) {
            $output->writeln(sprintf(' <info>Successfully installed "%s"</info>', $workshop->getName()));
        }
    }
}

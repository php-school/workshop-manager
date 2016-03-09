<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
     * @param Installer $installer
     * @param WorkshopRepository $workshopRepository
     */
    public function __construct(Installer $installer, WorkshopRepository $workshopRepository)
    {
        $this->installer          = $installer;
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
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop would you like to install');
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

        // TODO: Symlink ?

        $output->writeln(sprintf(' <info>Successfully installed "%s"</info>', $workshop->getName()));
    }
}

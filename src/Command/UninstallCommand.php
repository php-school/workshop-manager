<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use PhpSchool\WorkshopManager\WorkshopManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UninstallCommand
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class UninstallCommand extends Command
{
    /**
     * @var Uninstaller
     */
    private $uninstaller;
    
    /**
     * @var WorkshopRepository
     */
    private $workshopRepository;

    /**
     * @param Uninstaller $uninstaller
     * @param WorkshopRepository $workshopRepository
     */
    public function __construct(Uninstaller $uninstaller, WorkshopRepository $workshopRepository)
    {
        $this->uninstaller        = $uninstaller;
        $this->workshopRepository = $workshopRepository;

        parent::__construct();
    }

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('uninstall')
            ->setDescription('Uninstall a PHP School workshop')
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop would you like to uninstall');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workshopName = $input->getArgument('workshop');
        $output->writeln('');

        try {
            $workshop = $this->workshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(sprintf(' <error>No workshops found matching "%s"</error>', $workshopName));
            return;
        }

        try {
            $this->uninstaller->uninstallWorkshop($workshop);
        } catch (WorkshopNotInstalledException $e) {
            $output->writeln(sprintf(' <error>Workshop "%s" not currently installed</error>', $workshop->getName()));
            return;
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf(' <error>Failed to uninstall workshop "%s"</error>', $workshop->getName()));
            return;
        }

        $output->writeln(sprintf(' <info>Successfully uninstalled "%s"</info>', $workshop->getName()));
    }
}

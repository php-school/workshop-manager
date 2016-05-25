<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
     * @var Linker
     */
    private $linker;

    /**
     * @param Uninstaller $uninstaller
     * @param WorkshopRepository $workshopRepository
     * @param Linker $linker
     */
    public function __construct(Uninstaller $uninstaller, WorkshopRepository $workshopRepository, Linker $linker)
    {
        $this->uninstaller        = $uninstaller;
        $this->workshopRepository = $workshopRepository;
        $this->linker             = $linker;

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
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop would you like to uninstall')
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Attempt to force the removal of blocking files');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \RuntimeException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workshopName = $input->getArgument('workshop');
        $output->writeln('');

        try {
            $workshop = $this->workshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(sprintf(' <error> No workshops found matching "%s" </error>', $workshopName));
            return;
        }

        try {
            $this->linker->unlink($workshop, $input->getOption('force'));
            $this->uninstaller->uninstallWorkshop($workshop);
        } catch (WorkshopNotInstalledException $e) {
            $output->writeln(sprintf(' <error> Workshop "%s" not currently installed </error>', $workshop->getName()));
            return;
        } catch (\RuntimeException $e) {
            $output->writeln([
                '',
                sprintf(' <error> Failed to uninstall workshop "%s" </error>', $workshop->getName())
            ]);

            if ($$output->isVerbose()) {
                throw $e;
            }
            return;
        }

        $output->writeln(sprintf(' <info>Successfully uninstalled "%s"</info>', $workshop->getName()));
    }
}

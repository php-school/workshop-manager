<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
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
     * @param Uninstaller $uninstaller
     */
    public function __construct(Uninstaller $uninstaller)
    {
        $this->uninstaller = $uninstaller;
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
        $workshop = $input->getArgument('workshop');
        $output->writeln('');

        try {
            $this->uninstaller->uninstallWorkshop($workshop);
        } catch (WorkshopNotInstalledException $e) {
            $output->writeln(sprintf(' <error>Workshop "%s" not currently installed</error>', $workshop));
            return;
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf(' <error>Failed to uninstall workshop "%s"</error>', $workshop));
            return;
        }

        $output->writeln(sprintf(' <info>Successfully uninstalled "%s"</info>', $workshop));
    }
}

<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * ListCommand constructor
     *
     * @param Filesystem $filesystem
     * @throws LogicException When the command name is empty
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
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
        $contents = $this->filesystem->listContents(sprintf('workshops/%s', $workshop));

        if (!$contents) {
            $output->writeln(sprintf('Looks like "%s" isn\'t installed', $workshop));
            return;
        }

        $unlinkCommand = $this->getApplication()->find('unlink');
        $unlinkCommand->run($input, $output);

        try {
            $this->filesystem->deleteDir(sprintf('workshops/%s', $workshop));
        } catch (FileNotFoundException $e) {
            $output->writeln(sprintf('Failed to uninstall "%s"', $workshop));
            return;
        }

        $output->writeln(sprintf('Successfully uninstalled "%s"', $workshop));
    }
}

<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListCommand
 */
class ListCommand extends Command
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
            ->setName('list')
            ->setDescription('List installed PHP School workshops');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workshops = $this->filesystem->listContents('workshops');

        if (!$workshops) {
            $output->writeln('There are currently no workshops installed');
            return;
        }

        $output->writeln('Installed workshops');

        foreach ($workshops as $workshop) {
            // TODO: Filter out files/dirs not in JSON
            $output->writeln(sprintf('  - %s', $workshop['basename']));
        }
    }
}

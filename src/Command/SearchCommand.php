<?php

namespace PhpSchool\WorkshopManager\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SearchCommand
 */
class SearchCommand extends Command
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('search')
            ->setDescription('Search for a PHP School workshop')
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop are you searching for');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('workshop');

        $output->writeln('Searching for ' . $name);
    }
}

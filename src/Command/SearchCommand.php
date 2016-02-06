<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SearchCommand
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class SearchCommand extends Command
{
    /**
     * @var WorkshopRepository
     */
    private $workshopRepository;

    /**
     * SearchCommand constructor.
     * @param WorkshopRepository $repository
     */
    public function __construct(WorkshopRepository $repository)
    {
        $this->workshopRepository = $repository;
        parent::__construct();
    }

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
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('workshop');

        try {
            $workshop = $this->workshopRepository->find($name);
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('No workshop found called "%s"', $name));
            return;
        }

        $output->writeln('Workshop Found!');
        $output->writeln($workshop->getDisplayName());
        $output->writeln($workshop->getDescription());
    }
}

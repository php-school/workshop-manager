<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
        $output->writeln('');

        try {
            $workshops = $this->workshopRepository->find($name);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(sprintf(' No workshops found matching "%s"', $name));
            return;
        }

        $output->writeln(' <info>Search Results</info>');
        $output->writeln(' ==============');

        (new Table($output))
            ->setHeaders(['Name', 'Description', 'Package'])
            ->setRows(array_map(function (Workshop $workshop) {
                return [$workshop->getDisplayName(), wordwrap($workshop->getDescription(), 50), $workshop->getName()];
            }, $workshops))
            ->setStyle('borderless')
            ->render();
    }
}

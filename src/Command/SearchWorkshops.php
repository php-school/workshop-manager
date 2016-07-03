<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SearchWorkshops
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class SearchWorkshops
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
    }

    /**
     * @param string $workshopName
     * @param OutputInterface $output
     *
     * @return void
     */
    public function __invoke(OutputInterface $output, $workshopName)
    {
        $output->writeln('');

        try {
            $workshops = $this->workshopRepository->find($workshopName);
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

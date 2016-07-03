<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\WorkshopManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListWorkshops
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class ListWorkshops
{
    /**
     * @var WorkshopRepository
     */
    private $installedWorkshops;

    /**
     * @param WorkshopRepository $installedWorkshops
     */
    public function __construct(WorkshopRepository $installedWorkshops)
    {
        $this->installedWorkshops = $installedWorkshops;
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function __invoke(OutputInterface $output)
    {
        if (!$this->installedWorkshops->isempty()) {
            $output->writeln("\n There are currently no workshops installed");
            return;
        }

        $output->writeln("\n <info>Installed Workshops</info>");
        $output->writeln(" ===================");

        (new Table($output))
            ->setHeaders(['Name', 'Description', 'Package'])
            ->setRows(array_map(function (Workshop $workshop) {
                return [$workshop->getDisplayName(), wordwrap($workshop->getDescription(), 50), $workshop->getName()];
            }, $this->installedWorkshops->getAllWorkshops()))
            ->setStyle('borderless')
            ->render();
    }
}

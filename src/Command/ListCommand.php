<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\WorkshopManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListCommand
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class ListCommand extends Command
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

<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
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
     * @var WorkshopManager
     */
    private $workshopManager;

    /**
     * @param WorkshopManager $workshopManager
     */
    public function __construct(WorkshopManager $workshopManager)
    {
        $this->workshopManager = $workshopManager;
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
        $workshops = $this->workshopManager->getInstalledWorkshops();

        if (!$workshops) {
            $output->writeln("\n There are currently no workshops installed");
            return;
        }

        $output->writeln("\n <info>Installed workshops</info>");
        $output->writeln(" ===================");

        (new Table($output))
            ->setHeaders(['Name', 'Description', 'Package'])
            ->setRows(array_map(function (Workshop $workshop) {
                return [$workshop->getDisplayName(), wordwrap($workshop->getDescription(), 50), $workshop->getName()];
            }, $workshops))
            ->setStyle('borderless')
            ->render();
    }
}

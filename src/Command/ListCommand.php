<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var WorkshopRepository
     */
    private $repository;

    /**
     * ListCommand constructor
     *
     * @param Filesystem $filesystem
     * @param WorkshopRepository $repository
     */
    public function __construct(Filesystem $filesystem, WorkshopRepository $repository)
    {
        $this->filesystem = $filesystem;
        $this->repository = $repository;
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
        $workshops = array_filter(array_map(function ($listing) {
            try {
                return $this->repository->getByName($listing['basename']);
            } catch (WorkshopNotFoundException $e) {
                return false;
            }
        }, $this->filesystem->listContents('workshops')));

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

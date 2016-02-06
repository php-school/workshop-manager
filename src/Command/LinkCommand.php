<?php

namespace PhpSchool\WorkshopManager\Command;

use League\Flysystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LinkCommand
 */
class LinkCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $localFilesystem;

    /**
     * ListCommand constructor
     *
     * @param Filesystem $localFilesystem
     */
    public function __construct(Filesystem $localFilesystem)
    {
        $this->localFilesystem = $localFilesystem;
        parent::__construct();
    }

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('link')
            ->setDescription('Symlink an installed workshop')
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop would you like to symlink');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \RuntimeException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workshop     = $input->getArgument('workshop');
        $homePath     = strtolower(substr(PHP_OS, 0, 3)) === 'win' ? getenv('USERPROFILE') : getenv('HOME');
        $homeBinPath  = sprintf('%s/bin', $homePath);
        $workshopPath = $this->localFilesystem->getAdapter()->applyPathPrefix(sprintf('workshops/%s', $workshop));

        if (!@mkdir($homeBinPath) && !is_dir($homeBinPath)) {
            throw new \RuntimeException(sprintf('Failed to create path "%s"', $homeBinPath));
        }

        symlink(
            sprintf('%s/bin/%s', $workshopPath, $workshop),
            sprintf('%s/bin/%s', realpath($homePath), $workshop)
        );

        $output->writeln(sprintf('Workshop "%s" executable linked succesfully', $workshop));
    }
}

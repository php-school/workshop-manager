<?php

namespace PhpSchool\WorkshopManager\Command;

use Github\Client;
use Github\Exception\InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileExistsException;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallCommand
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class InstallCommand extends Command
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
            ->setName('install')
            ->setDescription('Install a PHP School workshop')
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop would you like to install');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workshop     = $input->getArgument('workshop');
        $workshopPath = sprintf('workshops/%s', $workshop);
        $downloadPath = sprintf('.temp/%s', $workshop);

        if ($this->localFilesystem->listContents($workshopPath)) {
            $output->writeln(sprintf('Looks like "%s" is already installed!', $workshop));
            return;
        }

        // TODO: Use JSON file to get workshop details
        $user   = 'php-school';
        $repo   = 'learn-you-php';
        $client = new Client();

        try {
            $tags      = $client->api('git')->tags()->all($user, $repo);
            $latest    = end($tags);
            $latestSha = $latest['object']['sha'];
            $data      = $client->api('repo')->contents()->archive($user, $repo, 'zipball', $latestSha);

            $this->localFilesystem->write(sprintf('%s.zip', $downloadPath), $data);
        } catch (InvalidArgumentException $e) {
            $output->writeln(sprintf('Failed to download "%s"', $workshop));
            return;
        } catch (FileExistsException $e) {
            $output->writeln(sprintf('Failed to install "%s"', $workshop));
            return;
        }

        /** @var Local $adaptor */
        $adaptor     = $this->localFilesystem->getAdapter();
        $fullZipPath = $adaptor->applyPathPrefix(sprintf('%s.zip', $downloadPath));
        $zipArchive  = new \ZipArchive();

        if (!$zipArchive->open($fullZipPath)) {
            $output->writeln(sprintf('Failed to open "%s" zip', $workshop));
            return;
        }

        $unzipPath = $adaptor->applyPathPrefix('.temp');
        $dirStat   = $zipArchive->statIndex(0);
        $dirName   = basename($dirStat['name']);

        if (!$zipArchive->extractTo($unzipPath)) {
            $output->writeln(sprintf('Failed to unzip "%s" zip', $workshop));
            return;
        }

        $this->localFilesystem->delete(sprintf('%s.zip', $downloadPath));

        if (!$this->localFilesystem->rename(sprintf('.temp/%s', $dirName), $workshopPath)) {
            $output->writeln(sprintf('Failed to move "%s"', $workshop));
            return;
        }

        $fullWorkshopPath = $adaptor->applyPathPrefix($workshopPath);
        $currentPath      = getcwd();

        chdir($fullWorkshopPath);
        exec('composer install --no-dev');
        chdir($currentPath);

        $this->localFilesystem->createDir('bin');

        $linkCommand = $this->getApplication()->find('link');
        $linkCommand->run($input, $output);

        $output->writeln(sprintf('Successfully installed "%s"', $workshop));
    }
}

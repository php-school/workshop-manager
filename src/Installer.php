<?php

namespace PhpSchool\WorkshopManager;

use Composer\Installer as ComposerInstaller;
use Composer\Factory;
use Composer\IO\IOInterface;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Installer
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
final class Installer
{
    /**
     * @var ManagerState
     */
    private $state;

    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @param ManagerState $state
     * @param Downloader $downloader
     * @param Filesystem $filesystem
     * @param Factory $factory
     */
    public function __construct(
        ManagerState $state,
        Downloader $downloader,
        Filesystem $filesystem,
        Factory $factory
    ) {
        $this->state      = $state;
        $this->downloader = $downloader;
        $this->filesystem = $filesystem;
        $this->factory    = $factory;
    }

    /**
     * @param Workshop $workshop
     * @param IOInterface $io
     * @throws \League\Flysystem\FileExistsException
     */
    public function installWorkshop(Workshop $workshop, IOInterface $io)
    {
        if ($this->state->isWorkshopInstalled($workshop->getName())) {
            throw new WorkshopAlreadyInstalledException;
        }

        $pathToZip  = $this->downloader->download($workshop);
        $zipArchive = new \ZipArchive();

        $zipArchive->open($pathToZip);
        $zipArchive->extractTo(dirname($pathToZip));

        /**
         * TODO: Handle exceptions...
         *      FileExistsException     [ ]
         *      FileNotFoundException   [ ]
         */
        $this->filesystem->rename(
            sprintf('.temp/%s', $zipArchive->getNameIndex(0)),
            sprintf('workshops/%s', $workshop->getName())
        );

        $currentPath  = getcwd();
        $workshopPath = $this->filesystem->getAdapter()->applyPathPrefix(sprintf('workshops/%s', $workshop->getName()));

        /**
         * TODO: Handle exceptions...
         *      InvalidArgumentException : No composer.json file found                      [ ]
         *      UnexpectedValueException : COMPOSER_AUTH environment variable is malformed  [ ]
         */
        $composer = $this->factory->createComposer(
            $io,
            sprintf('%s/composer.json', $workshopPath),
            false,
            $workshopPath
        );

        $installer = ComposerInstaller::create($io, $composer);

        chdir($workshopPath);
        try {
            $installer->run();
        } catch (\Exception $e) {
            // TODO: Exception handling
        }
        chdir($currentPath);
    }
}

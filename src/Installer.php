<?php

namespace PhpSchool\WorkshopManager;

use Composer\Installer as ComposerInstaller;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;

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
     * @var NullIO
     */
    private $io;

    /**
     * @param ManagerState $state
     * @param Downloader $downloader
     * @param Filesystem $filesystem
     * @param Factory $factory
     * @param IOInterface $io
     */
    public function __construct(
        ManagerState $state,
        Downloader $downloader,
        Filesystem $filesystem,
        Factory $factory,
        IOInterface $io
    ) {
        $this->state      = $state;
        $this->downloader = $downloader;
        $this->filesystem = $filesystem;
        $this->factory    = $factory;
        $this->io         = $io;
    }

    /**
     * @param Workshop $workshop
     * @throws WorkshopAlreadyInstalledException
     */
    public function installWorkshop(Workshop $workshop)
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
            $this->io,
            sprintf('%s/composer.json', $workshopPath),
            false,
            $workshopPath
        );

        $installer  = ComposerInstaller::create($this->io, $composer);

        chdir($workshopPath);
        try {
            $installer->run();
        } catch (\Exception $e) {
            // TODO: Exception handling
        }
        chdir($currentPath);
    }
}

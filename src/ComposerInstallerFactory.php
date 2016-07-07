<?php

namespace PhpSchool\WorkshopManager;

use Composer\Installer as ComposerInstaller;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;

/**
 * Class ComposerInstallerFactory
 * @package PhpSchool\WorkshopManager
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ComposerInstallerFactory
{
    /**
     * @var ComposerFactory
     */
    private $composerFactory;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @param ComposerFactory $composerFactory
     * @param IOInterface $io
     */
    public function __construct(ComposerFactory $composerFactory, IOInterface $io)
    {
        $this->io = $io;
        $this->composerFactory = $composerFactory;
    }

    /**
     * @param string $path
     * @return ComposerInstaller
     */
    public function create($path)
    {
        $composer = $this->composerFactory->createComposer(
            $this->io,
            sprintf('%s/composer.json', $path),
            false,
            $path
        );

        return ComposerInstaller::create($this->io, $composer);
    }
}

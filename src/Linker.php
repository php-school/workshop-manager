<?php

namespace PhpSchool\WorkshopManager;

use Composer\IO\IOInterface;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;

/**
 * Class Linker
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
final class Linker
{
    /**
     * @var ManagerState
     */
    private $state;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var bool
     */
    private $useSystem;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @param ManagerState $state
     * @param Filesystem $filesystem
     * @param IOInterface $io
     */
    public function __construct(ManagerState $state, Filesystem $filesystem, IOInterface $io)
    {
        $this->state      = $state;
        $this->filesystem = $filesystem;
        $this->io         = $io;
        $this->useSystem  = strpos($filesystem->getAdapter()->applyPathPrefix('bin'), getenv('PATH')) === false;
    }

    /**
     * @param Workshop $workshop
     * @return string
     */
    private function getWorkshopSrcPath(Workshop $workshop)
    {
        return $this->filesystem->getAdapter()->applyPathPrefix(sprintf(
            'workshops/%s/bin/%s',
            $workshop->getName(),
            $workshop->getName()
        ));
    }

    /**
     * @param Workshop $workshop
     * @return string
     */
    private function getLocalTargetPath(Workshop $workshop)
    {
        return $this->filesystem->getAdapter()->applyPathPrefix(sprintf('bin/%s', $workshop->getName()));
    }

    /**
     * @param Workshop $workshop
     * @param bool $force
     *
     * @return bool
     */
    public function symlink(Workshop $workshop, $force = false)
    {
        if (!$this->state->isWorkshopInstalled($workshop)) {
            $this->io->write(sprintf(' <error>Workshop "%s" not installed</error>', $workshop->getName()));
            return false;
        }

        $localTarget = $this->getLocalTargetPath($workshop);

        $this->removeWorkshopBin($localTarget, $force);
        if (!symlink($this->getWorkshopSrcPath($workshop), $localTarget)) {
            $this->io->write(' <error> Unexpected error occurred</error>');
            $this->io->write(sprintf(' <error> Failed symlinking workshop bin to path "%s"</error>', $localTarget));
            return false;
        }
        chmod($localTarget, 0755);

        if (!$this->useSystem) {
            return true;
        }

        return $this->symlinkToSystem($workshop, $force);
    }

    /**
     * @param Workshop $workshop
     * @param $force
     * @return bool
     */
    private function symlinkToSystem(Workshop $workshop, $force)
    {
        $localTarget  = $this->getLocalTargetPath($workshop);
        $systemTarget = $this->getSystemInstallPath($workshop->getName());

        if (!is_writable(dirname($systemTarget))) {
            $this->io->write(sprintf(
                ' <error>The directory: "%s" is not writeable. The workshop %s cannot be installed.</error>',
                dirname($systemTarget),
                $workshop->getName()
            ));
            $this->io->write('');
            $this->io->write(sprintf('You have two options now:'));
            $this->io->write(sprintf(
                ' 1. Add the PHP School local bin dir: <info>%s</info> to your PATH variable',
                dirname($localTarget)
            ));

            if ($this->isUnix()) {
                // TODO
                $this->io->write(
                    '    e.g. Run <info>$ echo "export PATH=$PATH:%s" >> ~/.profile && source ~/.bashrc</info>'
                );
                $this->io->write(
                    '    Replacing ~/.bashrc with your chosen bash config file e.g. ~/.zshrc or ~/.profile etc'
                );
            } else {
                // TODO
                $this->io->write('    Follow this guide for Windows <info>http://windows-guide-path</info>');
            }

            $this->io->write(sprintf(
                ' 2. Run <info>%s</info> directly with <info>php %s</info>',
                $workshop->getName(),
                $localTarget
            ));

            return false;
        }

        $this->removeWorkshopBin($systemTarget, $force);
        if (!symlink($this->getWorkshopSrcPath($workshop), $systemTarget)) {
            $this->io->write(' <error> Unexpected error occurred</error>');
            $this->io->write(sprintf(' <error> Failed symlinking workshop bin to path "%s"</error>', $systemTarget));
            return false;
        }
        chmod($systemTarget, 0755);

        return true;
    }

    /**
     * @param Workshop $workshop
     * @param bool $force
     */
    public function unlink(Workshop $workshop, $force = false)
    {
        if (!$this->state->isWorkshopInstalled($workshop)) {
            $this->io->write(sprintf(' <error>Workshop "%s" not installed</error>', $workshop->getName()));
            return;
        }

        $localTarget = $this->filesystem->getAdapter()->applyPathPrefix(sprintf('bin/%s', $workshop->getName()));

        $this->removeWorkshopBin($localTarget, $force);

        if ($this->useSystem) {
            $systemTarget = $this->getSystemInstallPath($workshop->getName());
            $this->removeWorkshopBin($systemTarget, $force);
        }
    }

    /**
     * @param string $path
     * @param bool $force
     *
     * @return void
     */
    private function removeWorkshopBin($path, $force)
    {
        if (!file_exists($path)) {
            return;
        }

        if (!$force && !is_link($path)) {
            $this->io->write(sprintf(' <error>File already exists at path "%s"</error>', $path));
            $this->io->write(' <error>Try again using --force or manually remove the file</error>');
            return;
        }

        if (!unlink($path)) {
            $this->io->write(sprintf(' <error>Failed to remove file at path "%s"</error>', $path));
            $this->io->write(' <info>You may need to remove a blocking file manually with elevated privilages</info>');
        }
    }

    /**
     * @param string $binary
     * @return string
     */
    private function getSystemInstallPath($binary)
    {
        // TODO: Platform speciifc.
        if ($this->isUnix()) {
            return sprintf('/usr/local/bin/%s', $binary);
        }

        return sprintf('/usr/local/bin/%s', $binary);
    }

    /**
     * @return bool
     */
    private function isUnix()
    {
        // TODO: Detect unix environment.
        return true;
    }
}

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

        return $this->useSystem
            ? $this->link($workshop, $localTarget) && $this->symlinkToSystem($workshop, $force)
            : $this->link($workshop, $localTarget);
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
            $this->io->write([
                sprintf(
                    ' <error>The system directory: "%s" is not writeable. Workshop "%s" cannot be installed.</error>',
                    dirname($systemTarget),
                    $workshop->getName()
                ),
                '',
                sprintf(' You have two options now:'),
                sprintf(
                    '  1. Add the PHP School local bin dir: <info>%s</info> to your PATH variable',
                    dirname($localTarget)
                ),
                '      e.g. Run <info>$ echo "export PATH=$PATH:%s" >> ~/.bashrc && source ~/.bashrc</info>',
                '      Replacing ~/.bashrc with your chosen bash config file e.g. ~/.zshrc or ~/.profile etc',
                sprintf(
                    '  2. Run <info>%s</info> directly with <info>php %s</info>',
                    $workshop->getName(),
                    $localTarget
                )
            ]);

            return false;
        }

        $this->removeWorkshopBin($systemTarget, $force);

        return $this->link($workshop, $systemTarget);
    }

    /**
     * @param Workshop $workshop
     * @param $target
     *
     * @return bool
     */
    private function link(Workshop $workshop, $target)
    {
        if (!symlink($this->getWorkshopSrcPath($workshop), $target)) {
            $this->io->write(' <error> Unexpected error occurred</error>');
            $this->io->write(sprintf(' <error> Failed symlinking workshop bin to path "%s"</error>', $target));
            return false;
        }
        chmod($target, 0755);

        return true;
    }

    /**
     * @param Workshop $workshop
     * @param bool $force
     *
     * @return bool
     */
    public function unlink(Workshop $workshop, $force = false)
    {
        if (!$this->state->isWorkshopInstalled($workshop)) {
            $this->io->write(sprintf(' <error>Workshop "%s" not installed</error>', $workshop->getName()));
            return true;
        }

        $localTarget = $this->filesystem->getAdapter()->applyPathPrefix(sprintf('bin/%s', $workshop->getName()));
        $result      = $this->removeWorkshopBin($localTarget, $force);

        // TODO: This seems wrong. Maybe there is a better way?
        if ($this->useSystem) {
            $systemTarget = $this->getSystemInstallPath($workshop->getName());
            $result       = $result && $this->removeWorkshopBin($systemTarget, $force);
        }

        return $result;
    }

    /**
     * @param string $path
     * @param bool $force
     *
     * @return bool
     */
    private function removeWorkshopBin($path, $force)
    {
        if (!file_exists($path)) {
            return true;
        }

        if (!$force && !is_link($path)) {
            $this->io->write([
                sprintf(' <error>File already exists at path "%s"</error>', $path),
                ' <error>Try again using --force or manually remove the file</error>'
            ]);

            return false;
        }

        if (!unlink($path)) {
            $this->io->write([
                sprintf(' <error>Failed to remove file at path "%s"</error>', $path),
                ' <info>You may need to remove a blocking file manually with elevated privilages</info>'
            ]);

            return false;
        }

        return true;
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
     * @param string $binary
     * @return string
     */
    private function getSystemInstallPath($binary)
    {
        return sprintf('/usr/local/bin/%s', $binary);
    }
}

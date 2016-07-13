<?php

namespace PhpSchool\WorkshopManager;

use Composer\IO\IOInterface;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class Linker
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var string
     */
    private $workshopHomeDirectory;

    /**
     * @param Filesystem $filesystem
     * @param $workshopHomeDirectory
     * @param IOInterface $io
     */
    public function __construct(
        Filesystem $filesystem,
        $workshopHomeDirectory,
        IOInterface $io
    ) {
        $this->filesystem            = $filesystem;
        $this->io                    = $io;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
    }

    /**
     * @param InstalledWorkshop $workshop
     * @param bool $force
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function link(InstalledWorkshop $workshop, $force = false)
    {
        $localTarget = $this->getLocalTargetPath($workshop);

        $this->removeWorkshopBin($localTarget, $force);

        $this->useSytemPaths()
            ? $this->symlink($workshop, $localTarget) && $this->symlinkToSystem($workshop, $force)
            : $this->symlink($workshop, $localTarget);
    }

    /**
     * @param InstalledWorkshop $workshop
     * @param string $localTarget
     * @param bool $force
     */
    private function symlinkToSystem(InstalledWorkshop $workshop, $localTarget, $force)
    {
        $systemTarget = $this->getSystemInstallPath($workshop->getName());

        if (!$this->filesystem->isWritable(dirname($systemTarget))) {
            return $this->io->write([
                sprintf(
                    ' <error> The system directory: "%s" is not writeable. </error>',
                    dirname($systemTarget)
                ),
                sprintf(
                    ' <info>Workshop "%s" is installed but not linked to an executable path.</info>',
                    $workshop->getName()
                ),
                '',
                sprintf(' You have two options now:'),
                sprintf(
                    '  1. Add the PHP School local bin dir: <info>%s</info> to your PATH variable',
                    dirname($localTarget)
                ),
                sprintf(
                    '      e.g. Run <info>$ echo \'export PATH="$PATH:%s"\' >> ~/.bashrc && source ~/.bashrc</info>',
                    dirname($localTarget)
                ),
                '      Replacing ~/.bashrc with your chosen bash config file e.g. ~/.zshrc or ~/.profile etc',
                sprintf(
                    '  2. Run <info>%s</info> directly with <info>$ php %s</info>',
                    $workshop->getName(),
                    $localTarget
                )
            ]);
        }

        $this->removeWorkshopBin($systemTarget, $force);
        $this->symlink($workshop, $systemTarget);
    }

    /**
     * @param InstalledWorkshop $workshop
     * @param string $target
     *
     * @throws \RuntimeException
     */
    private function symlink(InstalledWorkshop $workshop, $target)
    {
        try {
            $this->filesystem->symlink($this->getWorkshopSrcPath($workshop), $target);
        } catch (IOException $e) {
            $this->io->write([
                    ' <error> Unexpected error occurred </error>',
                    sprintf(' <error> Failed symlinking workshop bin to path "%s" </error>', $target)
            ]);
            return;
        }

        try {
            $this->filesystem->chmod($target, 0755);
        } catch (IOException $e) {
            //if we couldn't chmod - remove it
            $this->filesystem->remove($target);
        }
    }

    /**
     * @param InstalledWorkshop $workshop
     * @param bool $force
     *
     * @return bool
     * @throws WorkshopNotInstalledException
     */
    public function unlink(InstalledWorkshop $workshop, $force = false)
    {
        $systemTarget = $this->getSystemInstallPath($workshop->getName());
        $localTarget  = sprintf('%s/bin/%s', $this->workshopHomeDirectory, $workshop->getName());

        return $this->removeWorkshopBin($systemTarget, $force) && $this->removeWorkshopBin($localTarget, $force);
    }

    /**
     * @param string $path
     * @param bool $force
     *
     * @return bool
     */
    private function removeWorkshopBin($path, $force)
    {
        if (!$this->filesystem->exists($path)) {
            return true;
        }

        if (!$force && !$this->filesystem->isLink($path)) {
            $this->io->write([
                sprintf(' <error> File already exists at path "%s" </error>', $path),
                ' <info>Try again using --force or manually remove the file</info>'
            ]);

            return false;
        }

        try {
            $this->filesystem->remove($path);
        } catch (IOException $e) {
            $this->io->write([
                    sprintf(' <error> Failed to remove file at path "%s" </error>', $path),
                    ' <info>You may need to remove a blocking file manually with elevated privileges</info>'
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param InstalledWorkshop $workshop
     * @return string
     */
    private function getWorkshopSrcPath(InstalledWorkshop $workshop)
    {
        return sprintf(
            '%s/workshops/%s/bin/%s',
            $this->workshopHomeDirectory,
            $workshop->getName(),
            $workshop->getName()
        );
    }

    /**
     * @param InstalledWorkshop $workshop
     * @return string
     */
    private function getLocalTargetPath(InstalledWorkshop $workshop)
    {
        // Ensure bin dir exists
        $path = sprintf('%s/bin/%s', $this->workshopHomeDirectory, $workshop->getName());
        $this->filesystem->mkdir(dirname($path));

        return $path;
    }

    /**
     * @param string $binary
     * @return string
     */
    private function getSystemInstallPath($binary)
    {
        return sprintf('/usr/local/bin/%s', $binary);
    }

    /**
     * Use system paths if PHP School dir is not in PATH variable
     *
     * @return bool
     */
    private function useSytemPaths()
    {
        return strpos(getenv('PATH'), sprintf('%s/bin', $this->workshopHomeDirectory)) === false;
    }
}

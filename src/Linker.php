<?php

namespace PhpSchool\WorkshopManager;

use Composer\IO\IOInterface;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $workshopHomeDirectory;

    /**
     * @param Filesystem $filesystem
     * @param $workshopHomeDirectory
     * @param OutputInterface $output
     */
    public function __construct(
        Filesystem $filesystem,
        $workshopHomeDirectory,
        OutputInterface $output
    ) {
        $this->filesystem            = $filesystem;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->output                = $output;
    }

    /**
     * @param InstalledWorkshop $workshop
     *
     * @return bool
     * @throws RuntimeException
     */
    public function link(InstalledWorkshop $workshop)
    {
        $localTarget = $this->getLocalTargetPath($workshop);

        try {
            $this->removeWorkshopBin($localTarget);
        } catch (RuntimeException $e) {
            return;
        }

        try {
            $this->filesystem->symlink($this->getWorkshopSrcPath($workshop), $localTarget);
        } catch (IOException $e) {
            return $this->output->write([
                ' <error> Unable to create symlink for workshop </error>',
                sprintf(' <error> Failed symlinking workshop bin to path "%s" </error>', $localTarget)
            ]);
        }

        try {
            $this->filesystem->chmod(realpath($this->getWorkshopSrcPath($workshop)), 0777);
        } catch (IOException $e) {
            return $this->output->write([
                ' <error> Unable to make workshop executable </error>',
                ' You may have to run the following with elevated privileges:',
                sprintf(' <info>$ chmod +x %s</info>', $localTarget)
            ]);
        }

        if (!$this->isBinDirInPath()) {
            $this->output->writeln([
                ' <error>The PHP School bin directory is not in your PATH variable.</error>',
                '',
                sprintf(
                    ' Add "%s/bin" to your PATH variable before running the workshop',
                    $this->workshopHomeDirectory
                ),
                sprintf(
                    ' e.g. Run <info>$ echo \'export PATH="$PATH:%s/bin"\' >> ~/.bashrc && source ~/.bashrc</info>',
                    $this->workshopHomeDirectory
                ),
                ' Replacing ~/.bashrc with your chosen bash config file e.g. ~/.zshrc or ~/.profile etc',
                sprintf(
                    ' You can validate your PATH variable is configured correctly by running <info>%s validate</info>',
                    $_SERVER['argv'][0]
                )
            ]);
        }
    }

    /**
     * @param InstalledWorkshop $workshop
     *
     * @return bool
     * @throws WorkshopNotInstalledException
     */
    public function unlink(InstalledWorkshop $workshop)
    {
        $localTarget = sprintf('%s/bin/%s', $this->workshopHomeDirectory, $workshop->getName());

        if (!$this->filesystem->exists($localTarget)) {
            return;
        }

        if (!$this->filesystem->isLink($localTarget)) {
            return $this->output->write([
                sprintf(' <error> Unknown file exists at path "%s" </error>', $localTarget),
                ' <info>Not removing</info>'
            ]);
        }

        try {
            $this->filesystem->remove($localTarget);
        } catch (IOException $e) {
            $this->output->write([
                sprintf(' <error> Failed to remove file at path "%s" </error>', $localTarget),
                ' <info>You may need to remove a blocking file manually with elevated privileges</info>'
            ]);
        }
    }

    /**
     * @param string $path
     *
     */
    private function removeWorkshopBin($path)
    {
        if (!$this->filesystem->exists($path)) {
            return;
        }

        if (!$this->filesystem->isLink($path)) {
            $this->output->write([
                sprintf(' <error> File already exists at path "%s" </error>', $path),
                ' <info>Try removing the file, then remove the workshop and install it again</info>'
            ]);

            throw new \RuntimeException;
        }

        try {
            $this->filesystem->remove($path);
        } catch (IOException $e) {
            $msg  = ' <info>You may need to remove a blocking file manually with elevated privileges. Then you can ';
            $msg .= 'remove and try installing the workshop again</info>';

            $this->output->write([
                sprintf(' <error> Failed to remove file at path "%s" </error>', $path),
                $msg
            ]);

            throw new \RuntimeException;
        }
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
     * Check that the PHP School bin dir is in PATH variable
     *
     * @return bool
     */
    private function isBinDirInPath()
    {
        return strpos(getenv('PATH'), sprintf('%s/bin', $this->workshopHomeDirectory)) !== false;
    }
}

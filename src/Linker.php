<?php

namespace PhpSchool\WorkshopManager;

use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

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

    public function __construct(
        Filesystem $filesystem,
        string $workshopHomeDirectory,
        OutputInterface $output
    ) {
        $this->filesystem = $filesystem;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->output = $output;
    }

    public function link(InstalledWorkshop $workshop): void
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
            $this->output->write([
                ' <error> Unable to create symlink for workshop </error>',
                sprintf(' <error> Failed symlinking workshop bin to path "%s" </error>', $localTarget)
            ]);
            return;
        }

        try {
            $this->filesystem->chmod(realpath($this->getWorkshopSrcPath($workshop)), 0777);
        } catch (IOException $e) {
            $this->output->write([
                ' <error> Unable to make workshop executable </error>',
                ' You may have to run the following with elevated privileges:',
                sprintf(' <info>$ chmod +x %s</info>', $localTarget)
            ]);
            return;
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

    public function unlink(InstalledWorkshop $workshop): void
    {
        $localTarget = sprintf('%s/bin/%s', $this->workshopHomeDirectory, $workshop->getCode());

        if (!$this->filesystem->exists($localTarget)) {
            return;
        }

        if (!$this->filesystem->isLink($localTarget)) {
            $this->output->write([
                sprintf(' <error> Unknown file exists at path "%s" </error>', $localTarget),
                ' <info>Not removing</info>'
            ]);
            return;
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

    private function removeWorkshopBin(string $path): void
    {
        if (!$this->filesystem->exists($path)) {
            return;
        }

        if (!$this->filesystem->isLink($path)) {
            $this->output->write([
                sprintf(' <error> File already exists at path "%s" </error>', $path),
                ' <info>Try removing the file, then remove the workshop and install it again</info>'
            ]);

            throw new \RuntimeException();
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

            throw new \RuntimeException();
        }
    }

    private function getWorkshopSrcPath(InstalledWorkshop $workshop): string
    {
        return sprintf(
            '%s/workshops/%s/bin/%s',
            $this->workshopHomeDirectory,
            $workshop->getCode(),
            $workshop->getCode()
        );
    }

    private function getLocalTargetPath(InstalledWorkshop $workshop): string
    {
        // Ensure bin dir exists
        $path = sprintf('%s/bin/%s', $this->workshopHomeDirectory, $workshop->getCode());
        $this->filesystem->mkdir(dirname($path));

        return $path;
    }

    /**
     * Check that the PHP School bin dir is in PATH variable
     *
     * @return bool
     */
    private function isBinDirInPath(): bool
    {
        return strpos(getenv('PATH'), sprintf('%s/bin', $this->workshopHomeDirectory)) !== false;
    }
}

<?php

namespace PhpSchool\WorkshopManagerTest;

use Composer\IO\IOInterface;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Filesystem;
use PhpSchool\WorkshopManager\Linker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class LinkerTest extends TestCase
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
    private $tmpDir;

    /**
     * @var Linker
     */
    private $linker;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->io = $this->createMock(OutputInterface::class);
        $this->tmpDir = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        $this->linker = new Linker($this->filesystem, $this->tmpDir, $this->io);
    }

    public function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testErrorIsPrintedIsFileExistsAtTarget(): void
    {
        $path = sprintf('%s/bin/learn-you-php', $this->tmpDir);
        mkdir(dirname($path), 0775, true);
        touch($path);

        $this->io
            ->expects($this->once())
            ->method('write')
            ->with(
                [
                    sprintf(' <error> File already exists at path "%s" </error>', $path),
                    ' <info>Try removing the file, then remove the workshop and install it again</info>'
                ]
            );

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->link($workshop);
    }

    public function testErrorIsPrintedIfFileCannotBeRemoved(): void
    {
        $fs = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['remove'])
            ->getMock();

        $this->linker = new Linker($fs, $this->tmpDir, $this->io);

        $path = sprintf('%s/bin/learn-you-php', $this->tmpDir);
        $fs->expects($this->once())
            ->method('remove')
            ->with($path)
            ->willThrowException(new IOException('Some error'));

        mkdir(dirname($path), 0775, true);
        touch(sprintf('%s/test', $this->tmpDir));
        symlink(sprintf('%s/test', $this->tmpDir), $path);

        $msg  = ' <info>You may need to remove a blocking file manually with elevated privileges. Then you can ';
        $msg .= 'remove and try installing the workshop again</info>';

        $this->io
            ->expects($this->once())
            ->method('write')
            ->with(
                [
                    sprintf(' <error> Failed to remove file at path "%s" </error>', $path),
                    $msg
                ]
            );

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->link($workshop);
    }

    public function testErrorIsPrintedIfCannotSymlink(): void
    {
        $fs = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['symlink'])
            ->getMock();

        $this->linker = new Linker($fs, $this->tmpDir, $this->io);

        $path = sprintf('%s/bin/learn-you-php', $this->tmpDir);
        $fs->expects($this->once())
            ->method('symlink')
            ->with(sprintf('%s/workshops/learn-you-php/bin/learn-you-php', $this->tmpDir), $path)
            ->willThrowException(new IOException('Some error'));

        $this->io
            ->expects($this->once())
            ->method('write')
            ->with(
                [
                    ' <error> Unable to create symlink for workshop </error>',
                    sprintf(
                        ' <error> Failed symlinking workshop bin to path "%s/bin/learn-you-php" </error>',
                        $this->tmpDir
                    )
                ]
            );

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->link($workshop);
    }

    public function testErrorIsPrintedIfCannotChmod(): void
    {
        //chmod will fail if the symlink target does not exist
        $this->io
            ->expects($this->once())
            ->method('write')
            ->with(
                [
                    ' <error> Unable to make workshop executable </error>',
                    ' You may have to run the following with elevated privileges:',
                    sprintf(' <info>$ chmod +x %s/bin/learn-you-php</info>', $this->tmpDir)
                ]
            );

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->link($workshop);
    }

    public function testSuccess(): void
    {
        putenv(sprintf('PATH=%s/bin', $this->tmpDir));

        $target = sprintf('%s/workshops/learn-you-php/bin/learn-you-php', $this->tmpDir);
        mkdir(dirname($target), 0775, true);
        touch($target);

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->link($workshop);

        $this->assertFileExists(sprintf('%s/bin/learn-you-php', $this->tmpDir));
        $this->assertSame($target, readlink(sprintf('%s/bin/learn-you-php', $this->tmpDir)));
    }

    public function testSuccessButBinDirNotInPath(): void
    {
        putenv('PATH=/not-a-dir');
        $this->io->expects($this->once())
            ->method('writeln')
            ->with([
                ' <error>The PHP School bin directory is not in your PATH variable.</error>',
               '',
               sprintf(
                   ' Add "%s/bin" to your PATH variable before running the workshop',
                   $this->tmpDir
               ),
               sprintf(
                   ' e.g. Run <info>$ echo \'export PATH="$PATH:%s/bin"\' >> ~/.bashrc && source ~/.bashrc</info>',
                   $this->tmpDir
               ),
               ' Replacing ~/.bashrc with your chosen bash config file e.g. ~/.zshrc or ~/.profile etc',
               sprintf(
                   ' You can validate your PATH variable is configured correctly by running <info>%s validate</info>',
                   $_SERVER['argv'][0]
               )
            ]);


        $target = sprintf('%s/workshops/learn-you-php/bin/learn-you-php', $this->tmpDir);
        mkdir(dirname($target), 0775, true);
        touch($target);

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->link($workshop);

        $this->assertFileExists(sprintf('%s/bin/learn-you-php', $this->tmpDir));
        $this->assertSame($target, readlink(sprintf('%s/bin/learn-you-php', $this->tmpDir)));
    }

    public function testUnlinkErrorIsPrintedIfNonSymlinkExistsAtTarget(): void
    {
        $path = sprintf('%s/bin/learn-you-php', $this->tmpDir);
        mkdir(dirname($path), 0775, true);
        touch($path);

        $this->io
            ->expects($this->once())
            ->method('write')
            ->with(
                [
                    sprintf(' <error> Unknown file exists at path "%s" </error>', $path),
                    ' <info>Not removing</info>'
                ]
            );

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->unlink($workshop);
    }

    public function testUnlinkErrorIsPrintedIfFileCannotBeRemoved(): void
    {
        $fs = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['remove'])
            ->getMock();

        $this->linker = new Linker($fs, $this->tmpDir, $this->io);

        $path = sprintf('%s/bin/learn-you-php', $this->tmpDir);
        $fs->expects($this->once())
            ->method('remove')
            ->with($path)
            ->willThrowException(new IOException('Some error'));

        mkdir(dirname($path), 0775, true);
        touch(sprintf('%s/test', $this->tmpDir));
        symlink(sprintf('%s/test', $this->tmpDir), $path);

        $this->io
            ->expects($this->once())
            ->method('write')
            ->with(
                [
                    sprintf(' <error> Failed to remove file at path "%s" </error>', $path),
                    ' <info>You may need to remove a blocking file manually with elevated privileges</info>'
                ]
            );

        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->linker->unlink($workshop);
    }
}

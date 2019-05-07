<?php

namespace PhpSchool\WorkshopManagerTest;

use PhpSchool\WorkshopManager\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class FilesystemTest
 * @package PhpSchool\WorkshopManagerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class FilesystemTest extends TestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $tmpDir;

    public function setUp()
    {
        $this->filesystem = new Filesystem;
        $this->tmpDir = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->tmpDir);
    }

    public function testExecuteInPathThrowsExceptionIfPathNotExists()
    {
        rmdir($this->tmpDir);
        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf('Path: "%s" does not exist.', $this->tmpDir));
        $this->filesystem->executeInPath($this->tmpDir, function () {
        });
    }

    public function testExecuteInPath()
    {
        $currentDir = getcwd();

        $dir = null;
        $this->filesystem->executeInPath($this->tmpDir, function () use (&$dir) {
            $dir = getcwd();
        });
        $this->assertSame($this->tmpDir, $dir);
        $this->assertSame($currentDir, getcwd());
    }

    public function testIsLink()
    {
        $path = sprintf('%s/test', $this->tmpDir);
        touch($path);

        $this->assertFalse($this->filesystem->isLink($path));

        $link = sprintf('%s/link', $this->tmpDir);
        symlink($path, $link);

        $this->assertTrue($this->filesystem->isLink($link));
        unlink($link);
        unlink($path);
    }

    public function testIsWritable()
    {
        $path = sprintf('%s/test', $this->tmpDir);
        touch($path);
        $this->assertTrue($this->filesystem->isWritable($path));
        chmod($path, 0000);
        $this->assertFalse($this->filesystem->isWritable($path));
        unlink($path);
    }

    public function tearDown()
    {
        @rmdir($this->tmpDir);
    }
}

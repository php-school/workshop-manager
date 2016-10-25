<?php

namespace PhpSchool\WorkshopManagerTest;

use Composer\Factory;
use PhpSchool\WorkshopManager\ComposerInstaller;
use PhpSchool\WorkshopManager\Filesystem;
use PHPUnit_Framework_TestCase;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ComposerInstallerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function setUp()
    {
        $this->filesystem = new Filesystem;
        $this->tempDir = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->tempDir, 0777, true);
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testComposerOutputIsWrittenIfInVerboseMode()
    {
        $input  = new ArrayInput([]);
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $installer = new ComposerInstaller($input, $output, new Factory);
        file_put_contents(sprintf('%s/composer.json', $this->tempDir), '{"name" : "learnyouphp", "require" : { "php": ">=5.6"}}');
        $res = $installer->install($this->tempDir);

        $this->assertFileExists(sprintf('%s/vendor', $this->tempDir));
        $this->assertFileExists(sprintf('%s/composer.lock', $this->tempDir));

        $expectedOutput  = "/Loading composer repositories with package information\n";
        $expectedOutput .= "Updating dependencies\n";
        $expectedOutput .= "Dependency resolution completed in \\d+\\.\\d+ seconds\n";
        $expectedOutput .= "Analyzed \\d+ packages to resolve dependencies\n";
        $expectedOutput .= "Analyzed \\d+ rules to resolve dependencies\n";
        $expectedOutput .= "Nothing to install or update\n";
        $expectedOutput .= "Writing lock file\n";
        $expectedOutput .= "Generating autoload files\n/";
        $this->assertRegExp($expectedOutput, $output->fetch());
        $this->assertRegExp($expectedOutput, strip_tags($res->getOutput()));
        $this->assertEquals(0, $res->getExitCode());
    }

    public function testComposerOutputIsNotWrittenIfNotInVerboseMode()
    {
        $input  = new ArrayInput([]);
        $output = new BufferedOutput;
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        $installer = new ComposerInstaller($input, $output, new Factory);
        file_put_contents(sprintf('%s/composer.json', $this->tempDir), '{"name" : "learnyouphp", "require" : { "php": ">=5.6"}}');
        $res = $installer->install($this->tempDir);

        $this->assertFileExists(sprintf('%s/vendor', $this->tempDir));
        $this->assertFileExists(sprintf('%s/composer.lock', $this->tempDir));

        $expectedOutput  = "/Loading composer repositories with package information\n";
        $expectedOutput .= "Updating dependencies\n";
        $expectedOutput .= "Dependency resolution completed in \\d+\\.\\d+ seconds\n";
        $expectedOutput .= "Analyzed \\d+ packages to resolve dependencies\n";
        $expectedOutput .= "Analyzed \\d+ rules to resolve dependencies\n";
        $expectedOutput .= "Nothing to install or update\n";
        $expectedOutput .= "Writing lock file\n";
        $expectedOutput .= "Generating autoload files\n/";
        $this->assertEquals('', $output->fetch());
        $this->assertRegExp($expectedOutput, strip_tags($res->getOutput()));
        $this->assertEquals(0, $res->getExitCode());
    }

    public function testExceptionIsThrownIfNoComposerJson()
    {
        $input  = new ArrayInput([]);
        $output = new BufferedOutput;
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        $this->expectException(\InvalidArgumentException::class);

        $installer = new ComposerInstaller($input, $output, new Factory);
        $installer->install($this->tempDir);
    }

    public function testExceptionIsThrownIfInvalidComposerJson()
    {
        $input  = new ArrayInput([]);
        $output = new BufferedOutput;
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        $this->expectException(ParsingException::class);

        $installer = new ComposerInstaller($input, $output, new Factory);
        file_put_contents(sprintf('%s/composer.json', $this->tempDir), '{"name" : "learnyouphp"');
        $installer->install($this->tempDir);
    }
}

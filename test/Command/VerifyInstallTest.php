<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use PhpSchool\WorkshopManager\Command\VerifyInstall;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class VerifyInstallTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var OutputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $output;

    /**
     * @var InputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $input;

    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var VerifyInstall
     */
    private $command;

    public function setUp()
    {
        $this->output = new BufferedOutput;
        $this->output->getFormatter()->setStyle('phps', new OutputFormatterStyle('magenta'));
        $this->input = $this->createMock(InputInterface::class);
        $this->tmpDir = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        $this->command = new VerifyInstall($this->input, $this->output, $this->tmpDir);
    }

    public function testErrorIsPrintedIfWorkshopDirNotInPath()
    {
        putenv('PATH=/not-a-dir');

        $this->command->__invoke();

        $output = $this->output->fetch();
        $this->assertRegExp(
            sprintf('/%s/', preg_quote('The PHP School bin directory is not in your PATH variable.')),
            $output
        );
    }

    public function testSuccessIsPrintedIfWorkshopDirInPath()
    {
        putenv(sprintf('PATH=%s/bin', $this->tmpDir));

        $this->command->__invoke();

        $output = $this->output->fetch();
        $this->assertRegExp(
            sprintf('/%s/', preg_quote('Your $PATH environment variable is configured correctly')),
            $output
        );
    }
}

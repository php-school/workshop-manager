<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use PhpSchool\WorkshopManager\Command\InstallWorkshop;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallWorkshopTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var InstallWorkshop
     */
    private $command;

    /**
     * @var OutputInterface
     */
    private $output;

    public function setUp()
    {
        $this->installer = $this->createMock(Installer::class);
        $this->command = new InstallWorkshop($this->installer);
        $this->output = $this->createMock(OutputInterface::class);
    }

    public function testWhenWorkshopIsAlreadyInstalled()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new WorkshopAlreadyInstalledException);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <info>\"learnyouphp\" is already installed, you're ready to learn!</info>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testWhenWorkshopDoesNotExistInRegistry()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new WorkshopNotFoundException);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <fg=magenta> No workshops found matching \"learnyouphp\", did you spell it correctly? </>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testWhenDownloadFails()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new DownloadFailureException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> There was a problem downloading the workshop. Error: \"Some error\"</error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testWhenFailedToMove()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new FailedToMoveWorkshopException('/root/src', '/root/workshops/learnyouphp'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [
                    [
                        ' <error> There was a problem moving downloaded files for "learnyouphp"   </error>',
                        " Please check your file permissions for the following paths\n",
                        ' <info>/root</info>',
                        ' <info>/root/workshops</info>',
                    '',
                    ]
                ]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testWhenComposerInstallFails()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new ComposerFailureException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> There was a problem installing dependencies for \"learnyouphp\" </error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testAnyOtherFailure()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new \Exception('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> An unknown error occurred: \"Some error\" </error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testExceptionIsThrownIfInVerboseMode()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new \Exception('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> An unknown error occurred: \"Some error\" </error>\n"]
            );

        $this->output
            ->expects($this->once())
            ->method('isVerbose')
            ->willReturn(true);

        $this->expectException(\Exception::class);

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testSuccess()
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', false);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <info>Successfully installed \"learnyouphp\"</info>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }
}

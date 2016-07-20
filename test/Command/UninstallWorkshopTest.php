<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use PhpSchool\WorkshopManager\Command\UninstallWorkshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Uninstaller;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class UninstallWorkshopTest
 * @package PhpSchool\WorkshopManagerTest\Command
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UninstallWorkshopTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Uninstaller
     */
    private $uninstaller;

    /**
     * @var UninstallWorkshop
     */
    private $command;

    /**
     * @var OutputInterface
     */
    private $output;

    public function setUp()
    {
        $this->uninstaller = $this->createMock(Uninstaller::class);
        $this->command = new UninstallWorkshop($this->uninstaller);
        $this->output = $this->createMock(OutputInterface::class);
    }

    public function testWhenWorkshopIsNotInstalled()
    {
        $this->uninstaller
            ->expects($this->once())
            ->method('uninstallWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new WorkshopNotFoundException);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <fg=magenta> It doesn't look like \"learnyouphp\" is installed, did you spell it correctly?</>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testWhenFilesCannotBeCleanedUp()
    {
        $this->uninstaller
            ->expects($this->once())
            ->method('uninstallWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new IOException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> Failed to uninstall workshop \"learnyouphp\". Error: \"Some error\" </error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testExceptionIsThrownIfInVerboseMode()
    {
        $this->uninstaller
            ->expects($this->once())
            ->method('uninstallWorkshop')
            ->with('learnyouphp', false)
            ->willThrowException(new IOException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> Failed to uninstall workshop \"learnyouphp\". Error: \"Some error\" </error>\n"]
            );

        $this->output
            ->expects($this->once())
            ->method('isVerbose')
            ->willReturn(true);

        $this->expectException(IOException::class);

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }

    public function testSuccess()
    {
        $this->uninstaller
            ->expects($this->once())
            ->method('uninstallWorkshop')
            ->with('learnyouphp', false);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <info>Successfully uninstalled \"learnyouphp\"</info>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', false);
    }
}

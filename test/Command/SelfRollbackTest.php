<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use Exception;
use Humbug\SelfUpdate\Updater;
use PhpSchool\WorkshopManager\Command\ListWorkshops;
use PhpSchool\WorkshopManager\Command\SelfRollback;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class SelfRollbackTest extends TestCase
{
    /**
     * @var Updater
     */
    private $updater;

    /**
     * @var ListWorkshops
     */
    private $command;

    /**
     * @var BufferedOutput
     */
    private $output;

    public function setUp(): void
    {
        $this->updater = $this->createMock(Updater::class);
        $this->command = new SelfRollback($this->updater);
        $this->output = new BufferedOutput();
    }

    public function testUnknownError(): void
    {
        $this->updater
            ->expects($this->once())
            ->method('rollback')
            ->willReturn(false);

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();

        $this->assertStringContainsString('Unknown error rolling back workshop-manager', $output);
    }

    public function testExceptionThrown(): void
    {
        $this->updater
            ->expects($this->once())
            ->method('rollback')
            ->willThrowException(new Exception('Some error'));

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();

        $this->assertStringContainsString('Error rolling back workshop-manager: Some error', $output);
    }

    public function testSuccess(): void
    {
        $this->updater
            ->expects($this->once())
            ->method('rollback')
            ->willReturn(true);

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();

        $this->assertStringContainsString('Successfully rolled back to previous version of workshop-manage', $output);
    }
}

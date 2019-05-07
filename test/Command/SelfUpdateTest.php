<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use Exception;
use Humbug\SelfUpdate\Updater;
use PhpSchool\WorkshopManager\Command\ListWorkshops;
use PhpSchool\WorkshopManager\Command\SelfUpdate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class SelfUpdateTest extends TestCase
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

    public function setUp()
    {
        $this->updater = $this->createMock(Updater::class);
        $this->command = new SelfUpdate($this->updater);
        $this->output = new BufferedOutput;
    }

    public function testNoUpdateNeeded()
    {
        $this->updater
            ->expects($this->once())
            ->method('update')
            ->willReturn(false);

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();

        $this->assertContains('No update necessary!', $output);
    }

    public function testExceptionThrown()
    {
        $this->updater
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new Exception('Some error'));

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();

        $this->assertContains('Error updating workshop-manager: Some error', $output);
    }

    public function testSuccess()
    {
        $this->updater
            ->expects($this->once())
            ->method('update')
            ->willReturn(true);

        $this->updater
            ->expects($this->once())
            ->method('getNewVersion')
            ->willReturn('2.0.0');

        $this->updater
            ->expects($this->once())
            ->method('getOldVersion')
            ->willReturn('1.0.0');

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();

        $this->assertContains('Successfully updated workshop-manager from version 1.0.0 to 2.0.0', $output);
    }
}

<?php

namespace PhpSchool\WorkshopManagerTest;

use PhpSchool\WorkshopManager\RecordingOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RecordingOutputTest extends TestCase
{
    /**
     * @var RecordingOutput
     */
    private $output;

    /**
     * @var BufferedOutput
     */
    private $wrappedOutput;

    public function setup()
    {
        $this->wrappedOutput = new BufferedOutput;
        $this->output = new RecordingOutput($this->wrappedOutput);
    }

    public function testMethodsDelegateToWrapped()
    {
        $this->assertSame($this->wrappedOutput->isDebug(), $this->output->isDebug());
        $this->assertSame($this->wrappedOutput->isVerbose(), $this->output->isVerbose());
        $this->assertSame($this->wrappedOutput->isVeryVerbose(), $this->output->isVeryVerbose());
        $this->assertSame($this->wrappedOutput->getVerbosity(), $this->output->getVerbosity());
        $this->assertSame($this->wrappedOutput->isDecorated(), $this->output->isDecorated());
        $this->assertSame($this->wrappedOutput->getFormatter(), $this->output->getFormatter());
        $this->assertSame($this->wrappedOutput->isQuiet(), $this->output->isQuiet());
    }

    public function testSettersDelegateToWrapped()
    {
        $formatter = new OutputFormatter;
        $this->assertNotSame($formatter, $this->wrappedOutput->getFormatter());
        $this->assertFalse($this->wrappedOutput->isDecorated());
        $this->assertEquals(OutputInterface::VERBOSITY_NORMAL, $this->wrappedOutput->getVerbosity());

        $this->output->setFormatter($formatter);
        $this->assertSame($formatter, $this->wrappedOutput->getFormatter());

        $this->output->setDecorated(true);
        $this->assertTrue($this->output->isDecorated());

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->assertEquals(OutputInterface::VERBOSITY_VERBOSE, $this->wrappedOutput->getVerbosity());
    }

    public function testWriteWithRecordsAndDelegates()
    {
        $this->output->write('Hello', false);

        $this->assertEquals('Hello', $this->output->getOutput());
        $this->assertEquals('Hello', $this->wrappedOutput->fetch());
    }

    public function testWriteWithArrayAndRecordsAndDelegates()
    {
        $this->output->write(['Hello', 'Aydin'], false);

        $this->assertEquals('HelloAydin', $this->output->getOutput());
        $this->assertEquals('HelloAydin', $this->wrappedOutput->fetch());
    }

    public function testWriteWithNewLineRecordsAndDelegates()
    {
        $this->output->write('Hello', true);

        $this->assertEquals("Hello\n", $this->output->getOutput());
        $this->assertEquals("Hello\n", $this->wrappedOutput->fetch());
    }

    public function testWriteWithArrayAndNewLineRecordsAndDelegates()
    {
        $this->output->write(['Hello', 'Aydin'], true);

        $this->assertEquals("Hello\nAydin\n", $this->output->getOutput());
        $this->assertEquals("Hello\nAydin\n", $this->wrappedOutput->fetch());
    }

    public function testWriteLineRecordsAndDelegates()
    {
        $this->output->writeln('Hello');

        $this->assertEquals("Hello\n", $this->output->getOutput());
        $this->assertEquals("Hello\n", $this->wrappedOutput->fetch());
    }

    public function testWriteLineWithArrayRecordsAndDelegates()
    {
        $this->output->writeln(['Hello', 'Aydin']);

        $this->assertEquals("Hello\nAydin\n", $this->output->getOutput());
        $this->assertEquals("Hello\nAydin\n", $this->wrappedOutput->fetch());
    }
}

<?php

namespace PhpSchool\WorkshopManager;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecordingOutput implements OutputInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $buffer = '';

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    /**
     * @param string|array $messages The message as an array of lines or a single string
     */
    public function write($messages, $newline = false, $options = 0)
    {
        $messages = (array) $messages;
        $this->buffer .= sprintf('%s%s', implode($newline ? "\n" : '', $messages), $newline ? "\n" : '');
        return $this->output->write($messages, $newline, $options);
    }

    /**
     * @param string|array $messages The message as an array of lines of a single string
     */
    public function writeln($messages, $options = 0)
    {
        return $this->write($messages, true, $options);
    }

    public function setVerbosity($level): void
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function setDecorated($decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function getOutput(): string
    {
        //see \Composer\IO\BufferIO
        return preg_replace_callback("{(?<=^|\n|\x08)(.+?)(\x08+)}", function ($matches) {
            $pre = strip_tags($matches[1]);

            if (strlen($pre) === strlen($matches[2])) {
                return '';
            }

            return rtrim($matches[1]) . "\n";
        }, $this->buffer);
    }
}

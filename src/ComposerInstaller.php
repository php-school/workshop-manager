<?php

namespace PhpSchool\WorkshopManager;

use Composer\Factory;
use Composer\Installer;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ComposerInstaller
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;
    
    /**
     * @var Factory
     */
    private $composerFactory;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Factory $composerFactory
     */
    public function __construct(InputInterface $input, OutputInterface $output, Factory $composerFactory)
    {
        $this->input = $input;
        $this->output = $output;
        $this->composerFactory = $composerFactory;
    }

    /**
     * @param string $pathToComposerProject
     * @return InstallResult
     */
    public function install($pathToComposerProject)
    {
        if ($this->output->isVerbose()) {
            $output = $this->output;
        } else {
            //write all output in verbose mode to a temp stream
            //so we don't write it out when not in verbose mode
            $output = new StreamOutput(
                fopen('php://memory', 'w'),
                OutputInterface::VERBOSITY_VERY_VERBOSE,
                $this->output->isDecorated(),
                $this->output->getFormatter()
            );
        }

        $wrappedOutput = new RecordingOutput($output);
        $io            = new ConsoleIO($this->input, $wrappedOutput, new HelperSet);

        $composer = $this->composerFactory->createComposer(
            $io,
            sprintf('%s/composer.json', rtrim($pathToComposerProject, '/')),
            false,
            $pathToComposerProject
        );

        return new InstallResult(
            Installer::create($io, $composer)->run(),
            $wrappedOutput->getOutput()
        );
    }
}

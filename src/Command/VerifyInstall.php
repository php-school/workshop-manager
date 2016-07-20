<?php

namespace PhpSchool\WorkshopManager\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class VerifyInstall
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
     * @var string
     */
    private $workshopHomeDirectory;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $workshopHomeDirectory
     */
    public function __construct(InputInterface $input, OutputInterface $output, $workshopHomeDirectory)
    {
        $this->input = $input;
        $this->output = $output;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
    }

    public function __invoke()
    {
        $style = new SymfonyStyle($this->input, $this->output);

        $style->title("Verifying your installation");

        
        if (strpos(getenv('PATH'), sprintf('%s/bin', $this->workshopHomeDirectory)) !== false) {
            $style->success('Your $PATH environment variable is configured correctly');
        } else {
            $style->error('The PHP School bin directory is not in your PATH variable.');

            $this->output->writeln([
                sprintf(
                    ' Add "%s/bin" to your PATH variable before running a workshop',
                    $this->workshopHomeDirectory
                ),
                '',
                sprintf(
                    ' Use the command: <info>echo \'export PATH="$PATH:%s/bin"\' >> ~/.bashrc && source ~/.bashrc</info>',
                    $this->workshopHomeDirectory
                ),
                ' replacing <info>~/.bashrc</info> with your chosen terminal config file e.g. <info>~/.zshrc</info>' .
                ' or <info>~/.profile</info> etc',
                '',
                ' <phps>Run this command again to confirm the PATH variable has been updated.</phps>',
                ''
            ]);
        }
    }
}

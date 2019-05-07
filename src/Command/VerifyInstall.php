<?php

namespace PhpSchool\WorkshopManager\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VerifyInstall
{
    /**
     * @var array
     */
    private static $requiredExtensions = ['json', 'zip', 'mbstring', 'curl'];

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
            $style->success('Your $PATH environment variable is configured correctly.');
        } else {
            $style->error('The PHP School bin directory is not in your PATH variable.');

            $this->output->writeln([
                sprintf(
                    ' Add "%s/bin" to your PATH variable before running a workshop',
                    $this->workshopHomeDirectory
                ),
                '',
                sprintf(
                    ' Use the command: <info>echo \'export PATH="$PATH:%s/bin"\' >> ~/.bashrc && source' .
                    ' ~/.bashrc</info>',
                    $this->workshopHomeDirectory
                ),
                ' replacing <info>~/.bashrc</info> with your chosen terminal config file e.g. <info>~/.zshrc</info>' .
                ' or <info>~/.profile</info> etc',
                '',
                ' <phps>Run this command again to confirm the PATH variable has been updated.</phps>',
                ''
            ]);
        }

        if (version_compare(PHP_VERSION, '5.6')) {
            $message  = 'Your PHP version is %s, PHP 5.6 is the minimum supported version for this tool. Please note ';
            $message .= 'that some workshops may require a higher version of PHP, so you may not be able to install ';
            $message .= 'them without upgrading PHP.';
            $style->success(sprintf($message, PHP_VERSION));
        } else {
            $style->error('You need a PHP version of at least 5.6 to use PHP School.');
        }

        $missingExtensions = array_filter(static::$requiredExtensions, function($extension) {
            return !extension_loaded($extension);
        });

        array_walk($missingExtensions, function($missingExtension) use ($style) {
            $style->error(
                sprintf(
                    'The %s extension is missing - use your preferred package manager to install it.',
                    $missingExtension
                )
            );
        });

        if (empty($missingExtensions)) {
            $message  = 'All required PHP extensions are installed. Please note that some workshops may require ';
            $message .= 'additional PHP extensions.';
            $style->success($message);
        }
    }
}

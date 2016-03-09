<?php

namespace PhpSchool\WorkshopManager\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UnlinkCommand
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class UnlinkCommand extends Command
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('unlink')
            ->setDescription('Remove the symlink for an installed workshop')
            ->addArgument('workshop', InputArgument::REQUIRED, 'What workshop would you like to unlink');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: Reimplement
        return;

        $workshop     = $input->getArgument('workshop');
        $homePath     = strtolower(substr(PHP_OS, 0, 3)) === 'win' ? getenv('USERPROFILE') : getenv('HOME');
        $homeBinPath  = sprintf('%s/bin', $homePath);
        $workshopPath = sprintf('%s/%s', $homeBinPath, $workshop);

        if (!is_link($workshopPath)) {
            $output->writeln(sprintf(' <error>Link not found in "%s"</error>', $workshopPath));
            return;
        }

        unlink($workshopPath);
        $output->writeln(sprintf(' <info>Workshop "%s" executable unlinked succesfully</info>', $workshop));
    }
}

<?php

namespace PhpSchool\WorkshopManager\Command;

use Exception;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdate
{
    /**
     * @var Updater
     */
    private $updater;

    public function __construct(Updater $updater)
    {
        $this->updater = $updater;
    }

    public function __invoke(OutputInterface $output): void
    {
        try {
            $result = $this->updater->update();
            if (!$result) {
                $output->writeln([
                    '',
                    '<fg=magenta>No update necessary!</>',
                    ''
                ]);
                return;
            }
            $new = $this->updater->getNewVersion();
            $old = $this->updater->getOldVersion();

            $output->writeln([
                '',
                sprintf('<fg=magenta>Successfully updated workshop-manager from version %s to %s</>', $old, $new),
                ''
            ]);
        } catch (Exception $e) {
            $output->writeln([
                '',
                sprintf('<error>Error updating workshop-manager: %s</error>', $e->getMessage()),
                ''
            ]);
        }
    }
}

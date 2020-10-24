<?php

namespace PhpSchool\WorkshopManager\Command;

use Exception;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Output\OutputInterface;

class SelfRollback
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
            $result = $this->updater->rollback();
            if (!$result) {
                $output->writeln([
                    '',
                    '<error>Unknown error rolling back workshop-manager</error>',
                    ''
                ]);
                return;
            }
        } catch (Exception $e) {
            $output->writeln([
                '',
                sprintf('<error>Error rolling back workshop-manager: %s</error>', $e->getMessage()),
                ''
            ]);
            return;
        }

        $output->writeln([
            '',
            sprintf('<fg=magenta>Successfully rolled back to previous version of workshop-manager</>'),
            ''
        ]);
    }
}

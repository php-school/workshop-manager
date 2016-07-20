<?php

namespace PhpSchool\WorkshopManager\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package PhpSchool\WorkshopManager\Command
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class SelfRollback
{
    /**
     * @var Updater
     */
    private $updater;

    /**
     * @param Updater $updater
     */
    public function __construct(Updater $updater)
    {
        $this->updater = $updater;
    }

    public function __invoke(OutputInterface $output)
    {
        try {
            $result = $this->updater->rollback();
            if (!$result) {
                return $output->writeln([
                    '',
                    '<error>Unknown error rolling back workshop-manager</error>',
                    ''
                ]);
            }
        } catch (\Exception $e) {
            return $output->writeln([
                '',
                sprintf('<error>Error rolling back workshop-manager: %s</error>', $e->getMessage()),
                ''
            ]);
        }

        $output->writeln([
            '',
            sprintf('<fg=magenta>Successfully rolled back to previous version of workshop-manager</>'),
            ''
        ]);
    }
}

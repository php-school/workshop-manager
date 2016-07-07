<?php

namespace PhpSchool\WorkshopManager\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package PhpSchool\WorkshopManager\Command
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class SelfUpdate
{
    private static $pharFile = 'https://php-school.github.io/workshop-manager/workshop-manager.phar';
    private static $versionFile = 'https://php-school.github.io/workshop-manager/workshop-manager.phar.version';

    public function __invoke(OutputInterface $output)
    {
        $updater = new Updater(null, false);
        $updater->getStrategy()->setPharUrl(static::$pharFile);
        $updater->getStrategy()->setVersionUrl(static::$versionFile);

        try {
            $result = $updater->update();
            if (!$result) {
                return $output->writeln([
                    '',
                    '<fg=magenta>No update necessary!</>',
                    ''
                ]);
            }
            $new = $updater->getNewVersion();
            $old = $updater->getOldVersion();

            $output->writeln([
                '',
                sprintf('<fg=magenta>Updated workshop-manager from version %s to %s</>', $old, $new),
                ''
            ]);
        } catch (\Exception $e) {
            $output->writeln([
                '',
                sprintf('<error>Error updating workshop-manager: %s</error>', $e->getMessage()),
                ''
            ]);
        }
    }
}

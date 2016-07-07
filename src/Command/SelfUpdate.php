<?php

namespace PhpSchool\WorkshopManager\Command;

use Humbug\SelfUpdate\Updater;

/**
 * Class SelfUpdate
 * @package PhpSchool\WorkshopManager\Command
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class SelfUpdate
{
    private static $pharFile = 'https://php-school.github.io/workshop-manager/workshop-manager.phar';
    private static $versionFile = 'https://php-school.github.io/workshop-manager/workshop-manager.phar.version';

    public function __invoke()
    {
        $updater = new Updater();
        $updater->getStrategy()->setPharUrl(static::$pharFile);
        $updater->getStrategy()->setVersionUrl(static::$versionFile);

        try {
            $result = $updater->update();
            if (!$result) {
                // No update needed!
                exit(0);
            }
            $new = $updater->getNewVersion();
            $old = $updater->getOldVersion();
            printf('Updated from %s to %s', $old, $new);
            exit(0);
        } catch (\Exception $e) {
            // Report an error!
            exit(1);
        }
    }
}

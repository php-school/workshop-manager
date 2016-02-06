<?php

switch (true) {
    case (file_exists(__DIR__ . '/../vendor/autoload.php')):
        // Installed standalone
        require __DIR__ . '/../vendor/autoload.php';
        break;
    case (file_exists(__DIR__ . '/../../../autoload.php')):
        // Installed as a Composer dependency
        require __DIR__ . '/../../../autoload.php';
        break;
    case (file_exists('vendor/autoload.php')):
        // As a Composer dependency, relative to CWD
        require 'vendor/autoload.php';
        break;
    default:
        throw new RuntimeException('Unable to locate Composer autoloader; please run "composer install".');
}

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Command\InstallCommand;
use PhpSchool\WorkshopManager\Command\LinkCommand;
use PhpSchool\WorkshopManager\Command\ListCommand;
use PhpSchool\WorkshopManager\Command\SearchCommand;
use PhpSchool\WorkshopManager\Command\UninstallCommand;
use PhpSchool\WorkshopManager\Command\UnlinkCommand;
use Symfony\Component\Console\Application;

ini_set('display_errors', 1);

// Unix or Windows home path
$homePath   = strtolower(substr(PHP_OS, 0, 3)) === 'win'
    ? getenv('USERPROFILE')
    : getenv('HOME');

$appPath    = realpath(sprintf('%s/.php-school', $homePath));
$filesystem = new Filesystem(new Local($appPath));

$application = new Application();
$application->add(new InstallCommand($filesystem));
$application->add(new UninstallCommand($filesystem));
$application->add(new SearchCommand);
$application->add(new ListCommand($filesystem));
$application->add(new LinkCommand($filesystem));
$application->add(new UnlinkCommand);
$application->setAutoExit(false);
$application->run();

// Cleanup temp
$filesystem->deleteDir('.temp');

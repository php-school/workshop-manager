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
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\WorkshopInstaller;
use PhpSchool\WorkshopManager\WorkshopManager;
use Symfony\Component\Console\Application;

ini_set('display_errors', 1);

// Unix or Windows home path
$homePath   = strtolower(substr(PHP_OS, 0, 3)) === 'win'
    ? getenv('USERPROFILE')
    : getenv('HOME');

$appPath       = realpath(sprintf('%s/.php-school', $homePath));
$filesystem    = new Filesystem(new Local($appPath));

// Build Workshop entity array
$workshopsJson = json_decode(file_get_contents(sprintf('%s/workshops.json', __DIR__)), true);
$workshops     = array_map(function ($workshop) {
    return new Workshop(
        $workshop['name'],
        $workshop['display_name'],
        $workshop['owner'],
        $workshop['repo'],
        $workshop['description']
    );
}, $workshopsJson['workshops']);

$workshopRepository = new WorkshopRepository($workshops);

$workshopManager = new WorkshopManager(
    new WorkshopInstaller($filesystem),
    $workshopRepository,
    $filesystem
);

$application = new Application();
$application->add(new InstallCommand($filesystem));
$application->add(new UninstallCommand($filesystem));
$application->add(new SearchCommand($workshopRepository));
$application->add(new ListCommand($workshopManager));
$application->add(new LinkCommand($filesystem));
$application->add(new UnlinkCommand);
$application->setAutoExit(false);
$application->run();

// Cleanup temp
$filesystem->deleteDir('.temp');

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

use PhpSchool\WorkshopManager\ManagerState;
use Symfony\Component\Console\Application;

ini_set('display_errors', 1);


// TODO: Detect non Unix envs, pure Windows and bail out kindly.
// TODO: Ensure Cygwin and other compatible envs still work.

$container = (new \DI\ContainerBuilder())
    ->addDefinitions(__DIR__ . '/config.php')
    ->useAutowiring(false)
    ->build();

$container->get(Application::class)->run();
$container->get(ManagerState::class)->clearTemp();

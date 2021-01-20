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

use PhpSchool\WorkshopManager\Application;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;
use PhpSchool\WorkshopManager\ManagerState;
use Symfony\Component\Console\Output\OutputInterface;

ini_set('display_errors', 1);
error_reporting(E_ALL);

$container = (new \DI\ContainerBuilder())
    ->addDefinitions(__DIR__ . '/config.php')
    ->useAutowiring(false)
    ->build();


$app = $container->get(Application::class);

if (DIRECTORY_SEPARATOR === '\\') {
    $container->get(OutputInterface::class)->writeln([
        '',
        ' Woops!... It looks like your not running in a Unix environment',
        '',
        ' Currently we only support Unix based systems, if you\'re running Windows please use Cygwin',
        ' See <info>https://phpschool.io/install#windows</info> for more details',
        '',
    ]);
    exit;
}

try {
    exit($app->run());
} catch (RequiresNetworkAccessException $e) {
    $container->get(OutputInterface::class)
        ->writeln([
            '',
            '  <error>This command requires an internet connection, please connect and try again.</error>',
            ''
        ]);
} catch (\Exception $e) {
    $app->renderThrowable($e, $container->get(Symfony\Component\Console\Output\OutputInterface::class));
    exit(1);
}

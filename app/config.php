<?php

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Github\Client;
use Interop\Container\ContainerInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Command\InstallCommand;
use PhpSchool\WorkshopManager\Command\LinkCommand;
use PhpSchool\WorkshopManager\Command\ListCommand;
use PhpSchool\WorkshopManager\Command\SearchCommand;
use PhpSchool\WorkshopManager\Command\UninstallCommand;
use PhpSchool\WorkshopManager\Command\UnlinkCommand;
use PhpSchool\WorkshopManager\Downloader;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\IOFactory;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use Symfony\Component\Console\Application;

return [
    Application::class => \DI\factory(function (ContainerInterface $c) {
        $application = new Application();
        $application->add($c->get(InstallCommand::class));
        $application->add($c->get(UninstallCommand::class));
        $application->add($c->get(SearchCommand::class));
        $application->add($c->get(ListCommand::class));
        $application->add($c->get(LinkCommand::class));
        $application->add($c->get(UnlinkCommand::class));
        $application->setAutoExit(false);

        return $application;
    }),
    InstallCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new InstallCommand(
            $c->get(Installer::class),
            $c->get(WorkshopRepository::class),
            $c->get(IOFactory::class)
        );
    }),
    UninstallCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new UninstallCommand($c->get(Uninstaller::class));
    }),
    SearchCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new SearchCommand($c->get(WorkshopRepository::class));
    }),
    ListCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new ListCommand($c->get(ManagerState::class));
    }),
    LinkCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new LinkCommand($c->get(Filesystem::class));
    }),
    UnlinkCommand::class => \DI\object(),
    Installer::class => \DI\factory(function (ContainerInterface $c) {
        return new Installer(
            $c->get(ManagerState::class),
            $c->get(Downloader::class),
            $c->get(Filesystem::class),
            $c->get(Factory::class)
        );
    }),
    Uninstaller::class => \DI\factory(function (ContainerInterface $c) {
        return new Uninstaller(
            $c->get(Filesystem::class),
            $c->get(WorkshopRepository::class),
            $c->get(ManagerState::class)
        );
    }),
    Downloader::class => \DI\factory(function (ContainerInterface $c) {
        return new Downloader(
            $c->get(Client::class),
            $c->get(Filesystem::class),
            $c->get(ManagerState::class)
        );
    }),
    Client::class => \DI\object(),
    Factory::class => \DI\object(),
    IOFactory::class => \DI\object(),
    IOInterface::class => \DI\factory(function () {
        return new NullIO;
    }),
    WorkshopRepository::class => \DI\factory(function (ContainerInterface $c) {
        return new WorkshopRepository($c->get('workshops'));
    }),
    'workshops' => \DI\factory(function (ContainerInterface $c) {
        $workshopsJson = $c->get('workshopData');
        return array_map(function ($workshop) {
            return new Workshop(
                $workshop['name'],
                $workshop['display_name'],
                $workshop['owner'],
                $workshop['repo'],
                $workshop['description']
            );
        }, $workshopsJson['workshops']);
    }),
    'workshopSrc' => 'https://raw.githubusercontent.com/php-school/workshop-manager/master/app/workshops.json',
    'workshopData' => \DI\factory(function (ContainerInterface $c) {
        return json_decode(file_get_contents($c->get('workshopSrc')), true);
    }),
    ManagerState::class => \DI\factory(function (ContainerInterface $c) {
        return new ManagerState($c->get(Filesystem::class), $c->get(WorkshopRepository::class));
    }),
    FileSystem::class => \DI\factory(function (ContainerInterface $c) {
        return new Filesystem($c->get(Local::class));
    }),
    Local::class => \DI\factory(function (ContainerInterface $c) {
        return new Local($c->get('rootDir'));
    }),
    'rootDir' => \DI\factory(function (ContainerInterface $c) {
        return realpath(sprintf('%s/.php-school', $c->get('homePath')));
    }),
    'homePath' => \DI\factory(function () {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            return getenv('USERPROFILE') ?: getenv('HOMEDRIVE') ?: getenv('HOMEPATH');
        }

        return getenv('HOME');
    })
];

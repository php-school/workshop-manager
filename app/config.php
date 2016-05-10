<?php

use Composer\Factory;
use Composer\IO\IOInterface;
use Github\Client;
use Interop\Container\ContainerInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Command\InstallCommand;
use PhpSchool\WorkshopManager\Command\ListCommand;
use PhpSchool\WorkshopManager\Command\SearchCommand;
use PhpSchool\WorkshopManager\Command\UninstallCommand;
use PhpSchool\WorkshopManager\Downloader;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\IOFactory;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

return [
    Application::class => \DI\factory(function (ContainerInterface $c) {
        $application = new Application();
        $application->add($c->get(InstallCommand::class));
        $application->add($c->get(UninstallCommand::class));
        $application->add($c->get(SearchCommand::class));
        $application->add($c->get(ListCommand::class));
        $application->setAutoExit(false);

        return $application;
    }),
    InstallCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new InstallCommand(
            $c->get(Installer::class),
            $c->get(Linker::class),
            $c->get(WorkshopRepository::class)
        );
    }),
    UninstallCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new UninstallCommand(
            $c->get(Uninstaller::class),
            $c->get(WorkshopRepository::class),
            $c->get(Linker::class)
        );
    }),
    SearchCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new SearchCommand($c->get(WorkshopRepository::class));
    }),
    ListCommand::class => \DI\factory(function (ContainerInterface $c) {
        return new ListCommand($c->get(ManagerState::class));
    }),
    Linker::class => \DI\factory(function (ContainerInterface $c) {
        return new Linker(
            $c->get(ManagerState::class),
            $c->get(Filesystem::class),
            $c->get(IOInterface::class)
        );
    }),
    Installer::class => \DI\factory(function (ContainerInterface $c) {
        return new Installer(
            $c->get(ManagerState::class),
            $c->get(Downloader::class),
            $c->get(Filesystem::class),
            $c->get(Factory::class),
            $c->get(IOFactory::class)->getNullableIO($c->get(InputInterface::class), $c->get(OutputInterface::class))
        );
    }),
    Uninstaller::class => \DI\factory(function (ContainerInterface $c) {
        return new Uninstaller(
            $c->get(Filesystem::class),
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
    IOInterface::class => \DI\factory(function (ContainerInterface $c) {
        return $c->get(IOFactory::class)->getIO(
            $c->get(InputInterface::class),
            $c->get(OutputInterface::class)
        );
    }),
    InputInterface::class => \Di\factory(function () {
        return new \Symfony\Component\Console\Input\ArgvInput($_SERVER['argv']);
    }),
    OutputInterface::class => \Di\factory(function (ContainerInterface $c) {
        $input     = $c->get(InputInterface::class);
        $verbosity = OutputInterface::VERBOSITY_NORMAL;

        if (true === $input->hasParameterOption(array('--quiet', '-q'), true)) {
            $verbosity = OutputInterface::VERBOSITY_QUIET;
        }

        if ($input->hasParameterOption('-vvv', true)) {
            $verbosity = OutputInterface::VERBOSITY_DEBUG;
        }

        if ($input->hasParameterOption('-vv', true)) {
            $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
        }

        if ($input->hasParameterOption('-v', true)) {
            $verbosity = OutputInterface::VERBOSITY_VERBOSE;
        }

        return new \Symfony\Component\Console\Output\ConsoleOutput($verbosity);
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
    Filesystem::class => \DI\factory(function (ContainerInterface $c) {
        return new Filesystem($c->get(Local::class));
    }),
    Local::class => \DI\factory(function () {
        return new Local(realpath(sprintf('%s/.php-school', getenv('HOME'))));
    })
];

<?php

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Github\Client;
use Interop\Container\ContainerInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Command\InstallWorkshop;
use PhpSchool\WorkshopManager\Command\ListWorkshops;
use PhpSchool\WorkshopManager\Command\SearchWorkshops;
use PhpSchool\WorkshopManager\Command\UninstallWorkshop;
use PhpSchool\WorkshopManager\Downloader;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\IOFactory;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use PhpSchool\WorkshopManager\WorkshopDataSource;
use Silly\Edition\PhpDi\Application;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

return [
    Application::class => \DI\factory(function (ContainerInterface $c) {
        $application = new Application('PHP School workshop manager', '1.0.0', $c);
        $application->command('install workshop [-f|--force]', InstallWorkshop::class)
            ->setDescription('Install a PHP School workshop');
        $application->command('uninstall workshop [-f|--force]', UninstallWorkshop::class)
            ->setDescription('Uninstall a PHP School workshop');
        $application->command('search workshop', SearchWorkshops::class)
            ->setDescription('Search for a PHP School workshop');
        $application->command('list', ListWorkshops::class)
            ->setDescription('List installed PHP School workshops');

        $application
            ->command('list-commands', function (OutputInterface $output) use ($application) {
                $helper = new DescriptorHelper();
                $helper->describe($output, $application);
            });

        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->setDefaultCommand('list-commands');

        return $application;
    }),
    InstallWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new InstallWorkshop(
            $c->get(Installer::class),
            $c->get(Linker::class),
            $c->get('workshopRepository')
        );
    }),
    UninstallWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new UninstallWorkshop(
            $c->get(Uninstaller::class),
            $c->get('installedWorkshopRepository'),
            $c->get(Linker::class)
        );
    }),
    SearchWorkshops::class => \DI\factory(function (ContainerInterface $c) {
        return new SearchWorkshops($c->get('workshopRepository'));
    }),
    ListWorkshops::class => \DI\factory(function (ContainerInterface $c) {
        return new ListWorkshops($c->get(ManagerState::class));
    }),
    Linker::class => \DI\factory(function (ContainerInterface $c) {
        return new Linker(
            $c->get('installedWorkshopRepository'),
            $c->get(Filesystem::class),
            $c->get(IOInterface::class)
        );
    }),
    Installer::class => \DI\factory(function (ContainerInterface $c) {
        return new Installer(
            $c->get('installedWorkshopRepository'),
            $c->get(Downloader::class),
            $c->get(Filesystem::class),
            $c->get(Factory::class),
            $c->get(IOFactory::class)->getNullableIO($c->get(InputInterface::class), $c->get(OutputInterface::class))
        );
    }),
    Uninstaller::class => \DI\factory(function (ContainerInterface $c) {
        return new Uninstaller(
            $c->get(Filesystem::class),
            $c->get('installedWorkshopRepository')
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
    'workshopRepository' => \DI\factory(function () {
        return WorkshopRepository::fromDataSource(WorkshopDataSource::createFromExternalSrc(
            'https://raw.githubusercontent.com/php-school/workshop-manager/master/app/workshops.json'
        ));
    }),
    'installedWorkshopRepository' => \DI\factory(function (ContainerInterface $c) {
        return WorkshopRepository::fromDataSource(WorkshopDataSource::createFromLocalPath(
            $c->get('stateFile')
        ));
    }),
    'appDir' => sprintf('%s/.php-school', getenv('HOME')),
    'stateFile' => function (ContainerInterface $c) {
        return new JsonFile(sprintf('%s/installed.json', $c->get('appDir')));
    },
    ManagerState::class => \DI\factory(function (ContainerInterface $c) {
        return new ManagerState(
            $c->get(Filesystem::class),
            $c->get('stateFile')
        );
    }),
    Filesystem::class => \DI\factory(function (ContainerInterface $c) {
        return new Filesystem($c->get(Local::class));
    }),
    Local::class => \DI\factory(function (ContainerInterface $c) {
        return new Local($c->get('appDir'));
    }),
];

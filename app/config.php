<?php

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Util\HttpDownloader;
use Composer\Util\RemoteFilesystem;
use PhpSchool\WorkshopManager\GitHubApi\Client;
use Humbug\SelfUpdate\Updater as PharUpdater;
use Psr\Container\ContainerInterface;
use PhpSchool\WorkshopManager\Application;
use PhpSchool\WorkshopManager\Command\InstallWorkshop;
use PhpSchool\WorkshopManager\Command\ListWorkshops;
use PhpSchool\WorkshopManager\Command\SearchWorkshops;
use PhpSchool\WorkshopManager\Command\SelfRollback;
use PhpSchool\WorkshopManager\Command\SelfUpdate;
use PhpSchool\WorkshopManager\Command\UninstallWorkshop;
use PhpSchool\WorkshopManager\Command\UpdateWorkshop;
use PhpSchool\WorkshopManager\Command\VerifyInstall;
use PhpSchool\WorkshopManager\ComposerInstaller;
use PhpSchool\WorkshopManager\ComposerInstallerFactory;
use PhpSchool\WorkshopManager\Downloader;
use PhpSchool\WorkshopManager\Filesystem;
use PhpSchool\WorkshopManager\Installer\Installer;
use PhpSchool\WorkshopManager\Installer\Uninstaller;
use PhpSchool\WorkshopManager\Installer\Updater;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PhpSchool\WorkshopManager\VersionChecker;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

return [
    Application::class => \DI\factory(function (ContainerInterface $c) {
        $application = new \PhpSchool\WorkshopManager\Application('PHP School workshop manager', '1.1.0', $c);
        $application->command('install workshopName', InstallWorkshop::class, ['add'])
            ->setDescription('Install a PHP School workshop.')
            ->setHelp(<<<'EOF'
This command requires a <comment>workshopName</comment> code as an argument:
  <info>workshop-manager %command.name% <comment>workshopName</comment></info>
Install a workshop with its package field, you can find this by doing a search like:
  <info>workshop-manager search php</info>
In this example you would just use <comment>learnyouphp</comment> as workshopName.
  <info>workshop-manager %command.name% <comment>learnyouphp</comment></info>
You can then get started on your <comment>workshop</comment> instantly by using its package name.
EOF
            );
        $application->command('uninstall workshopName', UninstallWorkshop::class, ['remove', 'delete'])
            ->setDescription('Uninstall a PHP School workshop.')
            ->setHelp(<<<'EOF'
This command requires a <comment>workshopName</comment> code as an argument:
  <info>workshop-manager %command.name% <comment>workshopName</comment></info>
Remove a workshop by its package name (Example using <comment>learnyouphp</comment> as workshopName).
  <info>workshop-manager %command.name% <comment>learnyouphp</comment></info>
EOF
            );
        $application->command('update workshopName', UpdateWorkshop::class)
            ->setDescription('update a PHP School workshop.')
            ->setHelp(<<<'EOF'
This command requires a <comment>workshopName</comment> code as an argument:
  <info>workshop-manager %command.name% <comment>workshopName</comment></info>
To update a workshop you already have installed (Example using <comment>learnyouphp</comment> as workshopName).
  <info>workshop-manager %command.name% <comment>learnyouphp</comment></info>
EOF
            );
        $application->command('search [workshopName]', SearchWorkshops::class, ['find'])
            ->setDescription('Search for a PHP School workshop.')
            ->setHelp(<<<'EOF'
This command requires part of workshop name as argument:
  <info>workshop-manager %command.name% <comment>workshopName</comment></info>
Quickly find available workshops by part of its name and get an instant indication if they're already installed.
  <info>workshop-manager %command.name% <comment>php</comment></info>
EOF
            );
        $application->command('installed', ListWorkshops::class, ['show'])
            ->setDescription('List installed PHP School workshops.')
            ->setHelp(<<<'EOF'
List the installed workshops, just so you know what you can get working on.
  <info>workshop-manager <comment>%command.name%</comment></info>
It will also let you know if you need to update any workshops that you already have installed.
EOF
            );

        if (extension_loaded('phar') && Phar::running()) {
            $application->command('self-update', SelfUpdate::class)
                ->setDescription('Update the workshop manager to the latest version.')
                ->setHelp(<<<'EOF'
Keeping the workshop manager up to date is just as important as updated the workshops themselves.
  <info>workshop-manager <comment>%command.name%</comment></info>
You can then continue using the workshop manager as you were before.
EOF
            );
            $application->command('rollback', SelfRollback::class)
                ->setDescription('Rollback the workshop manager to the previous version.')
                ->setHelp(<<<'EOF'
Something go horribly wrong after that <comment>self-update</comment>? No worries this command got your back.
  <info>workshop-manager <comment>%command.name%</comment></info>
You can then continue using the workshop manager as you were before.
EOF
            );
        }

        $application->command('verify', VerifyInstall::class, ['validate'])
            ->descriptions('Verify your installation is working correctly')->setHelp(<<<'EOF'
This command will help diagnose those issues and point you in the right direction.
  <info>workshop-manager <comment>%command.name%</comment></info>
You might need to verify your installation if your running into problems.
EOF
            );

        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->setName('PHP School Workshop Manager');
        $application->setVersion('1.1.0');

        return $application;
    }),
    SelfUpdate::class => \DI\factory(function (ContainerInterface $c) {
        return new SelfUpdate($c->get(PharUpdater::class));
    }),
    SelfRollback::class => \DI\factory(function (ContainerInterface $c) {
        return new SelfRollback($c->get(PharUpdater::class));
    }),
    PharUpdater::class => \DI\factory(function (ContainerInterface $c) {
        $updater = new PharUpdater(null, false);
        $strategy = $updater->getStrategy();
        $strategy->setPharUrl('https://php-school.github.io/workshop-manager/workshop-manager.phar');
        $strategy->setVersionUrl('https://php-school.github.io/workshop-manager/workshop-manager.phar.version');
        return $updater;
    }),
    InstallWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new InstallWorkshop(
            $c->get(Installer::class),
            $c->get(Linker::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(RemoteWorkshopRepository::class)
        );
    }),
    UninstallWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new UninstallWorkshop(
            $c->get(Uninstaller::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(Linker::class)
        );
    }),
    UpdateWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new UpdateWorkshop(
            $c->get(Updater::class)
        );
    }),
    SearchWorkshops::class => \DI\factory(function (ContainerInterface $c) {
        return new SearchWorkshops(
            $c->get(RemoteWorkshopRepository::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(OutputInterface::class)
        );
    }),
    ListWorkshops::class => \DI\factory(function (ContainerInterface $c) {
        return new ListWorkshops(
            $c->get(InstalledWorkshopRepository::class),
            $c->get(VersionChecker::class)
        );
    }),
    VerifyInstall::class => \DI\factory(function (ContainerInterface $c) {
        return new VerifyInstall(
            $c->get(InputInterface::class),
            $c->get(OutputInterface::class),
            $c->get('appDir')
        );
    }),
    Linker::class => \DI\factory(function (ContainerInterface $c) {
        return new Linker(
            $c->get(Filesystem::class),
            $c->get('appDir'),
            $c->get(OutputInterface::class)
        );
    }),
    Installer::class => \DI\factory(function (ContainerInterface $c) {
        return new Installer(
            $c->get(InstalledWorkshopRepository::class),
            $c->get(RemoteWorkshopRepository::class),
            $c->get(Linker::class),
            $c->get(Filesystem::class),
            $c->get('appDir'),
            $c->get(ComposerInstaller::class),
            $c->get(Client::class),
            $c->get(VersionChecker::class)
        );
    }),
    Uninstaller::class => \DI\factory(function (ContainerInterface $c) {
        return new Uninstaller(
            $c->get(InstalledWorkshopRepository::class),
            $c->get(Linker::class),
            $c->get(Filesystem::class),
            $c->get('appDir')
        );
    }),
    Updater::class => \DI\factory(function (ContainerInterface $c) {
        return new Updater(
            $c->get(Installer::class),
            $c->get(Uninstaller::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(VersionChecker::class)
        );
    }),
    VersionChecker::class => \DI\factory(function (ContainerInterface $c) {
        return new VersionChecker($c->get(Client::class));
    }),
    Client::class => \DI\factory(function (ContainerInterface $c) {
        return new Client;
    }),
    ComposerInstaller::class => function (ContainerInterface $c) {
        return new ComposerInstaller(
            $c->get(InputInterface::class),
            $c->get(OutputInterface::class),
            new Factory
        );
    },
    Factory::class => \DI\create(),
    InputInterface::class => \Di\factory(function () {
        return new \Symfony\Component\Console\Input\ArgvInput($_SERVER['argv']);
    }),
    OutputInterface::class => \DI\factory(function (ContainerInterface $c) {
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

        $output = new \Symfony\Component\Console\Output\ConsoleOutput($verbosity);
        $output->getFormatter()->setStyle('phps', new OutputFormatterStyle('magenta'));
        return $output;
    }),
    RemoteWorkshopRepository::class => \DI\factory(function (ContainerInterface $c) {
        return new RemoteWorkshopRepository(
            new JsonFile(
                'https://www.phpschool.io/workshops.json',
                new HttpDownloader(new NullIO(), new Composer\Config)
            )
        );
    }),
    InstalledWorkshopRepository::class => \DI\factory(function (ContainerInterface $c) {
        return new InstalledWorkshopRepository($c->get('stateFile'));
    }),
    'appDir' => sprintf('%s/.php-school', getenv('HOME')),
    'stateFile' => function (ContainerInterface $c) {
        $stateFile = new JsonFile(sprintf('%s/installed.json', $c->get('appDir')));

        if (!$stateFile->exists()) {
            $stateFile->write(['workshops' => []]);
        }

        return $stateFile;
    },
    ManagerState::class => \DI\factory(function (ContainerInterface $c) {
        return new ManagerState(
            $c->get(Filesystem::class),
            $c->get('stateFile')
        );
    }),
    Filesystem::class => \DI\factory(function (ContainerInterface $c) {
        return new Filesystem;
    }),
];

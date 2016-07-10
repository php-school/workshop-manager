<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class UninstallWorkshop
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class UninstallWorkshop
{
    /**
     * @var Uninstaller
     */
    private $uninstaller;
    
    /**
     * @var InstalledWorkshopRepository
     */
    private $workshopRepository;

    /**
     * @var Linker
     */
    private $linker;

    /**
     * @param Uninstaller $uninstaller
     * @param InstalledWorkshopRepository $installedRepository
     * @param Linker $linker
     */
    public function __construct(
        Uninstaller $uninstaller,
        InstalledWorkshopRepository $installedRepository,
        Linker $linker
    ) {
        $this->uninstaller        = $uninstaller;
        $this->workshopRepository = $installedRepository;
        $this->linker             = $linker;
    }

    /**
     * @param OutputInterface $output
     * @param string $workshopName
     * @param bool force
     *
     * @return void
     * @throws \RuntimeException
     */
    public function __invoke(OutputInterface $output, $workshopName, $force)
    {
        $output->writeln('');

        try {
            $workshop = $this->workshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(
                [
                    sprintf(
                        ' <fg=magenta> It doesn\'t look like "%s" is installed, did you spell it correctly? </>',
                        $workshopName
                    ),
                    ''
                ]
            );
            return;
        }

        if (!$this->linker->unlink($workshop, $force)) {
            return;
        }

        try {
            $this->uninstaller->uninstallWorkshop($workshop);
        } catch (IOException $e) {
            $output->writeln([
                '',
                sprintf(
                    ' <error> Failed to uninstall workshop "%s". Error: "%s" </error>',
                    $workshop->getName(),
                    $e->getMessage()
                ),
                ''
            ]);
            return;
        }

        $this->workshopRepository->remove($workshop);
        $this->workshopRepository->save();

        $output->writeln(sprintf(" <info>Successfully uninstalled \"%s\"</info>\n", $workshop->getName()));
    }
}

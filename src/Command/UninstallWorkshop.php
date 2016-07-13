<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\ManagerState;
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
     * @param Uninstaller $uninstaller
     */
    public function __construct(Uninstaller $uninstaller)
    {
        $this->uninstaller = $uninstaller;
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
            $this->uninstaller->uninstallWorkshop($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(
                sprintf(
                    " <fg=magenta> It doesn't look like \"%s\" is installed, did you spell it correctly?</>\n",
                    $workshopName
                )
            );
        } catch (IOException $e) {
            $output->writeln(
                sprintf(
                    " <error> Failed to uninstall workshop \"%s\". Error: \"%s\" </error>\n",
                    $workshopName,
                    $e->getMessage()
                )
            );
        }

        if (isset($e) && $output->isVerbose()) {
            throw $e;
        } elseif (isset($e)) {
            return;
        }

        $output->writeln(sprintf(" <info>Successfully uninstalled \"%s\"</info>\n", $workshopName));
    }
}

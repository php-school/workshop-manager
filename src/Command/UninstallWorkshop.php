<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Uninstaller;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class UninstallWorkshop
{
    /**
     * @var Uninstaller
     */
    private $uninstaller;

    public function __construct(Uninstaller $uninstaller)
    {
        $this->uninstaller = $uninstaller;
    }

    public function __invoke(OutputInterface $output, string $workshopName): void
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

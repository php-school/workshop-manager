<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class SearchWorkshops
{
    /**
     * @var RemoteWorkshopRepository
     */
    private $remoteWorkshopRepository;

    /**
     * @var InstalledWorkshopRepository
     */
    private $installedWorkshopRepository;

    /**
     * @param RemoteWorkshopRepository $remoteWorkshopRepository
     * @param InstalledWorkshopRepository $installedWorkshopRepository
     */
    public function __construct(
        RemoteWorkshopRepository $remoteWorkshopRepository,
        InstalledWorkshopRepository $installedWorkshopRepository
    ) {
        $this->remoteWorkshopRepository = $remoteWorkshopRepository;
        $this->installedWorkshopRepository = $installedWorkshopRepository;
    }

    /**
     * @param string $workshopName
     * @param OutputInterface $output
     *
     * @return void
     */
    public function __invoke(OutputInterface $output, $workshopName)
    {
        $output->writeln('');

        $workshops = $this->remoteWorkshopRepository->find($workshopName);

        if (empty($workshops)) {
            return $output->writeln(sprintf(" <info>No workshops found matching \"%s\"</info>\n", $workshopName));
        }

        $output->writeln(' <info>*** Matches ***</info>');
        $output->writeln('');

        $style = (new TableStyle())
            ->setHorizontalBorderChar('<fg=magenta>-</>')
            ->setVerticalBorderChar('<fg=magenta>|</>')
            ->setCrossingChar('<fg=magenta>+</>');

        (new Table($output))
            ->setHeaders(['Name', 'Description', 'Package', 'Installed?'])
            ->setRows(array_map(function (Workshop $workshop) {

                $installed = $this->installedWorkshopRepository->hasWorkshop($workshop->getName())
                    ? '<fg=green>    ✔</>'
                    : '<fg=red>    ✘</>';

                return [
                    $workshop->getDisplayName(),
                    wordwrap($workshop->getDescription(), 50),
                    $workshop->getName(),
                    $installed
                ];
            }, $workshops))
            ->setStyle($style)
            ->render();

        $output->writeln([
            '',
            sprintf('  You can install a workshop by typing: %s install workshop-name', $_SERVER['argv'][0]),
            '',
            sprintf('  Eg: <fg=magenta>%s install learnyouphp</>', $_SERVER['argv'][0]),
            ''
        ]);
    }
}

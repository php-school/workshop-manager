<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
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
     * @var OutputInterface
     */
    private $output;

    /**
     * @param RemoteWorkshopRepository $remoteWorkshopRepository
     * @param InstalledWorkshopRepository $installedWorkshopRepository
     * @param OutputInterface $output
     */
    public function __construct(
        RemoteWorkshopRepository $remoteWorkshopRepository,
        InstalledWorkshopRepository $installedWorkshopRepository,
        OutputInterface $output
    ) {
        $this->remoteWorkshopRepository = $remoteWorkshopRepository;
        $this->installedWorkshopRepository = $installedWorkshopRepository;
        $this->output = $output;
    }

    /**
     * @param string $workshopName
     *
     * @return void
     */
    public function __invoke($workshopName)
    {
        $this->output->writeln('');

        $workshops = $this->remoteWorkshopRepository->find($workshopName);

        if (empty($workshops)) {
            return $this->output->writeln(sprintf(" <info>No workshops found matching \"%s\"</info>\n", $workshopName));
        }

        $this->output->writeln(' <info>*** Matches ***</info>');
        $this->output->writeln('');

        $style = (new TableStyle())
            ->setHorizontalBorderChar('<phps>-</phps>')
            ->setVerticalBorderChar('<phps>|</phps>')
            ->setCrossingChar('<phps>+</phps>');

        (new Table($this->output))
            ->setHeaders(['Name', 'Description', 'Code', 'Type', 'Level', 'Installed?'])
            ->setRows(array_map(function (Workshop $workshop) {

                $installed = $this->installedWorkshopRepository->hasWorkshop($workshop->getCode())
                    ? '<fg=green>    ✔</>'
                    : '<fg=red>    ✘</>';

                return [
                    $workshop->getDisplayName(),
                    wordwrap($workshop->getDescription(), 50),
                    $workshop->getCode(),
                    ucfirst($workshop->getType()),
                    ucfirst($workshop->getLevel()),
                    $installed
                ];
            }, $workshops))
            ->setStyle($style)
            ->render();

        $this->output->writeln([
            '',
            sprintf('  You can install a workshop by typing: %s install workshop-code', $_SERVER['argv'][0]),
            '',
            sprintf('  Eg: <phps>%s install learnyouphp</phps>', $_SERVER['argv'][0]),
            ''
        ]);
    }
}

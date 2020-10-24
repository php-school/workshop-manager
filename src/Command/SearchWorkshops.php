<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function __construct(
        RemoteWorkshopRepository $remoteWorkshopRepository,
        InstalledWorkshopRepository $installedWorkshopRepository,
        OutputInterface $output
    ) {
        $this->remoteWorkshopRepository = $remoteWorkshopRepository;
        $this->installedWorkshopRepository = $installedWorkshopRepository;
        $this->output = $output;
    }

    public function __invoke(string $workshopName = null): void
    {
        $this->output->writeln('');

        $workshops = $workshopName
            ? $this->remoteWorkshopRepository->find($workshopName)
            : $this->remoteWorkshopRepository->all();

        if (empty($workshops) && $workshopName) {
            $this->output->writeln(sprintf(" <info>No workshops found matching \"%s\"</info>\n", $workshopName));
            return;
        }

        $this->output->writeln(' <info>*** Matches ***</info>');
        $this->output->writeln('');

        $style = (new TableStyle())
            ->setHorizontalBorderChars('<phps>-</phps>')
            ->setVerticalBorderChars('<phps>|</phps>')
            ->setDefaultCrossingChar('<phps>+</phps>');

        (new Table($this->output))
            ->setHeaders(['Name', 'Description', 'Code', 'Type', 'Installed?'])
            ->setRows(array_map(function (Workshop $workshop) {

                $installed = $this->installedWorkshopRepository->hasWorkshop($workshop->getCode())
                    ? '<fg=green>    ✔</>'
                    : '<fg=red>    ✘</>';

                return [
                    $workshop->getDisplayName(),
                    wordwrap($workshop->getDescription(), 50),
                    $workshop->getCode(),
                    ucfirst($workshop->getType()),
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

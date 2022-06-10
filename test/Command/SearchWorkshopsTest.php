<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Command\SearchWorkshops;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Yoast\PHPUnitPolyfills\Polyfills\AssertionRenames;

class SearchWorkshopsTest extends TestCase
{
    use AssertionRenames;

    /**
     * @var JsonFile
     */
    private $localJsonFile;

    /**
     * @var InstalledWorkshopRepository
     */
    private $localRepo;

    /**
     * @var RemoteWorkshopRepository
     */
    private $remoteRepo;

    /**
     * @var SearchWorkshops
     */
    private $command;

    /**
     * @var OutputInterface
     */
    private $output;

    public function setUp(): void
    {
        $this->localJsonFile = $this->createMock(JsonFile::class);
        $this->localJsonFile
            ->expects($this->once())
            ->method('read')
            ->willReturn(['workshops' => []]);

        $this->localRepo = new InstalledWorkshopRepository($this->localJsonFile);
        $this->remoteRepo = $this->createMock(RemoteWorkshopRepository::class);
        $this->output = new BufferedOutput();
        $this->output->getFormatter()->setStyle('phps', new OutputFormatterStyle('magenta'));
        $this->command = new SearchWorkshops($this->remoteRepo, $this->localRepo, $this->output);
    }

    public function testMessageIsPrintedIfNoResults(): void
    {
        $this->remoteRepo
            ->expects($this->once())
            ->method('find')
            ->with('php')
            ->willReturn([]);

        $this->command->__invoke('php');

        $output = $this->output->fetch();

        $this->assertMatchesRegularExpression(
            sprintf('/%s/', preg_quote('No workshops found matching "php"')),
            $output
        );
    }

    public function testInstalledWorkshopIsMarkedAsInstalled(): void
    {
        $workshop = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $installedWorkshop = InstalledWorkshop::fromWorkshop($workshop, '1.0.0');
        $this->localRepo->add($installedWorkshop);
        $this->remoteRepo
            ->expects($this->once())
            ->method('find')
            ->with('php')
            ->willReturn([$workshop]);

        $this->command->__invoke('php');
        $output = $this->output->fetch();

        $this->assertMatchesRegularExpression(
            '/learnyouphp\s+\|\sworkshop\s+\|\slearnyouphp\s+\|\sCore\s+\|\s+✔/',
            $output
        );
    }

    public function testNotInstalledWorkshopIsMarkedAsNotInstalled(): void
    {
        $workshop = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $this->remoteRepo
            ->expects($this->once())
            ->method('find')
            ->with('php')
            ->willReturn([$workshop]);

        $this->command->__invoke('php');
        $output = $this->output->fetch();

        $this->assertMatchesRegularExpression(
            '/learnyouphp\s+\|\sworkshop\s+\|\slearnyouphp\s+\|\sCore\s+\|\s+✘/',
            $output
        );
    }

    public function testSearchListsAllRemotesIfNoSearchTerm(): void
    {
        $workshop1 = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $workshop2 = new Workshop('learnyoucouchdb', 'learnyoucouchdb', 'aydin', 'repo', 'workshop', 'core');

        $this->remoteRepo
            ->expects($this->once())
            ->method('all')
            ->willReturn([$workshop1, $workshop2]);

        $this->command->__invoke(null);
        $output = $this->output->fetch();


        $expected  = '/learnyouphp\s+\|\sworkshop\s+\|\slearnyouphp\s+\|\sCore\s+\|\s+✘\s+|\s+';
        $expected  .= 'learnyoucouchdb\s+\|\sworkshop\s+\|\slearnyoucouchdb\s+\|\sCore\s+\|\s+✘\s+|\s+/';
        $this->assertMatchesRegularExpression($expected, $output);
    }
}

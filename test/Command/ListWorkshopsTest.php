<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Command\ListWorkshops;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\NoTaggedReleaseException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\VersionChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ListWorkshopsTest extends TestCase
{
    /**
     * @var JsonFile
     */
    private $localJsonFile;

    /**
     * @var InstalledWorkshopRepository
     */
    private $localRepo;

    /**
     * @var VersionChecker
     */
    private $versionChecker;

    /**
     * @var ListWorkshops
     */
    private $command;

    /**
     * @var BufferedOutput
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
        $this->versionChecker = $this->createMock(VersionChecker::class);
        $this->command = new ListWorkshops($this->localRepo, $this->versionChecker);
        $this->output = new BufferedOutput();
    }

    public function testMessageIsPrintedIfNoWorkshopsInstalled(): void
    {
        $this->command->__invoke($this->output);

        $output = $this->output->fetch();

        $this->assertMatchesRegularExpression('/There are currently no workshops installed/', $output);
    }


    public function testNewVersionIsShownIfThereIsOne(): void
    {
        $workshop = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $installedWorkshop = InstalledWorkshop::fromWorkshop($workshop, '1.0.0');
        $this->localRepo->add($installedWorkshop);

        $this->versionChecker
            ->expects($this->once())
            ->method('getLatestRelease')
            ->willReturn(new Release('2.0.0', 'AAAA'));

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();
        $this->assertMatchesRegularExpression(
            '/learnyouphp\s+\|\s+workshop\s+\|\s+learnyouphp\s+\|\sCore\s+\|\s+1\.0\.0\s+\|\s+Yes - 2\.0\.0/',
            $output
        );
    }

    public function testOutputWithNoNewVersion(): void
    {
        $workshop = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $installedWorkshop = InstalledWorkshop::fromWorkshop($workshop, '1.0.0');
        $this->localRepo->add($installedWorkshop);

        $this->versionChecker
            ->expects($this->once())
            ->method('getLatestRelease')
            ->willReturn(new Release('1.0.0', 'AAAA'));

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();
        $this->assertMatchesRegularExpression(
            '/learnyouphp\s+\|\s+workshop\s+\|\s+learnyouphp\s+\|\sCore\s+\|\s+1\.0\.0\s+\|\s+Nope!/',
            $output
        );
    }

    public function testOutputWhenWorkshopInstalledAsBranch(): void
    {
        $workshop = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $installedWorkshop = InstalledWorkshop::fromWorkshop($workshop, 'master');
        $this->localRepo->add($installedWorkshop);

        $this->versionChecker
            ->expects($this->once())
            ->method('getLatestRelease')
            ->willReturn(new Release('1.0.0', 'AAAA'));

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();
        $this->assertMatchesRegularExpression(
            '/learnyouphp\s+\|\s+workshop\s+\|\s+learnyouphp\s+\|\sCore\s+\|master|\s+Yes - 1\.0\.0/',
            $output
        );
    }

    public function testOutputWhenWorkshopInstalledAsBranchFromDifferentRepo(): void
    {
        $workshop = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $installedWorkshop = InstalledWorkshop::fromWorkshop(
            $workshop,
            'https://github.com/AydinHassan/php8-appreciate:master'
        );
        $this->localRepo->add($installedWorkshop);

        $this->versionChecker
            ->expects($this->once())
            ->method('getLatestRelease')
            ->willReturn(new Release('1.0.0', 'AAAA'));

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();
        $this->assertMatchesRegularExpression(
            '/learnyouphp\s+\|\s+workshop\s+\|\s+learnyouphp\s+\|\sCore\s+\|https:\/\/github\.com\/AydinHassan' .
            '\/php8\-appreciate:master|\s+Yes - 1\.0\.0/',
            $output
        );
    }

    public function testOutputWhenWorkshopInstalledAsBranchFromDifferentRepoAndNoTagsExist(): void
    {
        $workshop = new Workshop('learnyouphp', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $installedWorkshop = InstalledWorkshop::fromWorkshop(
            $workshop,
            'https://github.com/AydinHassan/php8-appreciate:master'
        );
        $this->localRepo->add($installedWorkshop);

        $this->versionChecker
            ->expects($this->once())
            ->method('getLatestRelease')
            ->willThrowException(NoTaggedReleaseException::fromWorkshop($workshop));

        $this->command->__invoke($this->output);

        $output = $this->output->fetch();
        $this->assertMatchesRegularExpression(
            '/learnyouphp\s+\|\s+workshop\s+\|\s+learnyouphp\s+\|\sCore\s+\|https:\/\/github\.com\/AydinHassan' .
            '\/php8\-appreciate:master|\s+No releases/',
            $output
        );
    }
}

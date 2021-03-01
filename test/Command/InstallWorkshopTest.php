<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use PhpSchool\WorkshopManager\Command\InstallWorkshop;
use PhpSchool\WorkshopManager\Entity\Branch;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Installer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class InstallWorkshopTest extends TestCase
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var InstallWorkshop
     */
    private $command;

    /**
     * @var OutputInterface
     */
    private $output;

    public function setUp(): void
    {
        $this->installer = $this->createMock(Installer::class);
        $this->command = new InstallWorkshop($this->installer);
        $this->output = $this->createMock(OutputInterface::class);
    }

    public function testWhenWorkshopIsAlreadyInstalled(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new WorkshopAlreadyInstalledException());

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <info>\"learnyouphp\" is already installed, you're ready to learn!</info>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenWorkshopDoesNotExistInRegistry(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new WorkshopNotFoundException());

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <fg=magenta> No workshops found matching \"learnyouphp\", did you spell it correctly? </>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenDownloadFails(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new DownloadFailureException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> There was a problem downloading the workshop. Error: \"Some error\"</error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenFailedToMove(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new FailedToMoveWorkshopException('/root/src', '/root/workshops/learnyouphp'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [
                    [
                        ' <error> There was a problem moving downloaded files for "learnyouphp"   </error>',
                        " Please check your file permissions for the following paths\n",
                        ' <info>/root</info>',
                        ' <info>/root/workshops</info>',
                    '',
                    ]
                ]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenComposerInstallFails(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new ComposerFailureException('Some error.'));

        $msg  = " <error> There was a problem installing dependencies for \"learnyouphp\". Some error.";
        $msg .= sprintf(
            " Try running in verbose mode to see more details: %s install -v </error>\n",
            $_SERVER['argv'][0]
        );
        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [''],
                [$msg]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testAnyOtherFailure(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new \Exception('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> An unknown error occurred: \"Some error\" </error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testExceptionIsThrownIfInVerboseMode(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new \Exception('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> An unknown error occurred: \"Some error\" </error>\n"]
            );

        $this->output
            ->expects($this->once())
            ->method('isVerbose')
            ->willReturn(true);

        $this->expectException(\Exception::class);

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testSuccess(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp');

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <info>Successfully installed \"learnyouphp\"</info>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testSuccessWithBranch(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', $this->callback(function ($branch) {
                return $branch instanceof Branch
                    && 'master' === $branch->getBranch()
                    && !$branch->isDifferentRepository();
            }));

        $this->output
            ->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <fg=magenta> Installing branches is reserved for testing purposes</>\n"],
                [" <info>Successfully installed \"learnyouphp\"</info>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp', 'master');
    }

    public function testSuccessWithBranchAndDifferentRepo(): void
    {
        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learnyouphp', $this->callback(function ($branch) {
                return $branch instanceof Branch
                    && 'master' === $branch->getBranch()
                    && $branch->isDifferentRepository()
                    && $branch->getGitHubOwner() === 'AydinHassan'
                    && $branch->getGitHubRepoName() === 'php8-appreciate';
            }));

        $this->output
            ->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <fg=magenta> Installing branches is reserved for testing purposes</>\n"],
                [" <info>Successfully installed \"learnyouphp\"</info>\n"]
            );

        $this->command->__invoke(
            $this->output,
            'learnyouphp',
            'master',
            'https://github.com/AydinHassan/php8-appreciate'
        );
    }
}

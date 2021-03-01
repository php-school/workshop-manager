<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Entity\Branch;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Installer;
use Symfony\Component\Console\Output\OutputInterface;

class InstallWorkshop
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * @param Installer $installer
     */
    public function __construct(Installer $installer)
    {
        $this->installer = $installer;
    }

    public function __invoke(
        OutputInterface $output,
        string $workshopName,
        string $branchName = null,
        string $repo = null
    ): int {
        $output->writeln('');

        if ($branchName) {
            $output->writeln(" <fg=magenta> Installing branches is reserved for testing purposes</>\n");
        }

        $branch = $branchName ? new Branch($branchName, $repo) : null;

        try {
            $this->installer->installWorkshop($workshopName, $branch);
        } catch (WorkshopAlreadyInstalledException $e) {
            $output->writeln(
                sprintf(" <info>\"%s\" is already installed, you're ready to learn!</info>\n", $workshopName)
            );
            return 1;
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(
                sprintf(
                    " <fg=magenta> No workshops found matching \"%s\", did you spell it correctly? </>\n",
                    $workshopName
                )
            );
            return 1;
        } catch (DownloadFailureException $e) {
            $output->writeln(
                sprintf(
                    " <error> There was a problem downloading the workshop. Error: \"%s\"</error>\n",
                    $e->getMessage()
                )
            );
        } catch (FailedToMoveWorkshopException $e) {
            $output->writeln([
                sprintf(' <error> There was a problem moving downloaded files for "%s"   </error>', $workshopName),
                " Please check your file permissions for the following paths\n",
                sprintf(' <info>%s</info>', dirname($e->getSrcPath())),
                sprintf(' <info>%s</info>', dirname($e->getDestPath())),
                ''
            ]);
        } catch (ComposerFailureException $e) {
            $message  = " <error> There was a problem installing dependencies for \"%s\".%s Try running in verbose";
            $message .= " mode to see more details: %s </error>\n";

            $output->writeln(
                sprintf(
                    $message,
                    $workshopName,
                    $e->getMessage() ? sprintf(' %s', $e->getMessage()) : '',
                    $this->getCommand()
                )
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(" <error> An unknown error occurred: \"%s\" </error>\n", $e->getMessage())
            );
        }

        if (isset($e) && $output->isVerbose()) {
            throw $e;
        } elseif (isset($e)) {
            return 1;
        }

        $output->writeln(sprintf(" <info>Successfully installed \"%s\"</info>\n", $workshopName));

        return 0;
    }

    private function getCommand(): string
    {
        return sprintf('%s install -v', $_SERVER['argv'][0]);
    }
}

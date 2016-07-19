<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\NoUpdateAvailableException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Updater;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UpdateWorkshop
{
    /**
     * @var Updater
     */
    private $updater;

    /**
     * @param Updater $updater
     */
    public function __construct(Updater $updater)
    {
        $this->updater = $updater;
    }

    /**
     * @param OutputInterface $output
     * @param string $workshopName
     * @param bool $force
     */
    public function __invoke(OutputInterface $output, $workshopName, $force)
    {
        $output->writeln('');

        try {
            $version = $this->updater->updateWorkshop($workshopName, $force);
        } catch (WorkshopNotFoundException $e) {
            return $output->writeln(
                sprintf(
                    " <fg=magenta> It doesn't look like \"%s\" is installed, did you spell it correctly?</>\n",
                    $workshopName
                )
            );
        } catch (NoUpdateAvailableException $e) {
            return $output->writeln(
                sprintf(" <fg=magenta> There are no updates available for workshop \"%s\".</>\n", $workshopName)
            );
        } catch (IOException $e) {
            $output->writeln(
                sprintf(
                    " <error> Failed to uninstall workshop \"%s\". Error: \"%s\" </error>\n",
                    $workshopName,
                    $e->getMessage()
                )
            );
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
            $output->writeln(
                sprintf(" <error> There was a problem installing dependencies for \"%s\" </error>\n", $workshopName)
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(" <error> An unknown error occurred: \"%s\" </error>\n", $e->getMessage())
            );
        }

        if (isset($e) && $output->isVerbose()) {
            throw $e;
        } elseif (isset($e)) {
            return;
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $output->writeln(
            sprintf(" <info>Successfully updated %s to version %s</info>\n", $workshopName, $version)
        );
    }
}

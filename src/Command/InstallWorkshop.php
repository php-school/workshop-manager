<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Installer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallWorkshop
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
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

    /**
     * @param OutputInterface $output
     * @param string $workshopName
     *
     * @return void
     */
    public function __invoke(OutputInterface $output, $workshopName)
    {
        $output->writeln('');

        try {
            $this->installer->installWorkshop($workshopName);
        } catch (WorkshopAlreadyInstalledException $e) {
            return $output->writeln(
                sprintf(" <info>\"%s\" is already installed, you're ready to learn!</info>\n", $workshopName)
            );
        } catch (WorkshopNotFoundException $e) {
            return $output->writeln(
                sprintf(
                    " <fg=magenta> No workshops found matching \"%s\", did you spell it correctly? </>\n",
                    $workshopName
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
            $message  = " <error> There was a problem installing dependencies for \"%s\". Try running in verbose";
            $message .= " mode to see the composer error: %s </error>\n";

            $output->writeln(sprintf($message, $workshopName, $this->getCommand()));
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

        $output->writeln(sprintf(" <info>Successfully installed \"%s\"</info>\n", $workshopName));
    }

    private function getCommand()
    {
        return sprintf('%s install -v', $_SERVER['argv'][0]);
    }
}

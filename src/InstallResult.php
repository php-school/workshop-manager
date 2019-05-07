<?php

namespace PhpSchool\WorkshopManager;

use Illuminate\Support\Collection;

class InstallResult
{
    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var string
     */
    private $output;

    /**
     * @var Collection
     */
    private $missingExtensions;

    /**
     * @param int $exitCode
     * @param string $output
     */
    public function __construct($exitCode, $output)
    {
        $this->exitCode = $exitCode;
        $this->output = $output;

        $this->checkForMissingExtensions();
    }

    private function checkForMissingExtensions()
    {
        $this->missingExtensions = collect(explode(PHP_EOL, $this->output))
            ->filter(function ($line) {
                return preg_match(
                    '/the requested PHP extension [a-z-A-Z-_]+ is missing from your system/',
                    $line
                );
            })
            ->map(function ($extError) {
                preg_match(
                    '/the requested PHP extension ([a-z-A-Z-_]+) is missing from your system/',
                    $extError,
                    $match
                );

                return trim($match[1]);
            })
            ->unique();
    }

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return bool
     */
    public function missingExtensions()
    {
        return !$this->missingExtensions->isEmpty();
    }

    /**
     * @return array
     */
    public function getMissingExtensions()
    {
        return $this->missingExtensions->all();
    }
}

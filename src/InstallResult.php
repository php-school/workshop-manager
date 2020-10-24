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
     * @var Collection<string>
     */
    private $missingExtensions;

    public function __construct(int $exitCode, string $output)
    {
        $this->exitCode = $exitCode;
        $this->output = $output;

        $this->checkForMissingExtensions();
    }

    private function checkForMissingExtensions(): void
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
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @return bool
     */
    public function missingExtensions(): bool
    {
        return !$this->missingExtensions->isEmpty();
    }

    /**
     * @return array<string>
     */
    public function getMissingExtensions(): array
    {
        return $this->missingExtensions->all();
    }
}

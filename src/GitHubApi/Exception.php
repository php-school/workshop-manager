<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager\GitHubApi;

use RuntimeException;

class Exception extends RuntimeException
{
    public static function apiLimitExceeded(int $limit): self
    {
        return new self(sprintf('You have reached GitHub hourly limit! Actual limit is: %d', $limit));
    }

    /**
     * @param array<string> $errors
     */
    public static function validationFailed(array $errors): self
    {
        return new self(
            count($errors) ? sprintf('Validation Failed: %s', implode(', ', $errors)) : 'Validation Failed',
            422
        );
    }

    /**
     * @param array<array{message?: string}> $errors
     */
    public static function failed(array $errors): self
    {
        return new self(
            implode(
                ', ',
                array_filter(
                    array_map(function (array $error) {
                        return $error['message'] ?? null;
                    }, $errors)
                )
            ),
            502
        );
    }
}

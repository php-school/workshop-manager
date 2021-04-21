<?php

namespace PhpSchool\WorkshopManager\Exception;

final class InvalidRepositoryUrlException extends \RuntimeException
{
    public static function fromUrl(string $url): self
    {
        $message  = "Repository URL: '$url' is invalid. Use GitHub repository URL, eg: ";
        $message .= "https://github.com/php-school/php8-appreciate";
        return new self($message);
    }
}

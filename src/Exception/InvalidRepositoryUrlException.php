<?php


namespace PhpSchool\WorkshopManager\Exception;

final class InvalidRepositoryUrlException extends \RuntimeException
{
    public static function fromUrl(string $url): self
    {
        return new self("Repository URL: '$url' is invalid. Use GitHub repository URL, eg: https://github.com/php-school/php8-appreciate");
    }
}
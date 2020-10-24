<?php

namespace PhpSchool\WorkshopManager;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class Filesystem extends SymfonyFilesystem
{
    /**
     * Execute a callback in a directory, callback is passed the
     * absolute path.
     */
    public function executeInPath(string $path, callable $callback): void
    {
        $currentPath = getcwd();

        if (!$this->exists($path)) {
            throw new IOException(sprintf('Path: "%s" does not exist.', $path));
        }

        chdir($path);
        $callback($path);
        chdir($currentPath);
    }

    public function isLink(string $path): bool
    {
        return is_link($path);
    }

    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }
}

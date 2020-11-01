<?php

declare(strict_types=1);

use PhpSchool\WorkshopManager\Util\Collection;

if (!function_exists('collect')) {
    /**
     * @template T
     * @param array<T> $items
     * @return Collection<T>
     */
    function collect(array $items): Collection
    {
        return new Collection($items);
    }
}

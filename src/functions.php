<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager;

use PhpSchool\WorkshopManager\Util\Collection;

/**
 * @template T
 * @param array<T> $items
 * @return Collection<T>
 */
function collect(array $items): Collection
{
    return new Collection($items);
}

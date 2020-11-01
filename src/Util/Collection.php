<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager\Util;

use PhpSchool\PhpWorkshop\Utils\ArrayObject;

/**
 * @template T
 */
final class Collection implements \Countable
{
    /**
     * @var array<T>
     */
    private $items;

    /**
     * @param array<T> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param callable $callback
     * @return self<T>
     */
    public function map(callable $callback): self
    {
        return new self(
            (array) array_combine(
                array_keys($this->items),
                array_map($callback, $this->items, array_keys($this->items))
            )
        );
    }

    /**
     * @param ?callable $callback
     * @return self<T>
     */
    public function filter(callable $callback = null): self
    {
        if (null === $callback) {
            return new self(array_filter($this->items));
        }

        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @return self<T>
     */
    public function unique(): self
    {
        return new self(array_unique($this->items));
    }

    /**
     * @return self<T>
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    /**
     * @return array<T>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * @param callable $f
     * @return self<T>
     */
    public function each(callable $f): self
    {
        array_walk($this->items, $f);

        return $this;
    }

    /**
     * @param callable $f
     * @param mixed|null $initial
     * @return mixed
     */
    public function reduce(callable $f, $initial = null)
    {
        return array_reduce($this->items, $f, $initial);
    }

    /**
     * @param callable $f
     * @return self<T>
     */
    public function sortBy(callable $f): self
    {
        $new = $this->items;
        usort($new, $f);
        return new self($new);
    }

    /**
     * @param array<T> $items
     * @return self<T>
     */
    public function diff(array $items): self
    {
        return new self(array_diff($this->items, $items));
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return T
     */
    public function first()
    {
        $first = array_shift($this->items);

        if ($first === null) {
            throw new \RuntimeException('Collection is empty');
        }

        return $first;
    }
}

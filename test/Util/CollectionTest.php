<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManagerTest\Util;

use PhpSchool\WorkshopManager\Util\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{

    public function testMap(): void
    {
        $data = new Collection(['first' => 'taylor', 'last' => 'otwell']);
        $data = $data->map(function ($item, $key) {
            return $key . '-' . strrev($item);
        });
        $this->assertEquals(
            ['first' => 'first-rolyat', 'last' => 'last-llewto'],
            $data->all()
        );
    }

    public function testFilter(): void
    {
        $c = new Collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertEquals([1 => ['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->all());

        $c = new Collection(['', 'Hello', '', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->filter()->values()->all());

        $c = new Collection(['id' => 1, 'first' => 'Hello', 'second' => 'World']);
        $this->assertEquals(['first' => 'Hello', 'second' => 'World'], $c->filter(function ($item, $key) {
            return $key != 'id';
        })->all());
    }

    public function testUnique(): void
    {
        $c = new Collection(['Hello', 'World', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->unique()->all());
    }

    public function testValues(): void
    {
        $c = new Collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertEquals([['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->values()->all());
    }
    public function testCollectionIsConstructed(): void
    {
        $data = new Collection(['foo']);
        $this->assertSame(['foo'], $data->all());

        $data = new Collection([2]);
        $this->assertSame([2], $data->all());

        $data = new Collection([false]);
        $this->assertSame([false], $data->all());

        $data = new Collection();
        $this->assertEmpty($data->all());
    }

    public function testEmptyCollectionIsEmpty(): void
    {
        $c = new Collection();

        $this->assertTrue($c->isEmpty());
    }

    public function testEmptyCollectionIsNotEmpty(): void
    {
        $c = new Collection(['foo', 'bar']);

        $this->assertFalse($c->isEmpty());
    }

    public function testEach(): void
    {
        $c = new Collection($original = [1, 2, 'foo' => 'bar', 'bam' => 'baz']);

        $result = [];
        $c->each(function ($item, $key) use (&$result) {
            $result[$key] = $item;
        });
        $this->assertEquals($original, $result);
    }

    public function testReduce(): void
    {
        $data = new Collection([1, 2, 3]);
        $this->assertEquals(6, $data->reduce(function ($carry, $element) {
            return $carry += $element;
        }));
    }

    public function testSortWithCallback(): void
    {
        $data = (new Collection([5, 3, 1, 2, 4]))->sortBy(function ($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        $this->assertEquals(range(1, 5), array_values($data->all()));
    }

    public function testDiffCollection(): void
    {
        $c = new Collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals(['id' => 1], $c->diff(['first_word' => 'Hello', 'last_word' => 'World'])->all());
    }

    public function testCountable(): void
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertCount(2, $c);
    }

    public function testFirstReturnsFirstItemInCollection()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertSame('foo', $c->first());
    }
}

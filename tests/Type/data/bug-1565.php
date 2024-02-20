<?php

namespace Bug1565;

use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

class TestCollection extends Collection
{
    public function map(callable $callback): static
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    public function toArray(): array
    {
        return $this->map(fn($block) => $block->toArray())->values()->all();
    }
}

function test(TestCollection $collection): void
{
    assertType('mixed', $collection->random());
    assertType('Bug1565\TestCollection', $collection->map(fn ($val) => 'string'));
}

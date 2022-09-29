<?php

declare(strict_types=1);

namespace Tests\Features\Types;

class TransStringType
{
    public function testTransString(): void
    {
        $this->doSomethingWithATransKey('trans.language-key');
    }

    /**
     * @phpstan-param trans-string $key
     *
     * @param  string  $key
     * @return void
     */
    private function doSomethingWithATransKey(string $key): void
    {
    }
}

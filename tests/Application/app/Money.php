<?php

namespace App;

class Money
{
    public function __construct(
        private int $amount = 100,
    ) {
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\User;
use Illuminate\Support\Carbon;

class ModelPropertyExtension
{
    /** @var User */
    private $user;

    public function testPropertyReturnType(): int
    {
        return $this->user->id;
    }

    public function testDateReturnType(): ?Carbon
    {
        return $this->user->created_at;
    }

    public function testWriteToProperty(): void
    {
        $this->user->created_at = 'test';
        $this->user->created_at = now();
        $this->user->created_at = null;
    }

    /** @return array<int, string> */
    public function testCast(): array
    {
        return $this->user->meta;
    }
}

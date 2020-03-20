<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\User;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Support\Carbon;

class ModelPropertyExtension
{
    /** @var User */
    private $user;

    public function testPropertyReturnType(): int
    {
        return $this->user->id;
    }

    /**
     * @return Carbon|BaseCarbon|null
     */
    public function testDateReturnType()
    {
        return $this->user->created_at;
    }

    public function testWriteToProperty(): void
    {
        $this->user->created_at = 'test';
        $this->user->created_at = now();
        $this->user->created_at = null;
        $this->user->created_at = BaseCarbon::now();
    }

    /** @return array<int, string> */
    public function testCast(): array
    {
        return $this->user->meta;
    }

    public function testKnownColumnNameWithUnknownType(): string
    {
        $this->user->unknown_column = 5;
        $this->user->unknown_column = 'foo';

        return $this->user->unknown_column;
    }
}

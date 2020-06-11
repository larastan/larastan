<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HigherOrderTapProxy;

class TapExtension
{
    public function testTapClosure(): User
    {
        return tap(new User, function (User $user): void {
            $user->name = 'Daan Raatjes';
            $user->save();
        });
    }

    /**
     * @return HigherOrderTapProxy<User>
     */
    public function testTapProxyReturnType(): HigherOrderTapProxy
    {
        return tap(new User);
    }

    public function testTapProxy(): User
    {
        return tap(new User)->update(['name' => 'Taylor Otwell']);
    }
}

/**
 * @property string $name
 */
class TestModel extends Model
{
    public function setName(string $value): self
    {
        $this->name = $value;

        return tap($this)->save();
    }

    public function setName2(string $value): self
    {
        $this->name = $value;

        return tap($this, function (self $user): void {
            $user->save();
        });
    }
}

<?php

namespace Bug2516;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class Stations
{
    /** @return Collection<int, array{id: int, name: string, email: string}> */
    public function all(): Collection
    {
        return User::query()
            ->get()
            ->map(fn ($user) => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]);
    }

    /** @return Collection<int, string> */
    public function names(): Collection
    {
        return User::query()->get()->mapWithKeys(fn ($user) => [$user->id => $user->name]);
    }

    /** @return Collection<int, string> */
    public function labels(): Collection
    {
        $labels = User::query()->get()->map(fn ($user) => 'unknown');

        $labels->push('other');

        return $labels;
    }

    /**
     * @param EloquentCollection<int, User> $users
     *
     * @return Collection<int, float>
     */
    public function sums(EloquentCollection $users): Collection
    {
        $sums = $users->mapWithKeys(fn ($user) => [$user->id => 0.]);

        foreach ($users as $user) {
            $sums[$user->id] += (float) $user->id;
        }

        return $sums;
    }
}

<?php

namespace ModelFactories;

use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function PHPStan\Testing\assertType;

function test(?int $foo): void {
    assertType('Database\Factories\UserFactory', User::factory());
    assertType('Database\Factories\UserFactory', User::factory()->new());
    assertType('App\User', User::factory()->createOne());
    assertType('App\User', User::factory()->createOneQuietly());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->createMany([]));
    assertType('App\User', User::factory()->makeOne());

    assertType('App\User', User::factory()->create());
    assertType('App\User', User::factory()->createQuietly());
    assertType('App\User', User::factory()->make());

    assertType('App\User', User::factory()->configure()->create());
    assertType('App\User', User::factory()->configure()->createQuietly());
    assertType('App\User', User::factory()->configure()->make());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->create());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->createQuietly());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->make());
    assertType('App\User', User::factory(2)->createOneQuietly());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count(1)->create());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count(1)->createQuietly());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count(1)->make());
    assertType('App\User', User::factory()->count(2)->createOneQuietly());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->times(1)->create());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->times(1)->createQuietly());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->times(1)->make());
    assertType('App\User', User::factory()->times(2)->createOneQuietly());

    assertType('App\User', User::factory(2)->count(null)->create());
    assertType('App\User', User::factory(2)->count(null)->createQuietly());
    assertType('App\User', User::factory(2)->count(null)->createOneQuietly());
    assertType('App\User', User::factory(2)->count(null)->make());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->times(3)->create());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->times(3)->createQuietly());
    assertType('App\User', User::factory(2)->times(3)->createOneQuietly());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->times(3)->make());

    assertType('App\User', User::factory(2)->state(['foo'])->count(null)->create());
    assertType('App\User', User::factory(2)->state(['foo'])->count(null)->createQuietly());
    assertType('App\User', User::factory(2)->state(['foo'])->count(null)->createOneQuietly());
    assertType('App\User', User::factory(2)->state(['foo'])->count(null)->make());

    assertType('Database\Factories\UserFactory', User::factory()->hasPosts());
    assertType('Database\Factories\UserFactory', User::factory()->hasPosts(3));
    assertType('Database\Factories\UserFactory', User::factory()->hasPosts(['active' => 0]));
    assertType('App\User', User::factory()->hasPosts(3, ['active' => 0])->createOne());
    assertType('Database\Factories\UserFactory', User::factory()->forParent());
    assertType('Database\Factories\UserFactory', User::factory()->forParent(['meta' => ['foo']]));

    assertType('Database\Factories\UserFactory', User::factory()->unverified());
    assertType('Database\Factories\UserFactory', User::factory()->afterMaking(fn (User $user) => $user));
    assertType('Database\Factories\UserFactory', User::factory()->afterCreating(fn (User $user) => $user));
    assertType('Database\Factories\Domain\Foo\UserFactory', \App\Domain\Foo\User::factory());

    assertType('Database\Factories\Post\PostFactory', Post::factory());
    assertType('Database\Factories\Post\PostFactory', Post::factory()->new());
    assertType('App\Post', Post::factory()->createOne());
    assertType('App\Post', Post::factory()->createOneQuietly());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Post>', Post::factory()->createMany([]));
    assertType('App\Post', Post::factory()->makeOne());
    assertType('Database\Factories\Post\PostFactory', Post::factory()->afterMaking(fn (Post $post) => $post));
    assertType('Database\Factories\Post\PostFactory', Post::factory()->afterCreating(fn (Post $post) => $post));

    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count($foo)->create());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count($foo)->createQuietly());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count($foo)->make());

    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory($foo)->create());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory($foo)->createQuietly());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory($foo)->make());

    assertType('Database\Factories\UserFactory', User::factory()->trashed());
    assertType('*ERROR*', Post::factory()->trashed());
}

class Comment extends Model
{
    use HasFactory;

    /** @return BelongsTo<User> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return CommentFactory */
    protected static function newFactory(): Factory
    {
        return CommentFactory::new();
    }

    private function test(): void
    {
        assertType('ModelFactories\CommentFactory', static::factory());
    }
}

/** @extends Factory<Comment> */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [];
    }

    public function foo(): static
    {
        return $this->state(['foo' => 'bar']);
    }

    private function test(User $user): void
    {
        $type = 'static(ModelFactories\CommentFactory)';
        assertType($type, $this->state([]));
        assertType($type, $this->afterMaking(fn ($c) => $c));
        assertType($type, $this->afterCreating(fn ($c) => $c));
        assertType($type, $this->foo());
        assertType($type, $this->recycle($user));
        assertType($type, $this->has(User::factory()));
        assertType($type, $this->hasAttached(User::factory()));
        assertType($type, $this->for(User::factory()));
        assertType($type, $this->for($user));
        assertType($type, $this->forUser());
        assertType($type, $this->hasUser());
    }
}

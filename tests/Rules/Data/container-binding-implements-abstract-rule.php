<?php

declare(strict_types=1);

namespace Bindings;

interface FooContract
{
    public function foo(): string;
}

class FooImplementation implements FooContract
{
    public function foo(): string
    {
        return 'lorem';
    }
}

class OtherFooImplementation implements FooContract
{
    public function foo(): string
    {
        return 'ipsum';
    }
}

abstract class AbstractBar
{
    abstract public function bar(): void;
}

class BarConcretion extends AbstractBar
{
    public function bar(): void
    {
        //
    }
}

class Buzz
{
    //
}

// bind - positive

app()->bind(Buzz::class);

app()->bind('fizz', Buzz::class);

app()->bind(FooContract::class, FooImplementation::class);

app()->bind(AbstractBar::class, fn () => new BarConcretion());

app()->bind(FooContract::class, function () {
    return rand() ? new OtherFooImplementation() : new FooImplementation();
});

// bind - negative

app()->bind(FooContract::class, Buzz::class);

app()->bind(AbstractBar::class, fn () => new FooImplementation());

app()->bind(AbstractBar::class, function () {
    return rand() ? new Buzz() : new BarConcretion();
});

// bindIf - positive

app()->bindIf(Buzz::class);

app()->bindIf('fizz', Buzz::class);

app()->bindIf(FooContract::class, FooImplementation::class);

app()->bindIf(AbstractBar::class, fn () => new BarConcretion());

app()->bindIf(FooContract::class, function () {
    return rand() ? new OtherFooImplementation() : new FooImplementation();
});

// bindIf - negative

app()->bindIf(FooContract::class, Buzz::class);

app()->bindIf(AbstractBar::class, fn () => new FooImplementation());

app()->bindIf(AbstractBar::class, function () {
    return rand() ? new Buzz() : new BarConcretion();
});

// singleton - positive

app()->singleton(Buzz::class);

app()->singleton('fizz', Buzz::class);

app()->singleton(FooContract::class, FooImplementation::class);

app()->singleton(AbstractBar::class, fn () => new BarConcretion());

app()->singleton(FooContract::class, function () {
    return rand() ? new OtherFooImplementation() : new FooImplementation();
});

// singleton - negative

app()->singleton(FooContract::class, Buzz::class);

app()->singleton(AbstractBar::class, fn () => new FooImplementation());

app()->singleton(AbstractBar::class, function () {
    return rand() ? new Buzz() : new BarConcretion();
});

// singletonIf - positive

app()->singletonIf(Buzz::class);

app()->singletonIf('fizz', Buzz::class);

app()->singletonIf(FooContract::class, FooImplementation::class);

app()->singletonIf(AbstractBar::class, fn () => new BarConcretion());

app()->singletonIf(FooContract::class, function () {
    return rand() ? new OtherFooImplementation() : new FooImplementation();
});

// singletonIf - negative

app()->singletonIf(FooContract::class, Buzz::class);

app()->singletonIf(AbstractBar::class, fn () => new FooImplementation());

app()->singletonIf(AbstractBar::class, function () {
    return rand() ? new Buzz() : new BarConcretion();
});

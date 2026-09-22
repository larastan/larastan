<?php

namespace Tests\Rules\Data;

use Exception;
use RuntimeException;

function correctCalls(bool $condition): void
{
    throw_if($condition, ThrowIfException::class, 'foo', 1);
    throw_unless($condition, ThrowIfException::class, 'foo', 1);
    throw_if($condition, ThrowIfExceptionWithOptionalParameter::class, 'foo');
    throw_if($condition, RuntimeException::class, 'message');
    throw_if($condition);
    throw_if($condition, 'Just a message');
}

function wrongParameterCounts(bool $condition): void
{
    throw_if($condition, ThrowIfException::class);
    throw_if($condition, ThrowIfException::class, 'foo');
    throw_if($condition, ThrowIfException::class, 'foo', 1, 'extra');
    throw_unless($condition, ThrowIfException::class, 'foo');
}

function wrongParameterTypes(bool $condition): void
{
    throw_if($condition, ThrowIfException::class, 1, 'foo');
    throw_unless($condition, ThrowIfException::class, true, false);
}

function namedArguments(bool $condition): void
{
    throw_if(condition: $condition, exception: ThrowIfException::class);
    throw_if(exception: ThrowIfException::class, condition: $condition);
    throw_if($condition, ThrowIfException::class, foo: 'foo', bar: 1);
    throw_if($condition, ThrowIfException::class, foo: 1, bar: 'foo');
    throw_if($condition, ThrowIfException::class, 'foo', baz: 1);
}

function notCheckedCalls(bool $condition, string $class, array $parameters): void
{
    throw_if($condition, static fn (int $foo): Exception => new Exception((string) $foo), 1);
    throw_if($condition, new ThrowIfException('foo', 1));
    throw_if($condition, $class, 'foo');
    throw_if($condition, ThrowIfException::class, ...$parameters);
    throw_if($condition, AbstractThrowIfException::class, 'foo');
}

function unknownClassAndNonThrowable(bool $condition): void
{
    throw_if($condition, 'Some failure happened', 'foo');
    throw_if($condition, ThrowIfNotAnException::class, 'foo');
}

// The exception is only constructed when the condition holds, so the condition
// narrows the arguments. Each case needs its own function: throw_if() also
// narrows everything after it, which would otherwise mask the next case.

function narrowedByNotIdenticalToNull(int|null $bar): void
{
    throw_if(null !== $bar, ThrowIfException::class, 'foo', $bar);
}

function narrowedByFalseyConditionOfThrowUnless(int|null $bar): void
{
    throw_unless($bar === null, ThrowIfException::class, 'foo', $bar);
}

function narrowedByTypeFunction(string|int $foo): void
{
    throw_if(is_string($foo), ThrowIfException::class, $foo, 1);
}

function narrowedToTheOffendingTypeOnly(int|null $bar): void
{
    throw_if(null === $bar, ThrowIfException::class, 'foo', $bar);
}

function conditionUnrelatedToTheArguments(int|null $bar, string $foo): void
{
    throw_if($foo === 'x', ThrowIfException::class, 'foo', $bar);
}

function constantConditions(): void
{
    throw_if(true, ThrowIfException::class, 'foo', 1);
    throw_if(false, ThrowIfException::class, 'foo', 1);
    throw_unless(true, ThrowIfException::class, 'foo', 1);
}

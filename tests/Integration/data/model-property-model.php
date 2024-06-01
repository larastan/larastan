<?php

namespace ModelPropertyModel;

use Illuminate\Database\Eloquent\Model;

class ModelPropertyOnModel extends \Illuminate\Database\Eloquent\Model
{
    public function foo(): void
    {
        $this->update([
            'foo' => 'bar',
        ]);
    }

    public function unionMethod(\App\User|\App\Account $model): void
    {
        $model->update([
            'foo' => 'bar',
        ]);
    }

    public function unionMethodWithPropertyOnlyInOne(\App\User|\App\Account $model): void
    {
        $model->update(['email_verified_at' => 'bar']);
    }

    public function unionMethodGreen(\App\User|\App\Account $model): void
    {
        $model->update(['id' => 5]);
    }
}

class ModelPropertyCustomMethods extends Model
{
    /**
     * @phpstan-param model-property<\App\User> $property
     *
     * @param  string  $property
     */
    public function foo(string $property): void
    {
        // Do something with property
    }

    public function bar(): void
    {
        $this->foo('email'); // 'email' exists on \App\User
        $this->foo('foo'); // 'foo' does not exist on \App\User
    }
}

class ModelPropertyCustomMethodsInNormalClass
{
    /**
     * @phpstan-param model-property<\App\User> $property
     *
     * @param  string  $property
     */
    public function foo(string $property): void
    {
        // Do something with property
    }

    public function bar(): void
    {
        $this->foo('email'); // 'email' exists on \App\User
        $this->foo('foo'); // 'foo' does not exist on \App\User
    }
}

/**
 * @param model-property<\App\User> $userModelProperty
 */
function acceptsUserProperty(string $userModelProperty): void
{
}

/**
 * @param model-property<\App\Account> $accountModelProperty
 */
function acceptsAccountProperty(string $accountModelProperty): void
{
}

function getString(): string
{
    return 'string';
}

/** @param model-property<\App\Account> $property*/
function getAccountProperty(string $property): void
{
    acceptsUserProperty($property);
}

acceptsUserProperty(getString());

/**
 * @param model-property<\App\Account|\App\User> $accountModelProperty
 */
function acceptsUserOrAccountProperty(string $accountModelProperty): void
{
}

acceptsUserOrAccountProperty('id'); // id exists in both models
acceptsUserOrAccountProperty('email'); // email exists only in User

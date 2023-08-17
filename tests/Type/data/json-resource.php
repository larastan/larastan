<?php

namespace JsonResource;

use App\User;
use App\UserResource;
use App\UserResourceInherited;

use function PHPStan\Testing\assertType;

function testResourceProperties()
{
    $user = User::find(1);
    $resource = new UserResource($user);

    assertType('int', $resource->id);
    assertType('string', $resource->email);
    assertType('array<string, mixed>', $resource->toArray());
}

function testResourceInheritance()
{
    $user = User::find(1);
    $resource = new UserResourceInherited($user);

    assertType(
        'array|Illuminate\Contracts\Support\Arrayable<(int|string), mixed>|JsonSerializable',
        $resource->toArray()
    );
}

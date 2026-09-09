<?php

namespace JsonResourceMixinAccessor;

use App\UserResource;

use function PHPStan\Testing\assertType;

function test(UserResource $resource): void
{
    // Computed Attribute without explicit generics should resolve through @mixin (type is mixed)
    assertType('mixed', $resource->newStyleAttribute);

    // Computed Attribute with explicit generics should resolve through @mixin with correct type
    assertType('int', $resource->stringButInt);
}

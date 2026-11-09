<?php

namespace ModelPropertiesLaravel132;

use App\MemberWithoutTimestampsAttribute;

use function PHPStan\Testing\assertType;

function test(MemberWithoutTimestampsAttribute $memberWithoutTimestampsAttribute): void
{
    assertType('string|null', $memberWithoutTimestampsAttribute->created_at);
}

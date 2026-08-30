<?php

namespace ModelPropertiesLaravel13;

use App\MemberWithoutTimestampsAttribute;
use App\MemberWithoutTimestampsTable;

use function PHPStan\Testing\assertType;

function test(
    MemberWithoutTimestampsAttribute $memberWithoutTimestampsAttribute,
    MemberWithoutTimestampsTable $memberWithoutTimestampsTable,
): void
{
    assertType('string|null', $memberWithoutTimestampsAttribute->created_at);
    assertType('string|null', $memberWithoutTimestampsTable->created_at);
}

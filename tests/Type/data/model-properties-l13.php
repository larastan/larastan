<?php

namespace ModelPropertiesLaravel13;

use App\MemberWithoutTimestampsTable;

use function PHPStan\Testing\assertType;

function test(MemberWithoutTimestampsTable $memberWithoutTimestampsTable): void
{
    assertType('string|null', $memberWithoutTimestampsTable->created_at);
}

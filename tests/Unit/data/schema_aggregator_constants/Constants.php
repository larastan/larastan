<?php

namespace Tests\Unit\SchemaAggregatorConstants;

class Constants
{
    public const USERS = 'users';

    public const AMOUNT = 'amount';

    private const PREFIX = 'us';

    /** @var string */
    public const DOCUMENTED_USERS = self::PREFIX . 'ers';
}

class InheritedConstants extends Constants
{
    private const PREFIX = 'other';
}

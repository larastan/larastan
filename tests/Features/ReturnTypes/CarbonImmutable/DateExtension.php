<?php

declare(strict_types=1);

namespace Features\ReturnTypes\CarbonImmutable;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

class DateExtension
{
    public function testCreate(): CarbonImmutable
    {
        return Date::create();
    }

    public function testCreateFromDate(): CarbonImmutable
    {
        return Date::createFromDate();
    }

    public function testCreateFromTime(): CarbonImmutable
    {
        return Date::createFromTime();
    }

    public function testCreateFromTimeString(): CarbonImmutable
    {
        return Date::createFromTimeString('12:00');
    }

    public function testCreateFromTimestamp(): CarbonImmutable
    {
        return Date::createFromTimestamp(0);
    }

    public function testCreateFromTimestampMs(): CarbonImmutable
    {
        return Date::createFromTimestampMs(0);
    }

    public function testCreateFromTimestampUTC(): CarbonImmutable
    {
        return Date::createFromTimestampUTC(0);
    }

    public function testCreateMidnightDate(): CarbonImmutable
    {
        return Date::createMidnightDate();
    }

    public function testFromSerialized(mixed $serialized): CarbonImmutable
    {
        return Date::fromSerialized($serialized);
    }

    public function testGetTestNow(): ?CarbonImmutable
    {
        return Date::getTestNow();
    }

    public function testInstance(mixed $date): CarbonImmutable
    {
        return Date::instance($date);
    }

    public function testMaxValue(): CarbonImmutable
    {
        return Date::maxValue();
    }

    public function testMinValue(): CarbonImmutable
    {
        return Date::minValue();
    }

    public function testNow(): CarbonImmutable
    {
        return Date::now();
    }

    public function testParse(): CarbonImmutable
    {
        return Date::parse();
    }

    public function testToday(): CarbonImmutable
    {
        return Date::today();
    }

    public function testTomorrow(): CarbonImmutable
    {
        return Date::tomorrow();
    }

    public function testYesterday(): CarbonImmutable
    {
        return Date::yesterday();
    }

    public function testCreateFromFormat(): CarbonImmutable|false
    {
        return Date::createFromFormat('Y-m-d', '2022-01-01');
    }

    public function testCreateSafe(): CarbonImmutable|false
    {
        return Date::createSafe();
    }

    public function testMake(): ?CarbonImmutable
    {
        return Date::make('today');
    }
}

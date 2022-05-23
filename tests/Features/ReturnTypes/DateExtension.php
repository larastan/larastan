<?php

declare(strict_types=1);

namespace Features\ReturnTypes;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

class DateExtension
{
    public function testCreate(): Carbon
    {
        return Date::create();
    }

    public function testCreateFromDate(): Carbon
    {
        return Date::createFromDate();
    }

    public function testCreateFromTime(): Carbon
    {
        return Date::createFromTime();
    }

    public function testCreateFromTimeString(): Carbon
    {
        return Date::createFromTimeString('12:00');
    }

    public function testCreateFromTimestamp(): Carbon
    {
        return Date::createFromTimestamp(0);
    }

    public function testCreateFromTimestampMs(): Carbon
    {
        return Date::createFromTimestampMs(0);
    }

    public function testCreateFromTimestampUTC(): Carbon
    {
        return Date::createFromTimestampUTC(0);
    }

    public function testCreateMidnightDate(): Carbon
    {
        return Date::createMidnightDate();
    }

    public function testFromSerialized(mixed $serialized): Carbon
    {
        return Date::fromSerialized($serialized);
    }

    public function testGetTestNow(): ?Carbon
    {
        return Date::getTestNow();
    }

    public function testInstance(mixed $date): Carbon
    {
        return Date::instance($date);
    }

    public function testMaxValue(): Carbon
    {
        return Date::maxValue();
    }

    public function testMinValue(): Carbon
    {
        return Date::minValue();
    }

    public function testNow(): Carbon
    {
        return Date::now();
    }

    public function testParse(): Carbon
    {
        return Date::parse();
    }

    public function testToday(): Carbon
    {
        return Date::today();
    }

    public function testTomorrow(): Carbon
    {
        return Date::tomorrow();
    }

    public function testYesterday(): Carbon
    {
        return Date::yesterday();
    }

    public function testCreateFromFormat(): Carbon|false
    {
        return Date::createFromFormat('Y-m-d', '2022-01-01');
    }

    public function testCreateSafe(): Carbon|false
    {
        return Date::createSafe();
    }

    public function testMake(): ?Carbon
    {
        return Date::make('today');
    }
}

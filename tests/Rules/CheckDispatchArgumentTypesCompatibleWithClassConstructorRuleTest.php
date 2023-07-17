<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Events\Dispatchable as EventDispatchable;
use NunoMaduro\Larastan\Rules\CheckDispatchArgumentTypesCompatibleWithClassConstructorRule;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<CheckDispatchArgumentTypesCompatibleWithClassConstructorRule> */
class CheckDispatchArgumentTypesCompatibleWithClassConstructorRuleTest extends RuleTestCase
{
    /** @var string */
    private $dispatchableClass;

    /**
     * @inheritDoc
     */
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();

        return new CheckDispatchArgumentTypesCompatibleWithClassConstructorRule(
            $broker,
            self::getContainer()->getByType(FunctionCallParametersCheck::class),
            $this->dispatchableClass
        );
    }

    public function testJobDispatch(): void
    {
        $this->dispatchableClass = Dispatchable::class;

        $this->analyse([__DIR__.'/data/job-dispatch.php'], [
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\data\LaravelJob::dispatch(), 2 required.', 5],
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\data\LaravelJob::dispatchSync(), 2 required.', 6],
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\data\LaravelJob::dispatchNow(), 2 required.', 7],
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\data\LaravelJob::dispatchAfterResponse(), 2 required.', 8],
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\data\LaravelJob::dispatch(), 2 required.', 10],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatch(), int given.', 11],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatch(), string given.', 11],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatch(), true given.', 12],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatch(), false given.', 12],
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\data\LaravelJob::dispatchSync(), 2 required.', 14],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchSync(), int given.', 15],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatchSync(), string given.', 15],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchSync(), true given.', 16],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatchSync(), false given.', 16],
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\data\LaravelJob::dispatchNow(), 2 required.', 18],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchNow(), int given.', 19],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatchNow(), string given.', 19],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchNow(), true given.', 20],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatchNow(), false given.', 20],
            ['Job class Tests\Rules\data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\data\LaravelJob::dispatchAfterResponse(), 2 required.', 22],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchAfterResponse(), int given.', 23],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatchAfterResponse(), string given.', 23],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchAfterResponse(), true given.', 24],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatchAfterResponse(), false given.', 24],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchIf(), int given.', 26],
            ['Parameter #2 $bar of job class Tests\Rules\data\LaravelJob constructor expects int in Tests\Rules\data\LaravelJob::dispatchIf(), string given.', 26],
            ['Parameter #1 $foo of job class Tests\Rules\data\LaravelJob constructor expects string in Tests\Rules\data\LaravelJob::dispatchUnless(), int given.', 27],
            ['Job class Tests\Rules\data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 30],
            ['Job class Tests\Rules\data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 32],
            ['Job class Tests\Rules\data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 34],
            ['Job class Tests\Rules\data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 36],
            ['Job class Tests\Rules\data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 39],
        ]);
    }

    public function testEventDispatch(): void
    {
        $this->dispatchableClass = EventDispatchable::class;

        $this->analyse([__DIR__.'/data/event-dispatch.php'], [
            ['Event class Tests\Rules\data\LaravelEvent constructor invoked with 0 parameters in Tests\Rules\data\LaravelEvent::dispatch(), 2 required.', 5],
            ['Event class Tests\Rules\data\LaravelEvent constructor invoked with 1 parameter in Tests\Rules\data\LaravelEvent::dispatch(), 2 required.', 7],
            ['Parameter #1 $foo of event class Tests\Rules\data\LaravelEvent constructor expects string in Tests\Rules\data\LaravelEvent::dispatch(), int given.', 8],
            ['Parameter #2 $bar of event class Tests\Rules\data\LaravelEvent constructor expects int in Tests\Rules\data\LaravelEvent::dispatch(), string given.', 8],
            ['Parameter #1 $foo of event class Tests\Rules\data\LaravelEvent constructor expects string in Tests\Rules\data\LaravelEvent::dispatch(), true given.', 9],
            ['Parameter #2 $bar of event class Tests\Rules\data\LaravelEvent constructor expects int in Tests\Rules\data\LaravelEvent::dispatch(), false given.', 9],
            ['Parameter #1 $foo of event class Tests\Rules\data\LaravelEvent constructor expects string in Tests\Rules\data\LaravelEvent::dispatchIf(), int given.', 11],
            ['Parameter #2 $bar of event class Tests\Rules\data\LaravelEvent constructor expects int in Tests\Rules\data\LaravelEvent::dispatchIf(), string given.', 11],
            ['Parameter #1 $foo of event class Tests\Rules\data\LaravelEvent constructor expects string in Tests\Rules\data\LaravelEvent::dispatchUnless(), int given.', 12],
            ['Event class Tests\Rules\data\LaravelEventWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 15],
            ['Event class Tests\Rules\data\LaravelEventWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 18],
        ]);
    }
}

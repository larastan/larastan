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

        $this->analyse([__DIR__.'/Data/job-dispatch.php'], [
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\Data\LaravelJob::dispatch(), 2 required.', 5],
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\Data\LaravelJob::dispatchSync(), 2 required.', 6],
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\Data\LaravelJob::dispatchNow(), 2 required.', 7],
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters in Tests\Rules\Data\LaravelJob::dispatchAfterResponse(), 2 required.', 8],
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\Data\LaravelJob::dispatch(), 2 required.', 10],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatch(), int given.', 11],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatch(), string given.', 11],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatch(), true given.', 12],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatch(), false given.', 12],
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\Data\LaravelJob::dispatchSync(), 2 required.', 14],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchSync(), int given.', 15],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatchSync(), string given.', 15],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchSync(), true given.', 16],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatchSync(), false given.', 16],
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\Data\LaravelJob::dispatchNow(), 2 required.', 18],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchNow(), int given.', 19],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatchNow(), string given.', 19],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchNow(), true given.', 20],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatchNow(), false given.', 20],
            ['Job class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter in Tests\Rules\Data\LaravelJob::dispatchAfterResponse(), 2 required.', 22],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchAfterResponse(), int given.', 23],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatchAfterResponse(), string given.', 23],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchAfterResponse(), true given.', 24],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatchAfterResponse(), false given.', 24],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchIf(), int given.', 26],
            ['Parameter #2 $bar of job class Tests\Rules\Data\LaravelJob constructor expects int in Tests\Rules\Data\LaravelJob::dispatchIf(), string given.', 26],
            ['Parameter #1 $foo of job class Tests\Rules\Data\LaravelJob constructor expects string in Tests\Rules\Data\LaravelJob::dispatchUnless(), int given.', 27],
            ['Job class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 30],
            ['Job class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 32],
            ['Job class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 34],
            ['Job class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 36],
            ['Job class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 39],
        ]);
    }

    public function testEventDispatch(): void
    {
        $this->dispatchableClass = EventDispatchable::class;

        $this->analyse([__DIR__.'/Data/event-dispatch.php'], [
            ['Event class Tests\Rules\Data\LaravelEvent constructor invoked with 0 parameters in Tests\Rules\Data\LaravelEvent::dispatch(), 2 required.', 5],
            ['Event class Tests\Rules\Data\LaravelEvent constructor invoked with 1 parameter in Tests\Rules\Data\LaravelEvent::dispatch(), 2 required.', 7],
            ['Parameter #1 $foo of event class Tests\Rules\Data\LaravelEvent constructor expects string in Tests\Rules\Data\LaravelEvent::dispatch(), int given.', 8],
            ['Parameter #2 $bar of event class Tests\Rules\Data\LaravelEvent constructor expects int in Tests\Rules\Data\LaravelEvent::dispatch(), string given.', 8],
            ['Parameter #1 $foo of event class Tests\Rules\Data\LaravelEvent constructor expects string in Tests\Rules\Data\LaravelEvent::dispatch(), true given.', 9],
            ['Parameter #2 $bar of event class Tests\Rules\Data\LaravelEvent constructor expects int in Tests\Rules\Data\LaravelEvent::dispatch(), false given.', 9],
            ['Parameter #1 $foo of event class Tests\Rules\Data\LaravelEvent constructor expects string in Tests\Rules\Data\LaravelEvent::dispatchIf(), int given.', 11],
            ['Parameter #2 $bar of event class Tests\Rules\Data\LaravelEvent constructor expects int in Tests\Rules\Data\LaravelEvent::dispatchIf(), string given.', 11],
            ['Parameter #1 $foo of event class Tests\Rules\Data\LaravelEvent constructor expects string in Tests\Rules\Data\LaravelEvent::dispatchUnless(), int given.', 12],
            ['Event class Tests\Rules\Data\LaravelEventWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 15],
            ['Event class Tests\Rules\Data\LaravelEventWithoutConstructor does not have a constructor and must be dispatched without any parameters.', 18],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/phpstan-rules.neon',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Rules;

use NunoMaduro\Larastan\Collectors\ContainerBindingsCollector;
use NunoMaduro\Larastan\Rules\ContainerBindingImplementsAbstractRule;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<ContainerBindingImplementsAbstractRule> */
class ContainerBindingImplementsAbstractRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $provider = $this->createReflectionProvider();
        $resolver = self::getContainer()->getByType(TypeStringResolver::class);

        return new ContainerBindingImplementsAbstractRule($provider, $resolver);
    }

    protected function getCollectors(): array
    {
        return [
            new ContainerBindingsCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Data/container-binding-implements-abstract-rule.php'], [
            // bind
            [
                'Container binding of type Bindings\Buzz does not implement Bindings\FooContract.',
                62,
            ],
            [
                'Container binding of type Bindings\FooImplementation does not implement Bindings\AbstractBar.',
                64,
            ],
            [
                'Container binding of type Bindings\BarConcretion|Bindings\Buzz does not implement Bindings\AbstractBar.',
                66,
            ],
            // bindIf
            [
                'Container binding of type Bindings\Buzz does not implement Bindings\FooContract.',
                86,
            ],
            [
                'Container binding of type Bindings\FooImplementation does not implement Bindings\AbstractBar.',
                88,
            ],
            [
                'Container binding of type Bindings\BarConcretion|Bindings\Buzz does not implement Bindings\AbstractBar.',
                90,
            ],
            // singleton
            [
                'Container binding of type Bindings\Buzz does not implement Bindings\FooContract.',
                110,
            ],
            [
                'Container binding of type Bindings\FooImplementation does not implement Bindings\AbstractBar.',
                112,
            ],
            [
                'Container binding of type Bindings\BarConcretion|Bindings\Buzz does not implement Bindings\AbstractBar.',
                114,
            ],
            // singletonIf
            [
                'Container binding of type Bindings\Buzz does not implement Bindings\FooContract.',
                134,
            ],
            [
                'Container binding of type Bindings\FooImplementation does not implement Bindings\AbstractBar.',
                136,
            ],
            [
                'Container binding of type Bindings\BarConcretion|Bindings\Buzz does not implement Bindings\AbstractBar.',
                138,
            ],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/phpstan-rules.neon',
        ];
    }
}

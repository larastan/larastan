<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Validation;

use Larastan\Larastan\Support\Validation\RuleTreeBuilder;
use Larastan\Larastan\Support\Validation\RuleTreeTypeResolver;
use Larastan\Larastan\Support\Validation\ValidationRule;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;

class RuleTreeTypeResolverTest extends PHPStanTestCase
{
    private RuleTreeTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = self::getContainer()->getByType(RuleTreeTypeResolver::class);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[Test]
    public function itProjectsRawAndValidatedShapesSeparately(): void
    {
        $tree = RuleTreeBuilder::build([
            'optional' => ValidationRuleFactory::make('string'),
            'author.name' => ValidationRuleFactory::make('required|string'),
            'author.nickname' => ValidationRuleFactory::make('string'),
            'prefs.theme' => ValidationRuleFactory::make('string'),
            'tags.*' => ValidationRuleFactory::make('string'),
            'items' => ValidationRuleFactory::make('required|list'),
            'items.*' => ValidationRuleFactory::make('string'),
            'users' => ValidationRuleFactory::make('required|array'),
            'users.*.email' => ValidationRuleFactory::make('required|string'),
            'copied' => ValidationRuleFactory::make('required'),
            'copied.name' => ValidationRuleFactory::make('required|string'),
            'profile' => ValidationRuleFactory::make('nullable|array'),
            'profile.name' => ValidationRuleFactory::make('required|string'),
            'payload' => new ValidationRule(
                type: new ArrayType(new MixedType(), new MixedType()),
                required: true,
                allowedKeys: [new ConstantStringType('name'), new ConstantStringType('count')],
            ),
            'payload.name' => ValidationRuleFactory::make('required|string'),
        ]);

        $rawProperties = $this->resolver->resolveRawProperties($tree);

        self::assertSame('string|null', $rawProperties['optional']->describe(VerbosityLevel::precise()));
        self::assertSame(
            'array{name: string, nickname?: string, ...}',
            $rawProperties['author']->describe(VerbosityLevel::precise()),
        );
        self::assertSame('mixed', $rawProperties['prefs']->describe(VerbosityLevel::precise()));
        self::assertSame('mixed', $rawProperties['tags']->describe(VerbosityLevel::precise()));
        self::assertSame('list<string>', $rawProperties['items']->describe(VerbosityLevel::precise()));
        self::assertSame(
            'array<array{email: string, ...}>',
            $rawProperties['users']->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            'array{name: string, ...}',
            $rawProperties['copied']->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            'array{name: string, ...}',
            $rawProperties['profile']->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            'array{name: string, count?: mixed}',
            $rawProperties['payload']->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            'array{optional?: string, author: array{name: string, nickname?: string}, prefs?: array{theme?: string}, '
                . 'tags?: array<string>, items?: array<int, string>, users?: array<array{email: string}>, '
                . 'copied: array{name: string, ...}, profile: array{name: string}, payload: array{name: string}}',
            $this->resolver->resolveValidatedData($tree)->describe(VerbosityLevel::precise()),
        );
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../../phpstan-tests.neon'];
    }
}

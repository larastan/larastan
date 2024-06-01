<?php

declare(strict_types=1);

namespace Tests\Type;

use App\Account;
use App\User;
use Larastan\Larastan\Properties\ModelPropertyHelper;
use Larastan\Larastan\Types\ModelProperty\GenericModelPropertyType;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

use function array_map;
use function array_reverse;
use function implode;
use function sprintf;

class GenericModelPropertyTypeTest extends PHPStanTestCase
{
    /**
     * @param class-string<Type> $expectedTypeClass
     *
     * @dataProvider dataUnion
     */
    public function testUnion(
        callable $types,
        string $expectedTypeClass,
        string $expectedTypeDescription,
    ): void {
        $types = $types(static::getContainer());

        $actualType            = TypeCombinator::union(...$types);
        $actualTypeDescription = $actualType->describe(VerbosityLevel::precise());

        $this->assertSame(
            $expectedTypeDescription,
            $actualTypeDescription,
            sprintf('union(%s)', implode(', ', array_map(
                static fn (Type $type): string => $type->describe(VerbosityLevel::precise()),
                $types,
            ))),
        );

        $this->assertInstanceOf($expectedTypeClass, $actualType);
    }

    /**
     * @param class-string<Type> $expectedTypeClass
     *
     * @dataProvider dataUnion
     */
    public function testUnionInversed(
        callable $types,
        string $expectedTypeClass,
        string $expectedTypeDescription,
    ): void {
        $types                 = array_reverse($types(static::getContainer()));
        $actualType            = TypeCombinator::union(...$types);
        $actualTypeDescription = $actualType->describe(VerbosityLevel::precise());

        $this->assertSame(
            $expectedTypeDescription,
            $actualTypeDescription,
            sprintf('union(%s)', implode(', ', array_map(
                static fn (Type $type): string => $type->describe(VerbosityLevel::precise()),
                $types,
            ))),
        );
        $this->assertInstanceOf($expectedTypeClass, $actualType);
    }

    /** @return iterable<array{callable(mixed): Type[], class-string<Type>, string}> */
    public static function dataUnion(): iterable
    {
        yield [
            static fn ($container) => [
                new GenericModelPropertyType(new ObjectType(User::class), $container->getByType(ModelPropertyHelper::class)),
                new GenericModelPropertyType(new ObjectType(Account::class), $container->getByType(ModelPropertyHelper::class)),
            ],
            UnionType::class,
            'model property of App\Account|model property of App\User',
        ];

        yield [
            static fn ($container) => [
                new GenericModelPropertyType(new ObjectType(User::class), $container->getByType(ModelPropertyHelper::class)),
                new StringType(),
            ],
            StringType::class,
            'string',
        ];

        yield [
            static fn ($container) => [
                new GenericModelPropertyType(new ObjectType(User::class), $container->getByType(ModelPropertyHelper::class)),
                new ConstantStringType('email'),
            ],
            GenericModelPropertyType::class,
            'model property of App\User',
        ];
    }

    /**
     * @param class-string<Type> $expectedTypeClass
     *
     * @dataProvider dataIntersect
     */
    public function testIntersect(
        callable $types,
        string $expectedTypeClass,
        string $expectedTypeDescription,
    ): void {
        $types = $types(static::getContainer());

        $actualType            = TypeCombinator::intersect(...$types);
        $actualTypeDescription = $actualType->describe(VerbosityLevel::precise());

        $this->assertSame($expectedTypeDescription, $actualTypeDescription);
        $this->assertInstanceOf($expectedTypeClass, $actualType);
    }

    /**
     * @param class-string<Type> $expectedTypeClass
     *
     * @dataProvider dataIntersect
     */
    public function testIntersectInversed(
        callable $types,
        string $expectedTypeClass,
        string $expectedTypeDescription,
    ): void {
        $actualType            = TypeCombinator::intersect(...array_reverse($types(static::getContainer())));
        $actualTypeDescription = $actualType->describe(VerbosityLevel::precise());

        $this->assertSame($expectedTypeDescription, $actualTypeDescription);
        $this->assertInstanceOf($expectedTypeClass, $actualType);
    }

    /** @return iterable<array{callable(mixed): Type[], class-string<Type>, string}> */
    public static function dataIntersect(): iterable
    {
        yield [
            static fn ($container) => [
                new GenericModelPropertyType(new ObjectType(User::class), $container->getByType(ModelPropertyHelper::class)),
                new GenericModelPropertyType(new ObjectType(Account::class), $container->getByType(ModelPropertyHelper::class)),
            ],
            NeverType::class,
            '*NEVER*',
        ];

        yield [
            static fn ($container) => [
                new GenericModelPropertyType(new ObjectType(User::class), $container->getByType(ModelPropertyHelper::class)),
                new StringType(),
            ],
            GenericModelPropertyType::class,
            'model property of App\User',
        ];

        yield [
            static fn ($container) => [
                new GenericModelPropertyType(new ObjectType(User::class), $container->getByType(ModelPropertyHelper::class)),
                new ConstantStringType('email'),
            ],
            ConstantStringType::class,
            "'email'",
        ];
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }
}

<?php

namespace Unit;

use NunoMaduro\Larastan\Properties\ModelCastHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;

class ModelCastHelperTest extends PHPStanTestCase
{
    /**
     * @var mixed|ReflectionProvider
     */
    private ReflectionProvider $reflectionProvider;

    protected function setUp(): void
    {
        $this->registerComparator(new PHPStanTypeComparator());

        $this->reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);
    }

    /**
     * @dataProvider testGetReadableTypeCases
     */
    public function testGetReadableType($cast, Type\Type $expected): void
    {
        $helper = new ModelCastHelper($this->reflectionProvider);

        $this->assertEquals($expected, $helper->getReadableType($cast, new Type\StringType()));
    }

    protected function testGetReadableTypeCases(): \Generator
    {
        yield ['int', new Type\IntegerType()];
        yield ['integer', new Type\IntegerType()];
        yield ['timestamp', new Type\IntegerType()];
        yield ['decimal', new Type\IntersectionType([new Type\StringType(), new Type\Accessory\AccessoryNumericStringType()])];
        yield ['string', new Type\StringType()];
        yield ['bool', new Type\BooleanType()];
        yield ['boolean', new Type\BooleanType()];
        yield ['object', new Type\ObjectType('stdClass')];
        yield ['array', new Type\ArrayType(new Type\BenevolentUnionType([new Type\IntegerType(), new Type\StringType()]), new Type\MixedType())];
        yield ['collection', new Type\ObjectType('Illuminate\Support\Collection')];
        yield ['date', new Type\ObjectType('Carbon\Carbon')];
        yield ['datetime', new Type\ObjectType('Carbon\Carbon')];
        yield ['immutable_date', new Type\ObjectType('Carbon\CarbonImmutable')];
        yield ['immutable_datetime', new Type\ObjectType('Carbon\CarbonImmutable')];
        yield ['Illuminate\Database\Eloquent\Casts\AsArrayObject', TypeCombinator::addNull(new Type\ObjectType('Illuminate\Database\Eloquent\Casts\ArrayObject'))];
        yield ['Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject', TypeCombinator::addNull(new Type\ObjectType('Illuminate\Database\Eloquent\Casts\ArrayObject'))];
        yield ['Illuminate\Database\Eloquent\Casts\AsCollection', TypeCombinator::addNull(new GenericObjectType('Illuminate\Support\Collection', [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]))];
        yield ['Illuminate\Database\Eloquent\Casts\AsEncryptedCollection', TypeCombinator::addNull(new GenericObjectType('Illuminate\Support\Collection', [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]))];
        yield ['Illuminate\Database\Eloquent\Casts\AsStringable', TypeCombinator::addNull(new Type\ObjectType('Illuminate\Support\Stringable'))];

        yield [
            'Illuminate\Database\Eloquent\Casts\AsEnumArrayObject:App\Casts\BackedEnumeration',
            TypeCombinator::addNull(
                new Type\Generic\GenericObjectType(
                    'Illuminate\Database\Eloquent\Casts\ArrayObject', [
                        new Type\BenevolentUnionType([new Type\IntegerType(), new Type\StringType()]),
                        new Type\ObjectType('App\Casts\BackedEnumeration'),
                    ]
                )
            )];

        yield [
            'Illuminate\Database\Eloquent\Casts\AsEnumCollection:App\Casts\BackedEnumeration',
            TypeCombinator::addNull(
                new Type\Generic\GenericObjectType(
                    'Illuminate\Support\Collection', [
                        new Type\BenevolentUnionType([new Type\IntegerType(), new Type\StringType()]),
                        new Type\ObjectType('App\Casts\BackedEnumeration'),
                    ]
                )
            )];
    }
}

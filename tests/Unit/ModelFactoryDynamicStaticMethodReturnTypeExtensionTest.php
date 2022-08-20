<?php

namespace Unit;

use NunoMaduro\Larastan\ReturnTypes\ModelFactoryDynamicStaticMethodReturnTypeExtension;
use NunoMaduro\Larastan\Types\Factory\ModelFactoryType;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class ModelFactoryDynamicStaticMethodReturnTypeExtensionTest extends PHPStanTestCase
{
    /**
     * @test
     */
    public function it_sets_the_is_single_model_flag_to_true_if_no_args_given(): void
    {
        $scope = $this->createMock(Scope::class);
        $extension = new ModelFactoryDynamicStaticMethodReturnTypeExtension;

        $type = $extension->getTypeFromStaticMethodCall(
            new DummyMethodReflection('factory'), // @phpstan-ignore-line
            new StaticCall(new Name('App\\User'), 'factory', []),
            $scope
        );

        $this->assertInstanceOf(ModelFactoryType::class, $type);
        $this->assertTrue((TrinaryLogic::createYes())->equals($type->isSingleModel()));
    }

    /**
     * @test
     * @dataProvider argumentProvider
     */
    public function it_sets_the_is_single_model_flag_correctly(Type $phpstanType, TrinaryLogic $expected): void
    {
        $scope = $this->createMock(Scope::class);
        $extension = new ModelFactoryDynamicStaticMethodReturnTypeExtension;

        $scope->method('getType')->willReturn($phpstanType);

        $type = $extension->getTypeFromStaticMethodCall(
            new DummyMethodReflection('factory'), // @phpstan-ignore-line
            new StaticCall(new Name('App\\User'), 'factory', [new Arg(new LNumber(1))]), // args doesn't matter
            $scope
        );

        $this->assertInstanceOf(ModelFactoryType::class, $type);
        $this->assertSame($expected->describe(), $type->isSingleModel()->describe());
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../phpstan-tests.neon'];
    }

    public function argumentProvider(): \Generator
    {
        yield [new ConstantIntegerType(1), TrinaryLogic::createNo()];
        yield [new ConstantIntegerType(0), TrinaryLogic::createNo()];
        yield [new ConstantIntegerType(2), TrinaryLogic::createNo()];
        yield [new ConstantIntegerType(-1), TrinaryLogic::createNo()];
        yield [new ConstantFloatType(1.2), TrinaryLogic::createNo()];
        yield [new IntegerType(), TrinaryLogic::createNo()];
        yield [new FloatType(), TrinaryLogic::createNo()];
        yield [new IntersectionType([
            new ConstantStringType('1'),
            new AccessoryNumericStringType(),
        ]), TrinaryLogic::createNo()];
        yield [new UnionType([new FloatType(), new ConstantIntegerType(1)]), TrinaryLogic::createNo()];

        yield [new NullType(), TrinaryLogic::createYes()];
        yield [new ObjectType('App\\User'), TrinaryLogic::createYes()];
        yield [new ConstantStringType('foo'), TrinaryLogic::createYes()];

        yield [new MixedType(), TrinaryLogic::createMaybe()];
        yield [new UnionType([new NullType(), new ConstantIntegerType(1)]), TrinaryLogic::createMaybe()];
        yield [new UnionType([new ArrayType(new IntegerType(), new IntegerType()), new IntegerType()]), TrinaryLogic::createMaybe()];
    }
}

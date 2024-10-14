<?php

declare(strict_types=1);

namespace Unit;

use App\User;
use Generator;
use Larastan\Larastan\ReturnTypes\ModelFactoryDynamicStaticMethodReturnTypeExtension;
use Larastan\Larastan\Types\Factory\ModelFactoryType;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
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
    private ReflectionProvider $reflectionProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->reflectionProvider = $this->createReflectionProvider();
    }

    /** @test */
    public function it_sets_the_is_single_model_flag_to_true_if_no_args_given(): void
    {
        $class = new Name(User::class);

        $scope = $this->createMock(Scope::class);
        $scope->method('resolveName')->with($class)->willReturn(User::class);

        $extension = new ModelFactoryDynamicStaticMethodReturnTypeExtension(
            $this->createReflectionProvider(),
        );

        $type = $extension->getTypeFromStaticMethodCall(
            $this->reflectionProvider->getClass(User::class)->getNativeMethod('factory'),
            new StaticCall($class, 'factory', []),
            $scope,
        );

        $this->assertInstanceOf(ModelFactoryType::class, $type);
        $this->assertTrue(TrinaryLogic::createYes()->equals($type->isSingleModel()));
    }

    /**
     * @test
     * @dataProvider argumentProvider
     */
    public function it_sets_the_is_single_model_flag_correctly(Type $phpstanType, TrinaryLogic $expected): void
    {
        $class = new Name(User::class);

        $scope = $this->createMock(Scope::class);
        $scope->method('resolveName')->with($class)->willReturn(User::class);
        $scope->method('getType')->willReturn($phpstanType);

        $extension = new ModelFactoryDynamicStaticMethodReturnTypeExtension(
            $this->createReflectionProvider(),
        );

        $type = $extension->getTypeFromStaticMethodCall(
            $this->reflectionProvider->getClass(User::class)->getNativeMethod('factory'),
            new StaticCall($class, 'factory', [new Arg(new LNumber(1))]), // args doesn't matter
            $scope,
        );

        $this->assertInstanceOf(ModelFactoryType::class, $type);
        $this->assertSame($expected->describe(), $type->isSingleModel()->describe());
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }

    public static function argumentProvider(): Generator
    {
        yield [new ConstantIntegerType(1), TrinaryLogic::createNo()];
        yield [new ConstantIntegerType(0), TrinaryLogic::createNo()];
        yield [new ConstantIntegerType(2), TrinaryLogic::createNo()];
        yield [new ConstantIntegerType(-1), TrinaryLogic::createNo()];
        yield [new ConstantFloatType(1.2), TrinaryLogic::createNo()];
        yield [new IntegerType(), TrinaryLogic::createNo()];
        yield [new FloatType(), TrinaryLogic::createNo()];
        yield [
            new IntersectionType([
                new ConstantStringType('1'),
                new AccessoryNumericStringType(),
            ]),
            TrinaryLogic::createNo(),
        ];

        yield [new UnionType([new FloatType(), new ConstantIntegerType(1)]), TrinaryLogic::createNo()];

        yield [new NullType(), TrinaryLogic::createYes()];
        yield [new ObjectType('App\\User'), TrinaryLogic::createYes()];
        yield [new ConstantStringType('foo'), TrinaryLogic::createYes()];

        yield [new MixedType(), TrinaryLogic::createMaybe()];
        yield [new UnionType([new NullType(), new ConstantIntegerType(1)]), TrinaryLogic::createMaybe()];
        yield [new UnionType([new ArrayType(new IntegerType(), new IntegerType()), new IntegerType()]), TrinaryLogic::createMaybe()];
    }
}

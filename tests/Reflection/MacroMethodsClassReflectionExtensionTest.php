<?php

namespace Reflection;

use Generator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use NunoMaduro\Larastan\Methods\MacroMethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ClosureTypeFactory;
use PHPStan\Type\ObjectType;

class MacroMethodsClassReflectionExtensionTest extends PHPStanTestCase
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var MacroMethodsClassReflectionExtension
     */
    private $reflectionExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflectionProvider = $this->createReflectionProvider();
        $this->reflectionExtension = new MacroMethodsClassReflectionExtension($this->reflectionProvider, self::getContainer()->getByType(ClosureTypeFactory::class));
    }

    /**
     * @test
     *
     * @dataProvider methodAndClassProvider
     */
    public function it_can_find_macros_on_a_class(string $class, string $methodName)
    {
        $requestClass = $this->reflectionProvider->getClass($class);

        $this->assertTrue($this->reflectionExtension->hasMethod($requestClass, $methodName));
    }

    /**
     * @test
     *
     * @dataProvider methodAndThrowTypeProvider
     */
    public function it_can_set_throw_type_for_macros(string $class, string $methodName, string $exceptionClass)
    {
        $requestClass = $this->reflectionProvider->getClass($class);

        $this->assertTrue($this->reflectionExtension->hasMethod($requestClass, $methodName));

        $method = $this->reflectionExtension->getMethod($requestClass, $methodName);

        $this->assertNotNull($method->getThrowType());
        $this->assertInstanceOf(ObjectType::class, $method->getThrowType());
        $this->assertSame($exceptionClass, $method->getThrowType()->getClassName());
    }

    public function methodAndClassProvider(): Generator
    {
        yield [Request::class, 'validate'];
        yield [Request::class, 'validateWithBag'];
        yield [Request::class, 'hasValidSignature'];
        yield [Request::class, 'hasValidRelativeSignature'];
    }

    public function methodAndThrowTypeProvider(): Generator
    {
        yield [Request::class, 'validate', ValidationException::class];
        yield [Request::class, 'validateWithBag', ValidationException::class];
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../extension.neon'];
    }
}

<?php

namespace Reflection;

use Generator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use NunoMaduro\Larastan\Methods\Extension;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;

class MacroMethodsClassReflectionExtensionTest extends PHPStanTestCase
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var Extension
     */
    private $reflectionExtension;

    /** @var string */
    private $laravelVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflectionProvider = $this->createReflectionProvider();
        $this->reflectionExtension = new Extension(
            self::getContainer()->getByType(PhpMethodReflectionFactory::class),
            $this->reflectionProvider
        );
        $this->laravelVersion = LARAVEL_VERSION;
    }

    /**
     * @test
     *
     * @dataProvider methodAndClassProvider
     */
    public function it_can_find_macros_on_a_class(string $class, string $methodName, string $laravelVersion)
    {
        if ($laravelVersion !== '' && version_compare($this->laravelVersion, $laravelVersion, '<')) {
            $this->markTestSkipped('This test requires Laravel 8.0 or higher.');
        }

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
        yield [Request::class, 'validate', ''];
        yield [Request::class, 'validateWithBag', ''];
        yield [Request::class, 'hasValidSignature', ''];
        yield [Request::class, 'hasValidRelativeSignature', '8.0'];
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

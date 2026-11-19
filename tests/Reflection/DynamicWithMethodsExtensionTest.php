<?php

declare(strict_types=1);

namespace Reflection;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\View\View;
use Larastan\Larastan\Methods\DynamicWithMethodsExtension;
use Larastan\Larastan\Support\MacroHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DynamicWithMethodsExtensionTest extends PHPStanTestCase
{
    private ReflectionProvider $reflectionProvider;

    private DynamicWithMethodsExtension $reflectionExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflectionProvider  = $this->createReflectionProvider();
        $this->reflectionExtension = new DynamicWithMethodsExtension(self::getContainer()->getByType(MacroHelper::class));
    }

    /** @param class-string $className */
    #[Test]
    #[DataProvider('greenMethodProvider')]
    public function it_will_find_methods_starting_with_with(string $className, string $methodName): void
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $this->assertTrue($this->reflectionExtension->hasMethod($classReflection, $methodName));
    }

    /** @param class-string $className */
    #[Test]
    #[DataProvider('redMethodProvider')]
    public function it_will_not_find_methods(string $className, string $methodName): void
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $this->assertFalse($this->reflectionExtension->hasMethod($classReflection, $methodName));
    }

    /** @param class-string $className */
    #[Test]
    #[DataProvider('greenMethodProvider')]
    public function it_will_have_correct_method_reflection(string $className, string $methodName): void
    {
        $classReflection    = $this->reflectionProvider->getClass($className);
        $methodReflection   = $this->reflectionExtension->getMethod($classReflection, $methodName);
        $parametersAcceptor = $methodReflection->getVariants()[0];

        $this->assertSame($methodName, $methodReflection->getName());
        $this->assertSame($classReflection, $methodReflection->getDeclaringClass());
        $this->assertFalse($methodReflection->isStatic());
        $this->assertFalse($methodReflection->isPrivate());
        $this->assertTrue($methodReflection->isPublic());
        $this->assertCount(1, $parametersAcceptor->getParameters());
        $this->assertSame('mixed', $parametersAcceptor->getParameters()[0]->getType()->describe(VerbosityLevel::value()));
        $this->assertSame($className, $parametersAcceptor->getReturnType()->describe(VerbosityLevel::value()));
    }

    /** @return iterable<array{class-string, string}> */
    public static function greenMethodProvider(): iterable
    {
        foreach ([RedirectResponse::class, View::class, Mailable::class] as $className) {
            yield [$className, 'withFoo'];
            yield [$className, 'withFooAndBar'];
        }
    }

    /** @return iterable<array{class-string, string}> */
    public static function redMethodProvider(): iterable
    {
        foreach ([RedirectResponse::class, View::class, Mailable::class] as $className) {
            yield [$className, 'non-existent'];
            yield [$className, 'aWith'];
            yield [$className, 'WithFoo'];
        }

        yield [Request::class, 'withFoo'];
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../Rules/phpstan-rules.neon'];
    }
}

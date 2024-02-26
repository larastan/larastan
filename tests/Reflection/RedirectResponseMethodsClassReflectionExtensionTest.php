<?php

declare(strict_types=1);

namespace Reflection;

use Illuminate\Http\RedirectResponse;
use Larastan\Larastan\Methods\RedirectResponseMethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;

class RedirectResponseMethodsClassReflectionExtensionTest extends PHPStanTestCase
{
    private ReflectionProvider $reflectionProvider;

    private RedirectResponseMethodsClassReflectionExtension $reflectionExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflectionProvider  = $this->createReflectionProvider();
        $this->reflectionExtension = new RedirectResponseMethodsClassReflectionExtension();
    }

    /**
     * @test
     * @dataProvider greenMethodProvider
     */
    public function it_will_find_methods_starting_with_with(string $methodName): void
    {
        $requestClass = $this->reflectionProvider->getClass(RedirectResponse::class);

        $this->assertTrue($this->reflectionExtension->hasMethod($requestClass, $methodName));
    }

    /**
     * @test
     * @dataProvider redMethodProvider
     */
    public function it_will_not_find_methods(string $methodName): void
    {
        $requestClass = $this->reflectionProvider->getClass(RedirectResponse::class);

        $this->assertFalse($this->reflectionExtension->hasMethod($requestClass, $methodName));
    }

    /**
     * @test
     * @dataProvider greenMethodProvider
     */
    public function it_will_have_correct_method_reflection(string $methodName): void
    {
        $requestClass       = $this->reflectionProvider->getClass(RedirectResponse::class);
        $methodReflection   = $this->reflectionExtension->getMethod($requestClass, $methodName);
        $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        $this->assertSame($methodName, $methodReflection->getName());
        $this->assertSame($requestClass, $methodReflection->getDeclaringClass());
        $this->assertFalse($methodReflection->isStatic());
        $this->assertFalse($methodReflection->isPrivate());
        $this->assertTrue($methodReflection->isPublic());
        $this->assertCount(1, $parametersAcceptor->getParameters());
        $this->assertSame('mixed', $parametersAcceptor->getParameters()[0]->getType()->describe(VerbosityLevel::value()));
        $this->assertSame(RedirectResponse::class, $parametersAcceptor->getReturnType()->describe(VerbosityLevel::value()));
    }

    /** @return iterable<mixed> */
    public static function greenMethodProvider(): iterable
    {
        yield ['withFoo'];
        yield ['withFooAndBar'];
    }

    /** @return iterable<mixed> */
    public static function redMethodProvider(): iterable
    {
        yield ['non-existent'];
        yield ['aWith'];
        yield ['WithFoo'];
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../Rules/phpstan-rules.neon'];
    }
}

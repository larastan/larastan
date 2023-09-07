<?php

namespace Reflection;

use Illuminate\Http\RedirectResponse;
use NunoMaduro\Larastan\Methods\RedirectResponseMethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;

class RedirectResponseMethodsClassReflectionExtensionTest extends PHPStanTestCase
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var RedirectResponseMethodsClassReflectionExtension
     */
    private $reflectionExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflectionProvider = $this->createReflectionProvider();
        $this->reflectionExtension = new RedirectResponseMethodsClassReflectionExtension();
    }

    /**
     * @test
     *
     * @dataProvider greenMethodProvider
     */
    public function it_will_find_methods_starting_with_with(string $methodName)
    {
        $requestClass = $this->reflectionProvider->getClass(RedirectResponse::class);

        $this->assertTrue($this->reflectionExtension->hasMethod($requestClass, $methodName));
    }

    /**
     * @test
     *
     * @dataProvider redMethodProvider
     */
    public function it_will_not_find_methods(string $methodName)
    {
        $requestClass = $this->reflectionProvider->getClass(RedirectResponse::class);

        $this->assertFalse($this->reflectionExtension->hasMethod($requestClass, $methodName));
    }

    /**
     * @test
     *
     * @dataProvider greenMethodProvider
     */
    public function it_will_have_correct_method_reflection(string $methodName)
    {
        $requestClass = $this->reflectionProvider->getClass(RedirectResponse::class);
        $methodReflection = $this->reflectionExtension->getMethod($requestClass, $methodName);
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
    public function greenMethodProvider(): iterable
    {
        yield ['withFoo'];
        yield ['withFooAndBar'];
    }

    /** @return iterable<mixed> */
    public function redMethodProvider(): iterable
    {
        yield ['non-existent'];
        yield ['aWith'];
        yield ['WithFoo'];
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../Rules/phpstan-rules.neon'];
    }
}

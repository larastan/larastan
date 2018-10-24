<?php

namespace Tests\ReturnTypes\Helpers;

use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Testing\TestCase;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Reflection\FunctionReflection;
use PHPUnit\Framework\MockObject\MockObject;
use NunoMaduro\Larastan\ReturnTypes\Helpers\UrlExtension;

class UrlExtensionTest extends TestCase
{
    public function testIsFunctionSupported()
    {
        $extension = new UrlExtension();

        $functionReflection = $this->mockFunctionReflection();
        $functionReflection
            ->expects($this->once())
            ->method('getName')
            ->willReturn('url');

        $this->assertTrue($extension->isFunctionSupported($functionReflection));
    }

    public function testGetTypeFromFunctionCallReturnsUrlGenerator()
    {
        $extension = new UrlExtension();

        $actualType = $extension->getTypeFromFunctionCall(
            $this->mockFunctionReflection(),
            $this->mockFunctionCall(), // Not passing any arguments here
            $this->mockScope()
        );

        $this->assertInstanceOf(ObjectType::class, $actualType);
        $this->assertEquals(\Illuminate\Contracts\Routing\UrlGenerator::class, $actualType->getClassName());
    }

    public function testGetTypeFromFunctionCallReturnsStringGivenNonNullPath()
    {
        $extension = new UrlExtension();

        $actualType = $extension->getTypeFromFunctionCall(
            $this->mockFunctionReflection(),
            $this->mockFunctionCall(['/some/path']),
            $this->mockScope()
        );

        $this->assertInstanceOf(StringType::class, $actualType);
    }

    /**
     * Create a FunctionReflection mock.
     *
     * @return \PHPStan\Reflection\FunctionReflection|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockFunctionReflection(): MockObject
    {
        return $this->createMock(FunctionReflection::class);
    }

    /**
     * Create an instance of FuncCall.
     *
     * @param  \PhpParser\Node\Expr[] $arguments
     * @return \PhpParser\Node\Expr\FuncCall
     */
    private function mockFunctionCall(array $arguments = []): FuncCall
    {
        $name = $this->createMock(\PhpParser\Node\Name::class);

        return new FuncCall($name, $arguments);
    }

    /**
     * Create a Scope mock.
     *
     * @return \PHPStan\Analyser\Scope|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockScope(): MockObject
    {
        return $this->createMock(Scope::class);
    }
}

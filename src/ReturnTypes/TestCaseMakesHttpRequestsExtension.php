<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Foundation\Testing\TestCase;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class TestCaseMakesHttpRequestsExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return TestCase::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'get',
            'getJson',
            'post',
            'postJson',
            'put',
            'putJson',
            'patch',
            'patchJson',
            'delete',
            'deleteJson',
            'options',
            'optionsJson',
            'head',
            'json',
            'call',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        return new ObjectType('Illuminate\\Testing\\TestResponse');
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function in_array;

final class PendingRequestDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const HTTP_METHODS = ['get', 'post', 'put', 'patch', 'delete', 'head', 'send'];

    public function getClass(): string
    {
        return PendingRequest::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), self::HTTP_METHODS, true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        // Check if async() was called in the method chain
        if ($this->hasAsyncInChain($methodCall)) {
            // Return null to use the original return type (Response|PromiseInterface)
            return null;
        }

        // Synchronous call - return Response only
        return new ObjectType(Response::class);
    }

    private function hasAsyncInChain(MethodCall $methodCall): bool
    {
        $current = $methodCall->var;

        while ($current instanceof MethodCall) {
            if ($current->name instanceof Identifier && $current->name->name === 'async') {
                return true;
            }

            $current = $current->var;
        }

        return false;
    }
}

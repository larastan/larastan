<?php

declare(strict_types=1);

namespace Larastan\Larastan\Ignore;

use Illuminate\Support\Facades\Facade;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;

final class FacadeStaticCallIgnoreExtension implements IgnoreErrorExtension
{
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'method.staticCall') {
            return false;
        }

        if (
            ! $node instanceof StaticCall
            || ! $node->name instanceof Identifier
        ) {
            return false;
        }

        $type = $node->class instanceof Name
            ? $scope->resolveTypeByName($node->class)
            : $scope->getType($node->class);

        if (! (new ObjectType(Facade::class))->isSuperTypeOf($type)->yes()) {
            return false;
        }

        $method = $node->name->toString();

        foreach ($type->getObjectClassReflections() as $classReflection) {
            if ($classReflection->hasNativeMethod($method)) {
                return false;
            }
        }

        return true;
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

#[AutowiredService]
final class ModelGetOriginalDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getOriginal';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        // The default value also goes through the casts, so its own type would be wrong.
        if (count($args) !== 1 || $args[0]->unpack) {
            return null;
        }

        $argStrings = $scope->getType($args[0]->value)->getConstantStrings();

        if ($argStrings === []) {
            return null;
        }

        $model = $scope->getType($methodCall->var);
        $types = [];

        foreach ($argStrings as $argString) {
            $name = $argString->getValue();

            if (! $model->hasInstanceProperty($name)->yes() || $this->isRelation($model, $name)) {
                return null;
            }

            // Original values go through the same accessors and casts as current values.
            $types[] = $model->getInstanceProperty($name, $scope)->getReadableType();
        }

        return TypeCombinator::union(...$types);
    }

    /** Relations are not attributes, so getOriginal() returns null for them. */
    private function isRelation(Type $model, string $name): bool
    {
        foreach ($model->getObjectClassReflections() as $class) {
            if (! $class->hasNativeMethod($name)) {
                continue;
            }

            $returnType = $class->getNativeMethod($name)->getVariants()[0]->getReturnType();

            if (! (new ObjectType(Relation::class))->isSuperTypeOf($returnType)->no()) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Validation\Rules\Date;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function class_exists;
use function in_array;

/** @internal */
final class ValidationRuleBuilderDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /** @param class-string $className */
    public function __construct(private string $className)
    {
    }

    public function getClass(): string
    {
        return class_exists($this->className) ? $this->className : self::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            $this->className === Date::class ? ['format'] : ['lowercase', 'uppercase'],
            true,
        );
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $valueType = match ($methodReflection->getName()) {
            'lowercase' => new AccessoryLowercaseStringType(),
            'uppercase' => new AccessoryUppercaseStringType(),
            default => TypeCombinator::union(new FloatType(), new IntegerType(), new StringType()),
        };

        return new GenericObjectType($this->className, [
            TypeCombinator::intersect(
                $scope->getType($methodCall->var)->getTemplateType($this->className, 'TValue'),
                $valueType,
            ),
        ]);
    }
}

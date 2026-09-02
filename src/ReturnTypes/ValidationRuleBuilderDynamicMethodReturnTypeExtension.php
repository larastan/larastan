<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Validation\Rules\Numeric;
use LogicException;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

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
        return $this->className;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), $this->refiningMethods(), true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $valueType = match ($methodReflection->getName()) {
            'lowercase' => new AccessoryLowercaseStringType(),
            'uppercase' => new AccessoryUppercaseStringType(),
            'integer' => isset($methodCall->getArgs()[0])
                && $scope->getType($methodCall->getArgs()[0]->value)->isTrue()->yes()
                    ? new IntegerType()
                    : self::looseIntegerType(),
            'digits', 'digitsBetween', 'exactly' => self::looseIntegerType(),
            default => throw new LogicException('Unsupported validation rule builder method.'),
        };

        if ($this->className !== Numeric::class) {
            $currentType = $scope->getType($methodCall->var)->getTemplateType($this->className, 'TValue');
            $valueType   = TypeCombinator::intersect($currentType, $valueType);
        }

        return new GenericObjectType($this->className, [$valueType]);
    }

    /** @return list<string> */
    private function refiningMethods(): array
    {
        return $this->className === Numeric::class
            ? ['digits', 'digitsBetween', 'exactly', 'integer']
            : ['lowercase', 'uppercase'];
    }

    private static function looseIntegerType(): Type
    {
        return TypeUtils::toBenevolentUnion(TypeCombinator::union(
            new IntegerType(),
            new AccessoryNumericStringType(),
        ));
    }
}

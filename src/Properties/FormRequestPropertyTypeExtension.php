<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Reflection\ReflectionHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

use function in_array;
use function strtolower;

/** @internal */
final class FormRequestPropertyTypeExtension implements ExpressionTypeResolverExtension
{
    private const UNSAFE_METHODS = [
        'setcontainer',
        'setredirector',
        'validateresolved',
        'prepareforvalidation',
        'passesauthorization',
        'authorize',
        'failedauthorization',
        'getvalidatorinstance',
        'isprecognitive',
        'filterprecognitiverules',
        'configurefromattributes',
        'validator',
        'createdefaultvalidator',
        'validationrules',
        'rules',
        'validationdata',
        'messages',
        'attributes',
        'withvalidator',
        'after',
        'setvalidator',
        'failedvalidation',
        'getredirecturl',
        'shouldfailonunknownfields',
        'validatenounknownfields',
        'dotinputkeys',
        'isknownfield',
    ];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getType(Expr $expr, Scope $scope): Type|null
    {
        if (
            ! $expr instanceof PropertyFetch
            || ! $expr->name instanceof Identifier
            || $scope->hasExpressionType($expr)->yes()
        ) {
            return null;
        }

        $function = $scope->getFunction();

        if (
            $function === null
            || ! $function->isMethodOrPropertyHook()
            || ! in_array(strtolower($function->getName()), self::UNSAFE_METHODS, true)
            || TypeUtils::findThisType($scope->getType($expr->var)) === null
        ) {
            return null;
        }

        $classReflection = $scope->getClassReflection();

        if (
            $classReflection === null
            || ! $classReflection->isSubclassOfClass($this->reflectionProvider->getClass(FormRequest::class))
            || $classReflection->hasNativeProperty($expr->name->name)
            || ReflectionHelper::hasPropertyTag($classReflection, $expr->name->name)
        ) {
            return null;
        }

        return new MixedType();
    }
}

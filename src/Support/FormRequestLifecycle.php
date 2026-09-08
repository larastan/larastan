<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Foundation\Http\FormRequest;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

use function in_array;
use function strtolower;

/**
 * Tells whether a FormRequest reads its own input before validation has run.
 *
 * @internal
 */
final class FormRequestLifecycle
{
    /** FormRequest methods that Laravel runs before, or instead of, a successful validation. */
    private const UNVALIDATED_METHODS = [
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

    /** Whether $receiverType is the FormRequest itself, read inside one of its pre-validation methods. */
    public function isBeforeValidation(Scope $scope, Type $receiverType): bool
    {
        $function = $scope->getFunction();

        if (
            $function === null
            || ! $function->isMethodOrPropertyHook()
            || ! in_array(strtolower($function->getName()), self::UNVALIDATED_METHODS, true)
            || TypeUtils::findThisType($receiverType) === null
        ) {
            return false;
        }

        $classReflection = $scope->getClassReflection();

        return $classReflection !== null
            && $classReflection->isSubclassOfClass($this->reflectionProvider->getClass(FormRequest::class));
    }
}

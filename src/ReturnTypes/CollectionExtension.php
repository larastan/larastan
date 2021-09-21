<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Support\Collection;
use NunoMaduro\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class CollectionExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var CollectionHelper
     */
    private $collectionHelper;

    public function __construct(CollectionHelper $collectionHelper)
    {
        $this->collectionHelper = $collectionHelper;
    }

    public function getClass(): string
    {
        return Collection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'make';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        if (count($methodCall->getArgs()) < 1) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $valueType = $scope->getType($methodCall->getArgs()[0]->value);

        return $this->collectionHelper->determineGenericCollectionTypeFromType($valueType);
    }
}

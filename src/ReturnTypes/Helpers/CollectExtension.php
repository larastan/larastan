<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes\Helpers;

use Illuminate\Support\Collection;
use NunoMaduro\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

final class CollectExtension implements DynamicFunctionReturnTypeExtension
{
    /**
     * @var CollectionHelper
     */
    private $collectionHelper;

    public function __construct(CollectionHelper $collectionHelper)
    {
        $this->collectionHelper = $collectionHelper;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'collect';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): ?Type {
        if (count($functionCall->getArgs()) < 1) {
            return new GenericObjectType(Collection::class, [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]);
        }

        $valueType = $scope->getType($functionCall->getArgs()[0]->value);

        return $this->collectionHelper->determineGenericCollectionTypeFromType($valueType);
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use Illuminate\Support\Collection;
use Larastan\Larastan\Support\CollectionHelper;
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

use function count;

final class CollectExtension implements DynamicFunctionReturnTypeExtension
{
    private Type|null $emptyCollectionType = null;

    /**
     * With PHPStan's unresolvedTemplateArguments feature toggle, the template arguments of an
     * empty collection are inferred from how it is used, so the extension must not fix them.
     */
    public function __construct(private CollectionHelper $collectionHelper, private bool $inferFromUsage)
    {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'collect';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type|null {
        if (count($functionCall->getArgs()) < 1) {
            if ($this->inferFromUsage) {
                return null;
            }

            return $this->emptyCollectionType ??= new GenericObjectType(Collection::class, [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]);
        }

        $valueType = $scope->getType($functionCall->getArgs()[0]->value);

        if ($this->inferFromUsage && $valueType->isArray()->yes() && $valueType->isIterableAtLeastOnce()->no()) {
            return null;
        }

        return $this->collectionHelper->determineGenericCollectionTypeFromType($valueType);
    }
}

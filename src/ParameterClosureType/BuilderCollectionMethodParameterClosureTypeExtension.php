<?php

declare(strict_types=1);

namespace Larastan\Larastan\ParameterClosureType;

use Illuminate\Database\Eloquent\Builder;
use Larastan\Larastan\Internal\LaravelVersion;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ClosureType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function in_array;

class BuilderCollectionMethodParameterClosureTypeExtension implements MethodParameterClosureTypeExtension
{
    public function __construct(private CollectionHelper $collectionHelper)
    {
    }

    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $methodReflection->getDeclaringClass()->is(Builder::class) &&
            $parameter->getName() === 'callback' &&
            in_array($methodReflection->getName(), [
                'chunk',
                'chunkById',
                'chunkByIdDesc',
                'orderedChuckById',
            ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        $calledOnType = $scope->getType($methodCall->var);

        if ((new ObjectType(Builder::class))->isSuperTypeOf($calledOnType)->no()) {
            return null;
        }

        $model = $calledOnType->getTemplateType(Builder::class, LaravelVersion::getBuilderModelGenericName());

        if ($model->getObjectClassNames() === []) {
            return null;
        }

        $collectionType = $this->collectionHelper->determineCollectionClass($model->getObjectClassNames()[0], $model);

        return new ClosureType([
            $this->getParameterReflection($collectionType, 'collection'),
            $this->getParameterReflection(IntegerRangeType::createAllGreaterThanOrEqualTo(1), 'page'),
        ]);
    }

    private function getParameterReflection(Type $type, string $name): ParameterReflection
    {
        return new class ($type, $name) implements ParameterReflection
        {
            public function __construct(
                private Type $type,
                private string $name,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function isOptional(): bool
            {
                return false;
            }

            public function getType(): Type
            {
                return $this->type;
            }

            public function passedByReference(): PassedByReference
            {
                return PassedByReference::createNo();
            }

            public function isVariadic(): bool
            {
                return false;
            }

            public function getDefaultValue(): Type|null
            {
                return null;
            }
        };
    }
}

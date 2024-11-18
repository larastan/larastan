<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Internal\LaravelVersion;
use Larastan\Larastan\Support\CollectionHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PhpParser\Node\Expr\MethodCall;

final class CollectionChunkClosureTypeExtension implements MethodParameterClosureTypeExtension
{
    public function __construct(private CollectionHelper $collectionHelper)
    {
    }

    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        if (!in_array($methodReflection->getName(), ['chunk', 'chunkById', 'chunkByIdDesc'])) {
            return false;
        }

        if ($parameter->getName() != 'callback') {
            return false;
        }

        return (new ObjectType(EloquentBuilder::class))->isSuperTypeOf(new ObjectType($methodReflection->getDeclaringClass()->getName()))->yes();
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope
    ): Type|null {
        $modelClassType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType(LaravelVersion::getBuilderModelGenericName());

        if ((new ObjectType(Model::class))->isSuperTypeOf($modelClassType)->no()) {
            return null;
        }

        $modelClassName = $modelClassType->getClassName();
        $collectionType = $this->collectionHelper->determineCollectionClass($modelClassName);

        $notByReference = PassedByReference::createNo();
        return new ClosureType(
            [
                new NativeParameterReflection(
                    'collection',
                    optional: false,
                    type: $collectionType,
                    passedByReference: $notByReference,
                    variadic: false,
                    defaultValue: null,
                ),
                new NativeParameterReflection(
                    'index',
                    optional: true,
                    type: new IntegerType(),
                    passedByReference: $notByReference,
                    variadic: false,
                    defaultValue: null,
                ),
            ],
            new MixedType(),
        );
    }

}

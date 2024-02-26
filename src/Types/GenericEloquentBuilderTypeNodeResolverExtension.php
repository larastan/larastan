<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function count;

class GenericEloquentBuilderTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    public function __construct(private ReflectionProvider $provider)
    {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if (! $typeNode instanceof UnionTypeNode || count($typeNode->types) !== 2) {
            return null;
        }

        $modelTypeNode   = null;
        $builderTypeNode = null;
        foreach ($typeNode->types as $innerTypeNode) {
            if (
                ! ($innerTypeNode instanceof IdentifierTypeNode)
                || ! $this->provider->hasClass($nameScope->resolveStringName($innerTypeNode->name))
                || ! (new ObjectType(Model::class))->isSuperTypeOf(new ObjectType($nameScope->resolveStringName($innerTypeNode->name)))->yes()
            ) {
                continue;
            }

            $modelTypeNode = $innerTypeNode;
        }

        if ($modelTypeNode === null) {
            return null;
        }

        foreach ($typeNode->types as $innerTypeNode) {
            if (
                ! ($innerTypeNode instanceof IdentifierTypeNode)
                || ! $this->provider->hasClass($nameScope->resolveStringName($innerTypeNode->name))
                || ($nameScope->resolveStringName($innerTypeNode->name) !== Builder::class && ! (new ObjectType(Builder::class))->isSuperTypeOf(new ObjectType($nameScope->resolveStringName($innerTypeNode->name)))->yes())
            ) {
                continue;
            }

            $builderTypeNode = $innerTypeNode;
        }

        if ($builderTypeNode === null) {
            return null;
        }

        $builderTypeName = $nameScope->resolveStringName($builderTypeNode->name);
        $modelTypeName   = $nameScope->resolveStringName($modelTypeNode->name);

        return new GenericObjectType($builderTypeName, [
            new ObjectType($modelTypeName),
        ]);
    }
}

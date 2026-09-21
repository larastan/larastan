<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\CollectionOf;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Support\CollectionHelper;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

use function count;

final class CollectionOfTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{
    private TypeNodeResolver $typeNodeResolver;

    public function __construct(
        private CollectionHelper $collectionHelper,
    ) {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if (! $typeNode instanceof GenericTypeNode) {
            return null;
        }

        if ($typeNode->type->name !== 'collection-of') {
            return null;
        }

        $genericTypes = $typeNode->genericTypes;

        if (count($genericTypes) !== 1 && count($genericTypes) !== 2) {
            return null;
        }

        // Like array<TKey, TValue>, the optional key type comes first.
        $keyType     = count($genericTypes) === 2 ? $this->typeNodeResolver->resolve($genericTypes[0], $nameScope) : null;
        $genericType = $this->typeNodeResolver->resolve($genericTypes[count($genericTypes) - 1], $nameScope);

        if ((new ObjectType(Model::class))->isSuperTypeOf($genericType)->no()) {
            return null;
        }

        if ($genericType instanceof NeverType) {
            return null;
        }

        if ($keyType !== null && (new UnionType([new IntegerType(), new StringType()]))->isSuperTypeOf($keyType)->no()) {
            return null;
        }

        return new CollectionOfType($genericType, $this->collectionHelper, $keyType);
    }

    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }
}

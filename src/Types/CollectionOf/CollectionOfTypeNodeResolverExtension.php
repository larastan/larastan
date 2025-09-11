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
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

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

        if (count($typeNode->genericTypes) !== 1) {
            return null;
        }

        $genericType = $this->typeNodeResolver->resolve($typeNode->genericTypes[0], $nameScope);

        if ((new ObjectType(Model::class))->isSuperTypeOf($genericType)->no()) {
            return null;
        }

        if ($genericType instanceof NeverType) {
            return null;
        }

        return new CollectionOfType($genericType, $this->collectionHelper);
    }

    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }
}

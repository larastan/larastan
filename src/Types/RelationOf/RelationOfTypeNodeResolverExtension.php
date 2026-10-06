<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\RelationOf;

use Illuminate\Database\Eloquent\Model;
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

final class RelationOfTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{
    private TypeNodeResolver $typeNodeResolver;

    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if (! $typeNode instanceof GenericTypeNode || $typeNode->type->name !== 'relation-of' || count($typeNode->genericTypes) !== 2) {
            return null;
        }

        $model = $this->typeNodeResolver->resolve($typeNode->genericTypes[0], $nameScope);
        $key   = $this->typeNodeResolver->resolve($typeNode->genericTypes[1], $nameScope);

        if ($model instanceof NeverType || (new ObjectType(Model::class))->isSuperTypeOf($model)->no()) {
            return null;
        }

        if ($key instanceof NeverType || ! $key->isString()->yes()) {
            return null;
        }

        return new RelationOfType($model, $key);
    }

    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }
}

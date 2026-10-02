<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Contracts\Support\Arrayable;
use PHPStan\Analyser\NameScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

/** Normalizes Laravel 13's Arrayable<TKey, TValue> to Laravel 14's Arrayable<TReturn>. */
#[AutowiredService]
final class ArrayableTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{
    private TypeNodeResolver $typeNodeResolver;

    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if (
            ! $typeNode instanceof GenericTypeNode
            || count($typeNode->genericTypes) !== 2
            || $nameScope->resolveStringName((string) $typeNode->type) !== Arrayable::class
        ) {
            return null;
        }

        $keyType = $this->typeNodeResolver->resolve($typeNode->genericTypes[0], $nameScope);

        if (
            ($typeNode->variances[0] ?? GenericTypeNode::VARIANCE_INVARIANT) !== GenericTypeNode::VARIANCE_BIVARIANT
            && ! TypeCombinator::union(new IntegerType(), new StringType())->isSuperTypeOf($keyType)->yes()
        ) {
            return null;
        }

        $variance = in_array(GenericTypeNode::VARIANCE_CONTRAVARIANT, $typeNode->variances, true)
            ? TemplateTypeVariance::createContravariant()
            : TemplateTypeVariance::createInvariant();

        return new GenericObjectType(Arrayable::class, [
            $this->typeNodeResolver->resolve(new GenericTypeNode(new IdentifierTypeNode('array'), $typeNode->genericTypes), $nameScope),
        ], variances: [$variance]);
    }

    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }
}

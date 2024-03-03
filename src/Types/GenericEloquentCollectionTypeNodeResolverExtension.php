<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Database\Eloquent\Collection;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

use function count;

/**
 * @see https://github.com/larastan/larastan/issues/476
 * @see https://gist.github.com/ondrejmirtes/56af016d0595788d5400b8dfb6520adc
 *
 * This extension interprets docblocks like:
 *
 * \Illuminate\Database\Eloquent\Collection|\App\Account[] $accounts
 *
 * and transforms them into:
 *
 * \Illuminate\Database\Eloquent\Collection<int, \App\Account> $accounts
 *
 * Now IDE's can benefit from auto-completion, and we can benefit from the correct type passed to the generic collection
 */
class GenericEloquentCollectionTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    public function __construct(private TypeNodeResolver $typeNodeResolver)
    {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if (! $typeNode instanceof UnionTypeNode || count($typeNode->types) !== 2) {
            return null;
        }

        $arrayTypeNode      = null;
        $identifierTypeNode = null;
        foreach ($typeNode->types as $innerTypeNode) {
            if ($innerTypeNode instanceof ArrayTypeNode) {
                $arrayTypeNode = $innerTypeNode;
                continue;
            }

            if (! ($innerTypeNode instanceof IdentifierTypeNode)) {
                continue;
            }

            $identifierTypeNode = $innerTypeNode;
        }

        if ($arrayTypeNode === null || $identifierTypeNode === null) {
            return null;
        }

        $identifierTypeName = $nameScope->resolveStringName($identifierTypeNode->name);
        if ($identifierTypeName !== Collection::class) {
            return null;
        }

        $innerArrayTypeNode = $arrayTypeNode->type;
        if (! $innerArrayTypeNode instanceof IdentifierTypeNode) {
            return null;
        }

        $resolvedInnerArrayType = $this->typeNodeResolver->resolve($innerArrayTypeNode, $nameScope);

        return new GenericObjectType($identifierTypeName, [
            new IntegerType(),
            $resolvedInnerArrayType,
        ]);
    }
}

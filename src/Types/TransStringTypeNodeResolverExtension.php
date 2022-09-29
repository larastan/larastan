<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

/**
 * Ensures a 'trans-string' type in PHPDoc is recognised to be of type TransStringType.
 */
class TransStringTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof IdentifierTypeNode && $typeNode->__toString() === 'trans-string') {
            return new TransStringType();
        }

        return null;
    }
}

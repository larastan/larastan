<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

/**
 * Ensures a 'blade-view' type in PHPDoc is recognised to be of type BladeViewType.
 */
class BladeViewTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof IdentifierTypeNode && $typeNode->__toString() === 'blade-view') {
            return new BladeViewType();
        }
        return null;
    }
}

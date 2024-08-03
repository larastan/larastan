<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

/**
 * Ensures a 'route-name-string' type in PHPDoc is recognised to be of type RouteNameStringType.
 */
class RouteNameStringTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if ($typeNode instanceof IdentifierTypeNode && $typeNode->__toString() === 'route-name-string') {
            return new RouteNameStringType();
        }

        return null;
    }
}

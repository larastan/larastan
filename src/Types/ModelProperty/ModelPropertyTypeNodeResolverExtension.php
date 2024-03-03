<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\ModelProperty;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

use function count;

/**
 * Ensures a 'model-property' type in PHPDoc is recognised to be of type ModelPropertyType.
 */
final class ModelPropertyTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    public function __construct(protected TypeNodeResolver $baseResolver, protected bool $active)
    {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if ($typeNode instanceof IdentifierTypeNode && $typeNode->name === 'model-property') {
            return $this->active ? new ModelPropertyType() : new StringType();
        }

        if ($typeNode instanceof GenericTypeNode && $typeNode->type->name === 'model-property') {
            if (! $this->active) {
                return new StringType();
            }

            if (count($typeNode->genericTypes) !== 1) {
                return new ErrorType();
            }

            $genericType = $this->baseResolver->resolve($typeNode->genericTypes[0], $nameScope);

            if ((new ObjectType(Model::class))->isSuperTypeOf($genericType)->no()) {
                return new ErrorType();
            }

            if ($genericType instanceof NeverType) {
                return new ErrorType();
            }

            return new GenericModelPropertyType($genericType);
        }

        return null;
    }
}

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

use function array_key_exists;
use function count;

class GenericEloquentBuilderTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    private const KIND_MODEL = 1;

    private const KIND_BUILDER = 2;

    /**
     * This extension is consulted for every PHPDoc type node, so the verdict
     * for a resolved identifier (model class, builder class, or neither) is
     * memoized to avoid repeated reflection lookups for common union members
     * like `string` or `null`.
     *
     * @var array<string, int|null>
     */
    private array $classKind = [];

    public function __construct(private ReflectionProvider $provider)
    {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): Type|null
    {
        if (! $typeNode instanceof UnionTypeNode || count($typeNode->types) !== 2) {
            return null;
        }

        $modelTypeName   = null;
        $builderTypeName = null;

        foreach ($typeNode->types as $innerTypeNode) {
            // A matching union needs a model member and a builder member, so
            // any member that is not a plain identifier means we can bail out.
            if (! $innerTypeNode instanceof IdentifierTypeNode) {
                return null;
            }

            $resolvedName = $nameScope->resolveStringName($innerTypeNode->name);
            $kind         = $this->resolveClassKind($resolvedName);

            if ($kind === self::KIND_MODEL && $modelTypeName === null) {
                $modelTypeName = $resolvedName;
            } elseif ($kind === self::KIND_BUILDER && $builderTypeName === null) {
                $builderTypeName = $resolvedName;
            }
        }

        if ($modelTypeName === null || $builderTypeName === null) {
            return null;
        }

        if (! $this->provider->getClass($builderTypeName)->isGeneric()) {
            return new ObjectType($builderTypeName);
        }

        return new GenericObjectType($builderTypeName, [
            new ObjectType($modelTypeName),
        ]);
    }

    private function resolveClassKind(string $resolvedName): int|null
    {
        if (array_key_exists($resolvedName, $this->classKind)) {
            return $this->classKind[$resolvedName];
        }

        $kind = null;

        if ($this->provider->hasClass($resolvedName)) {
            $classReflection = $this->provider->getClass($resolvedName);

            if ($classReflection->is(Model::class)) {
                $kind = self::KIND_MODEL;
            } elseif ($classReflection->is(Builder::class)) {
                $kind = self::KIND_BUILDER;
            }
        }

        return $this->classKind[$resolvedName] = $kind;
    }
}

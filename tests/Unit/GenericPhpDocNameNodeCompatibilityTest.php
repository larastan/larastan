<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Types\BuilderOf\BuilderOfTypeNodeResolverExtension;
use Larastan\Larastan\Types\CollectionOf\CollectionOfTypeNodeResolverExtension;
use Larastan\Larastan\Types\ModelProperty\ModelPropertyTypeNodeResolverExtension;
use Larastan\Larastan\Types\RelationOf\RelationOfTypeNodeResolverExtension;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\FullyQualifiedNameNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\RelativeNameNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function class_exists;

final class GenericPhpDocNameNodeCompatibilityTest extends TestCase
{
    #[Test]
    public function name_nodes_are_handled_by_all_generic_type_resolvers(): void
    {
        if (! class_exists(RelativeNameNode::class) || ! class_exists(FullyQualifiedNameNode::class)) {
            self::markTestSkipped('Requires phpstan/phpdoc-parser 2.4 or newer.');
        }

        /** @var list<class-string<TypeNodeResolverExtension>> $resolvers */
        $resolvers = [
            BuilderOfTypeNodeResolverExtension::class,
            CollectionOfTypeNodeResolverExtension::class,
            ModelPropertyTypeNodeResolverExtension::class,
            RelationOfTypeNodeResolverExtension::class,
        ];

        foreach ($resolvers as $resolverClass) {
            // Nothing beyond the initial generic name check should be invoked.
            $resolver = (new ReflectionClass($resolverClass))->newInstanceWithoutConstructor();
            $scope    = new NameScope(null, []);

            $relative = new GenericTypeNode(new RelativeNameNode('unrelated-type'), [
                new IdentifierTypeNode('App\\User'),
            ]);
            self::assertNull($resolver->resolve($relative, $scope), $resolverClass);

            $qualified = new GenericTypeNode(new FullyQualifiedNameNode('unrelated-type'), [
                new IdentifierTypeNode('App\\User'),
            ]);
            self::assertNull($resolver->resolve($qualified, $scope), $resolverClass);
        }
    }
}

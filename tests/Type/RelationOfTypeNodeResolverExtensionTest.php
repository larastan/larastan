<?php

declare(strict_types=1);

namespace Tests\Type;

use Larastan\Larastan\Types\RelationOf\RelationOfTypeNodeResolverExtension;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_fill;

class RelationOfTypeNodeResolverExtensionTest extends PHPStanTestCase
{
    private RelationOfTypeNodeResolverExtension $extension;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::getContainer();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = self::getContainer()->getByType(RelationOfTypeNodeResolverExtension::class);
        $this->extension->setTypeNodeResolver(self::getContainer()->getByType(TypeNodeResolver::class));
    }

    #[DataProvider('invalidTypes')]
    public function testInvalidTypesReturnNull(TypeNode $node): void
    {
        $this->assertNull($this->extension->resolve($node, new NameScope(null, [])));
    }

    /** @return iterable<string, array{TypeNode}> */
    public static function invalidTypes(): iterable
    {
        yield 'not generic' => [new IdentifierTypeNode('string')];
        yield 'other type' => [new GenericTypeNode(new IdentifierTypeNode('builder-of'), [new IdentifierTypeNode('App\User')])];

        foreach ([0, 1, 3] as $count) {
            yield 'arity ' . $count => [new GenericTypeNode(new IdentifierTypeNode('relation-of'), array_fill(0, $count, new IdentifierTypeNode('App\User')))];
        }

        foreach (['string', 'int', 'stdClass', 'never'] as $model) {
            yield 'model ' . $model => [new GenericTypeNode(new IdentifierTypeNode('relation-of'), [new IdentifierTypeNode($model), new IdentifierTypeNode('string')])];
        }

        foreach (['int', 'mixed', 'null', 'never', 'App\User'] as $key) {
            yield 'key ' . $key => [new GenericTypeNode(new IdentifierTypeNode('relation-of'), [new IdentifierTypeNode('App\User'), new IdentifierTypeNode($key)])];
        }

        yield 'nullable key' => [new GenericTypeNode(new IdentifierTypeNode('relation-of'), [new IdentifierTypeNode('App\User'), new NullableTypeNode(new IdentifierTypeNode('string'))])];
    }

    public function testUnboundedKeyTemplateReturnsNull(): void
    {
        $template  = TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('test'), 'TKey', null, TemplateTypeVariance::createInvariant());
        $nameScope = (new NameScope(null, []))->withTemplateTypeMap(new TemplateTypeMap(['TKey' => $template]), []);
        $node      = new GenericTypeNode(new IdentifierTypeNode('relation-of'), [new IdentifierTypeNode('App\User'), new IdentifierTypeNode('TKey')]);

        $this->assertNull($this->extension->resolve($node, $nameScope));
    }

    public function testPhpDocRoundTripPreservesTypeAndDistinguishesBuilders(): void
    {
        $resolver = self::getContainer()->getByType(TypeStringResolver::class);
        $relation = $resolver->resolve("relation-of<App\User, 'accounts'|'group'>");
        $builder  = $resolver->resolve("builder-of<App\User, 'accounts'|'group'>");

        $this->assertSame("relation-of<App\User, 'accounts'|'group'>", $relation->describe(VerbosityLevel::precise()));
        $this->assertTrue($relation->equals($resolver->resolve((string) $relation->toPhpDocNode())));
        $this->assertFalse($relation->equals($resolver->resolve("relation-of<App\User, 'accounts'>")));
        $this->assertFalse($relation->equals($builder));
        $this->assertFalse($builder->equals($relation));
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Type;

use Larastan\Larastan\Types\BuilderOf\BuilderOfTypeNodeResolverExtension;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class BuilderOfTypeNodeResolverExtensionTest extends PHPStanTestCase
{
    private BuilderOfTypeNodeResolverExtension $extension;
    private NameScope $nameScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = static::getContainer()->getByType(BuilderOfTypeNodeResolverExtension::class);

        $this->extension->setTypeNodeResolver(static::getContainer()->getByType(TypeNodeResolver::class));

        $this->nameScope = new NameScope(null, []);
    }

    public function testNonGenericTypeNodeReturnsNull(): void
    {
        $typeNode = new IdentifierTypeNode('string');

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    public function testNonBuilderOfGenericTypeReturnsNull(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('collection-of'),
            [new IdentifierTypeNode('User')],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    public function testBuilderOfWithNoGenericTypesReturnsNull(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('builder-of'),
            [],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    public function testBuilderOfWithTooManyGenericTypesReturnsNull(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('builder-of'),
            [
                new IdentifierTypeNode('App\User'),
                new IdentifierTypeNode('string'),
                new IdentifierTypeNode('string'),
            ],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    #[DataProvider('invalidRelationTypesProvider')]
    public function testBuilderOfWithInvalidRelationTypeReturnsNull(TypeNode $relationType): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('builder-of'),
            [new IdentifierTypeNode('App\User'), $relationType],
        );

        $this->assertNull($this->extension->resolve($typeNode, $this->nameScope));
    }

    /** @return iterable<string, array{TypeNode}> */
    public static function invalidRelationTypesProvider(): iterable
    {
        foreach (['int', 'mixed', 'null', 'never', 'App\Account'] as $type) {
            yield $type => [new IdentifierTypeNode($type)];
        }

        yield 'nullable string' => [new NullableTypeNode(new IdentifierTypeNode('string'))];
        yield 'string or int' => [new UnionTypeNode([new IdentifierTypeNode('string'), new IdentifierTypeNode('int')])];
    }

    public function testBuilderOfWithUnboundedRelationTemplateReturnsNull(): void
    {
        $template  = TemplateTypeFactory::create(
            TemplateTypeScope::createWithFunction('test'),
            'TRelation',
            null,
            TemplateTypeVariance::createInvariant(),
        );
        $nameScope = $this->nameScope->withTemplateTypeMap(new TemplateTypeMap(['TRelation' => $template]), []);
        $typeNode  = new GenericTypeNode(
            new IdentifierTypeNode('builder-of'),
            [new IdentifierTypeNode('App\User'), new IdentifierTypeNode('TRelation')],
        );

        $this->assertNull($this->extension->resolve($typeNode, $nameScope));
    }

    public function testRelationshipTypeSurvivesPhpDocSerialization(): void
    {
        $resolver = static::getContainer()->getByType(TypeStringResolver::class);
        $type     = $resolver->resolve("builder-of<App\User, 'posts'>");

        $this->assertSame("builder-of<App\User, 'posts'>", $type->describe(VerbosityLevel::precise()));
        $this->assertTrue($type->equals($resolver->resolve((string) $type->toPhpDocNode())));
        $this->assertFalse($type->equals($resolver->resolve("builder-of<App\User, 'accounts'>")));
        $this->assertFalse($type->equals($resolver->resolve('builder-of<App\User>')));
    }

    public function testBuilderOfWithNonModelTypeReturnsNull(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('builder-of'),
            [new IdentifierTypeNode('string')],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    #[DataProvider('validModelTypesProvider')]
    public function testBuilderOfWithValidModelTypes(string $modelClass, string $expectedDescription): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('builder-of'),
            [new IdentifierTypeNode($modelClass)],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNotNull($result);
        $this->assertInstanceOf(LateResolvableType::class, $result);
        $this->assertStringContainsString($expectedDescription, $result->resolve()->describe(VerbosityLevel::value()));
    }

    /** @return array<string, array{string, string}> */
    public static function validModelTypesProvider(): array
    {
        return [
            'User model with standard builder' => [
                'App\User',
                'Illuminate\Database\Eloquent\Builder<App\User>',
            ],
            'Post model with custom builder' => [
                'App\Post',
                'App\PostBuilder<App\Post>',
            ],
            'Account model with standard builder' => [
                'App\Account',
                'Illuminate\Database\Eloquent\Builder<App\Account>',
            ],
            'Team model with custom builder' => [
                'App\Team',
                'App\ChildTeamBuilder',
            ],
        ];
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }
}

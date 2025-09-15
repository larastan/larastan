<?php

declare(strict_types=1);

namespace Tests\Type;

use Larastan\Larastan\Types\BuilderOf\BuilderOfTypeNodeResolverExtension;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Testing\PHPStanTestCase;
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

    public function testBuilderOfWithMultipleGenericTypesReturnsNull(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('builder-of'),
            [
                new IdentifierTypeNode('User'),
                new IdentifierTypeNode('Account'),
            ],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
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

    /** @dataProvider validModelTypesProvider */
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

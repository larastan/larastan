<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Validation;

use Larastan\Larastan\Support\Validation\RuleTreeBuilder;
use Larastan\Larastan\Support\Validation\RuleTreeNode;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PHPUnit\Framework\TestCase;

use function array_keys;

class RuleTreeBuilderTest extends TestCase
{
    public function testFlatKeys(): void
    {
        $roots = RuleTreeBuilder::build([
            'name' => ValidationRuleFactory::make('required|string'),
            'age' => ValidationRuleFactory::make('required|integer'),
        ]);

        $this->assertSame(['name', 'age'], array_keys($roots));
        $this->assertSame('string', $roots['name']->rule?->type);
        $this->assertSame([], $roots['name']->children);
        $this->assertSame('float|int|numeric-string|true|Stringable', $roots['age']->rule?->type);
        $this->assertFalse($roots['name']->degraded);
    }

    public function testDotKeysMergeIntoOneRoot(): void
    {
        $roots = RuleTreeBuilder::build([
            'author.name' => ValidationRuleFactory::make('required|string'),
            'author.surname' => ValidationRuleFactory::make('nullable|string'),
        ]);

        $this->assertSame(['author'], array_keys($roots));
        $this->assertNull($roots['author']->rule);
        $this->assertSame(['name', 'surname'], array_keys($roots['author']->children));
        $this->assertSame('string', $roots['author']->children['name']->rule?->type);
        $this->assertTrue($roots['author']->children['surname']->rule?->nullable);
    }

    public function testWildcardChain(): void
    {
        $roots = RuleTreeBuilder::build([
            'users.*.email' => ValidationRuleFactory::make('required|email'),
        ]);

        $users = $roots['users'];
        $this->assertSame([RuleTreeNode::WILDCARD], array_keys($users->children));

        $element = $users->children[RuleTreeNode::WILDCARD];
        $this->assertNull($element->rule);
        $this->assertSame('string|Stringable', $element->children['email']->rule?->type);
    }

    public function testMultiWildcardChain(): void
    {
        $roots = RuleTreeBuilder::build([
            'users.*.addresses.*.city' => ValidationRuleFactory::make('required|string'),
        ]);

        $city = $roots['users']
            ->children[RuleTreeNode::WILDCARD]
            ->children['addresses']
            ->children[RuleTreeNode::WILDCARD]
            ->children['city'];

        $this->assertSame('string', $city->rule?->type);
        $this->assertTrue($city->rule?->required);
    }

    public function testTrailingWildcard(): void
    {
        $roots = RuleTreeBuilder::build([
            'tags.*' => ValidationRuleFactory::make('required|string'),
        ]);

        $this->assertNull($roots['tags']->rule);
        $this->assertSame('string', $roots['tags']->children[RuleTreeNode::WILDCARD]->rule?->type);
    }

    public function testParentAndNestedRulesOnSameNode(): void
    {
        $roots = RuleTreeBuilder::build([
            'users' => ValidationRuleFactory::make('required|array'),
            'users.*.email' => ValidationRuleFactory::make('required|email'),
        ]);

        $users = $roots['users'];
        $this->assertSame('array', $users->rule?->type);
        $this->assertTrue($users->rule?->required);
        $this->assertArrayHasKey(RuleTreeNode::WILDCARD, $users->children);
    }

    public function testRepeatedSegmentNames(): void
    {
        $roots = RuleTreeBuilder::build([
            'a.a.a' => ValidationRuleFactory::make('required|string'),
        ]);

        $leaf = $roots['a']->children['a']->children['a'];
        $this->assertSame('string', $leaf->rule?->type);
        $this->assertSame([], $leaf->children);
    }

    public function testNumericIndexDegradesRoot(): void
    {
        $roots = RuleTreeBuilder::build([
            'users.0.email' => ValidationRuleFactory::make('required|email'),
        ]);

        $this->assertTrue($roots['users']->degraded);
        $this->assertSame([], $roots['users']->children);
    }

    public function testEscapedDotStaysFlat(): void
    {
        $roots = RuleTreeBuilder::build([
            'v1\.0' => ValidationRuleFactory::make('required|string'),
        ]);

        $this->assertSame(['v1.0'], array_keys($roots));
        $this->assertSame('string', $roots['v1.0']->rule?->type);
        $this->assertSame([], $roots['v1.0']->children);
    }

    public function testWildcardMixedWithNamedSegmentDegrades(): void
    {
        $roots = RuleTreeBuilder::build([
            'tags.*' => ValidationRuleFactory::make('required|string'),
            'tags.first' => ValidationRuleFactory::make('required|string'),
        ]);

        $this->assertTrue($roots['tags']->degraded);
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\Validation\RuleTreeBuilder;
use Larastan\Larastan\Support\Validation\RuleTreeNode;
use Larastan\Larastan\Support\Validation\RuleTreeTypeResolver;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\NodeFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;

use function array_key_exists;
use function is_array;
use function is_string;

/** @internal */
final class FormRequestHelper
{
    /** @var array<class-string<FormRequest>, array<string, RuleTreeNode>> */
    private array $properties = [];

    public function __construct(
        private RuleTreeTypeResolver $treeTypeResolver,
        private Parser $parser,
    ) {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        if (! array_key_exists($className, $this->properties)) {
            $this->properties[$className] = $this->parseProperties($classReflection);
        }

        return array_key_exists($propertyName, $this->properties[$className]);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): Type
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        return $this->treeTypeResolver->resolveTopLevel($this->properties[$className][$propertyName]);
    }

    /** @return array<string, RuleTreeNode> */
    private function parseProperties(ClassReflection $classReflection): array
    {
        /** @var string $fileName */
        $fileName = $classReflection->getFileName();

        $stmts = $this->parser->parseFile($fileName);

        $rulesMethodNode = (new NodeFinder())->findFirst($stmts, static function (Node $node): bool {
            return $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === 'rules';
        });

        if ($rulesMethodNode === null) {
            return [];
        }

        /** @var Node\Stmt\Return_|null $returnNode */
        $returnNode = (new NodeFinder())->findFirstInstanceOf($rulesMethodNode, Node\Stmt\Return_::class);

        if ($returnNode === null) {
            return [];
        }

        if (! $returnNode->expr instanceof Array_) {
            return [];
        }

        $evaluator = new ConstExprEvaluator(static fn (Expr $expr) => null);

        $flatRules = [];

        foreach ($returnNode->expr->items as $item) {
            if ($item->unpack || $item->key === null) {
                continue;
            }

            try {
                $propertyName = $evaluator->evaluateSilently($item->key);
                $rules        = $evaluator->evaluateSilently($item->value);
            } catch (ConstExprEvaluationException) {
                continue;
            }

            if (! is_string($propertyName) || (! is_string($rules) && ! is_array($rules))) {
                continue;
            }

            $flatRules[$propertyName] = ValidationRuleFactory::make($rules);
        }

        return RuleTreeBuilder::build($flatRules);
    }
}

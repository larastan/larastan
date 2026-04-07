<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\Validation\ValidationRule;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\NodeFinder;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\TypeCombinator;

use function array_key_exists;

/** @internal */
final class FormRequestHelper
{
    /** @var array<class-string<FormRequest>, array<string, ValidationRule>> */
    private array $properties = [];

    public function __construct(
        private TypeStringResolver $stringResolver,
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

    public function getProperty(ClassReflection $classReflection, string $propertyName): mixed
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        $valType = $this->properties[$className][$propertyName];

        $type = $this->stringResolver->resolve($valType->type);

        if ($valType->nullable) {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }

    /** @return array<string, ValidationRule> */
    private function parseProperties(ClassReflection $classReflection): array
    {
        /** @var string $fileName */
        $fileName = $classReflection->getFileName();

        $stmts = $this->parser->parseFile($fileName);

        $castsMethodNode = (new NodeFinder())->findFirst($stmts, static function (Node $node): bool {
            return $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === 'rules';
        });

        if ($castsMethodNode === null) {
            return [];
        }

        /** @var Node\Stmt\Return_|null $returnNode */
        $returnNode = (new NodeFinder())->findFirstInstanceOf($castsMethodNode, Node\Stmt\Return_::class);

        if ($returnNode === null) {
            return [];
        }

        if (! $returnNode->expr instanceof Array_) {
            return [];
        }

        $realArray = (new ConstExprEvaluator(static fn (Expr $expr) => null))->evaluateSilently($returnNode->expr);

        $return = [];

        foreach ($realArray as $propertyName => $rules) {
            $type = ValidationRuleFactory::make($rules);

            $return[$propertyName] = $type;
        }

        return $return;
    }
}

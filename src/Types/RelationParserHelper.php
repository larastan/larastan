<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Generic\TemplateTypeMap;

use function array_map;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function method_exists;

class RelationParserHelper
{
    public function __construct(private Parser $parser, private ScopeFactory $scopeFactory)
    {
    }

    /** @return list<string> */
    public function findModelsInRelationMethod(
        MethodReflection $methodReflection,
    ): array {
        if (method_exists($methodReflection, 'getDeclaringTrait') && $methodReflection->getDeclaringTrait() !== null) {
            $fileName = $methodReflection->getDeclaringTrait()->getFileName();
        } else {
            $fileName = $methodReflection
                ->getDeclaringClass()
                ->getNativeReflection()
                ->getMethod($methodReflection->getName())
                ->getFileName();
        }

        if ($fileName === false || $fileName === null) {
            return [];
        }

        $fileStmts = $this->parser->parseFile($fileName);

        /** @var Node\Stmt\ClassMethod|null $relationMethod */
        $relationMethod = $this->findMethod($methodReflection->getName(), $fileStmts);

        if ($relationMethod === null) {
            return [];
        }

        /** @var Node\Stmt\Return_|null $returnStmt */
        $returnStmt = $this->findReturn($relationMethod);

        if ($returnStmt === null || ! $returnStmt->expr instanceof MethodCall) {
            return [];
        }

        $methodCall = $returnStmt->expr;

        while ($methodCall->var instanceof MethodCall) {
            $methodCall = $methodCall->var;
        }

        if (count($methodCall->getArgs()) < 1) {
            return [];
        }

        $scope = $this->scopeFactory->create(ScopeContext::create($fileName));

        $methodScope = $scope
            ->enterClass($methodReflection->getDeclaringClass())
            ->enterClassMethod($relationMethod, TemplateTypeMap::createEmpty(), [], null, null, null, false, false, false);

        $isThroughRelation = false;

        if ($methodCall->name instanceof Node\Identifier) {
            $isThroughRelation = in_array($methodCall->name->toString(), ['hasManyThrough', 'hasOneThrough'], strict: true);
        }

        $args = array_slice($methodCall->getArgs(), 0, $isThroughRelation ? 2 : 1);

        return array_map(static function ($arg) use ($methodScope): string {
            $argType     = $methodScope->getType($arg->value);
            $returnClass = Model::class;

            if ($argType->isClassStringType()->yes()) {
                $classNames = $argType->getClassStringObjectType()->getObjectClassNames();

                if (count($classNames) === 1) {
                    $returnClass = $classNames[0];
                }
            }

            return $returnClass;
        }, array_values($args));
    }

    private function findMethod(string $method, mixed $statements): Node|null
    {
        return (new NodeFinder())->findFirst($statements, static function (Node $node) use ($method) {
            return $node instanceof Node\Stmt\ClassMethod
                && $node->name->toString() === $method;
        });
    }

    private function findReturn(Node\Stmt\ClassMethod $relationMethod): Node|null
    {
        /** @var Node[] $statements */
        $statements = $relationMethod->stmts;

        return (new NodeFinder())->findFirstInstanceOf($statements, Node\Stmt\Return_::class);
    }
}

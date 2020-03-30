<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;
use PHPStan\Parser\CachedParser;

class RelationParserHelper
{
    /** @var CachedParser */
    private $parser;

    public function __construct(CachedParser $parser)
    {
        $this->parser = $parser;
    }

    public function findRelatedModelInRelationMethod(string $fileName, string $methodName): ?string
    {
        $fileStmts = $this->parser->parseFile($fileName);

        /** @var Node\Stmt\ClassMethod|null $relationMethod */
        $relationMethod = $this->findMethod($methodName, $fileStmts);

        if ($relationMethod === null) {
            return null;
        }

        /** @var Node\Stmt\Return_|null $returnStmt */
        $returnStmt = $this->findReturn($relationMethod);

        if ($returnStmt === null || ! $returnStmt->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $returnStmt->expr;

        if ($returnStmt->expr->var instanceof MethodCall) {
            $methodCall = $returnStmt->expr->var;
        }

        if (count($methodCall->args) < 1) {
            return null;
        }

        $argValue = $methodCall->args[0]->value;

        if ($argValue instanceof Node\Expr\ClassConstFetch && $argValue->class instanceof Node\Name) {
            return $argValue->class->toString();
        }

        if ($argValue instanceof Node\Scalar\String_) {
            return $argValue->value;
        }

        return null;
    }

    /**
     * @param string $method
     * @param mixed $statements
     * @return Node|null
     */
    private function findMethod(string $method, $statements): ?Node
    {
        return (new NodeFinder)->findFirst($statements, static function (Node $node) use ($method) {
            return $node instanceof Node\Stmt\ClassMethod
                && $node->name->toString() === $method;
        });
    }

    private function findReturn(Node\Stmt\ClassMethod $relationMethod): ?Node
    {
        /** @var Node[] $statements */
        $statements = $relationMethod->stmts;

        return (new NodeFinder)->findFirstInstanceOf($statements, Node\Stmt\Return_::class);
    }
}

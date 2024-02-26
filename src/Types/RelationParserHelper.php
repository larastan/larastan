<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Generic\TemplateTypeMap;

use function count;
use function method_exists;

class RelationParserHelper
{
    public function __construct(private Parser $parser, private ScopeFactory $scopeFactory, private ReflectionProvider $reflectionProvider)
    {
    }

    public function findRelatedModelInRelationMethod(
        MethodReflection $methodReflection,
    ): string|null {
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
            return null;
        }

        $fileStmts = $this->parser->parseFile($fileName);

        /** @var Node\Stmt\ClassMethod|null $relationMethod */
        $relationMethod = $this->findMethod($methodReflection->getName(), $fileStmts);

        if ($relationMethod === null) {
            return null;
        }

        /** @var Node\Stmt\Return_|null $returnStmt */
        $returnStmt = $this->findReturn($relationMethod);

        if ($returnStmt === null || ! $returnStmt->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $returnStmt->expr;

        while ($methodCall->var instanceof MethodCall) {
            $methodCall = $methodCall->var;
        }

        if (count($methodCall->getArgs()) < 1) {
            return null;
        }

        $scope = $this->scopeFactory->create(ScopeContext::create($fileName));

        $methodScope = $scope
            ->enterClass($methodReflection->getDeclaringClass())
            ->enterClassMethod($relationMethod, TemplateTypeMap::createEmpty(), [], null, null, null, false, false, false);

        $argType     = $methodScope->getType($methodCall->getArgs()[0]->value);
        $returnClass = null;

        $constantStrings = $argType->getConstantStrings();

        if (count($constantStrings) === 1) {
            $returnClass = $constantStrings[0]->getValue();
        }

        if ($argType->isClassStringType()->yes()) {
            $modelType = $argType->getClassStringObjectType();

            $classNames = $modelType->getObjectClassNames();

            if (count($classNames) !== 1) {
                return null;
            }

            $returnClass = $classNames[0];
        }

        if ($returnClass === null) {
            return null;
        }

        return $this->reflectionProvider->hasClass($returnClass) ? $returnClass : null;
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

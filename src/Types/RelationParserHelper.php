<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
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
    public function __construct(
        private Parser $parser,
        private ScopeFactory $scopeFactory,
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function findRelatedModelInRelationMethod(MethodReflection $methodReflection): string|null
    {
        $fileName = $this->getFileNameContainingMethod($methodReflection);

        if ($fileName === null) {
            return null;
        }

        $relationMethod = $this->findRelationMethod($methodReflection, $fileName);

        if ($relationMethod === null) {
            return null;
        }

        $returnStatement = $this->findReturn($relationMethod);

        if ($returnStatement === null || ! $returnStatement->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $returnStatement->expr;

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

    public function isRelationWithDefault(MethodReflection $methodReflection): bool
    {
        $fileName = $this->getFileNameContainingMethod($methodReflection);

        if ($fileName === null) {
            return false;
        }

        $relationMethod = $this->findRelationMethod($methodReflection, $fileName);

        if ($relationMethod === null) {
            return false;
        }

        $returnStatement = $this->findReturn($relationMethod);

        if ($returnStatement === null || ! $returnStatement->expr instanceof MethodCall) {
            return false;
        }

        $methodCall = $returnStatement->expr;

        while ($methodCall instanceof MethodCall) {
            if (
                $methodCall->name instanceof Node\Identifier
                && $methodCall->name->toString() === 'withDefault'
            ) {
                return true;
            }

            $methodCall = $methodCall->var;
        }

        return false;
    }

    /** @param  array<int, Node> $statements */
    private function findMethod(string $method, array $statements): ClassMethod|null
    {
        /** @var ClassMethod|null $node */
        $node = (new NodeFinder())->findFirst($statements, static function (Node $node) use ($method) {
            return $node instanceof ClassMethod
                && $node->name->toString() === $method;
        });

        return $node;
    }

    private function findReturn(ClassMethod $relationMethod): Return_|null
    {
        /** @var Node[] $statements */
        $statements = $relationMethod->stmts;

        /** @var Return_|null $node */
        $node = (new NodeFinder())->findFirstInstanceOf($statements, Return_::class);

        return $node;
    }

    private function getFileNameContainingMethod(MethodReflection $methodReflection): string|null
    {
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

        return $fileName;
    }

    private function findRelationMethod(
        MethodReflection $methodReflection,
        string $fileName,
    ): ClassMethod|null {
        return $this->findMethod(
            $methodReflection->getName(),
            $this->parser->parseFile($fileName),
        );
    }
}

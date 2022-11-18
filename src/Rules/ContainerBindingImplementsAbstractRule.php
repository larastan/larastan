<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use NunoMaduro\Larastan\Collectors\ContainerBindingsCollector;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<CollectedDataNode> */
class ContainerBindingImplementsAbstractRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private TypeStringResolver $typeStringResolver
    ) {
    }

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param  CollectedDataNode  $node
     * @param  Scope  $scope
     * @return array<int, RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        /** @var array<string, list<array{string, bool, ?string, int}>> $containerBindingsData */
        $containerBindingsData = $node->get(ContainerBindingsCollector::class);

        foreach ($containerBindingsData as $file => $declarations) {
            foreach ($declarations as [$bindingId, $isClassBinding, $concreteTypeString, $line]) {
                // No binding was given, skip
                if (! $concreteTypeString) {
                    continue;
                }

                // If the binding ID is not a valid class-string, skip (e.g 'auth.driver')
                if (! $isClassBinding || ! $this->reflectionProvider->hasClass($bindingId)) {
                    continue;
                }

                $abstractType = $this->typeStringResolver->resolve($bindingId);
                $concreteType = $this->typeStringResolver->resolve($concreteTypeString);

                if (! $abstractType->isSuperTypeOf($concreteType)->yes()) {
                    $errors[] = $this->getRuleError($bindingId, $concreteTypeString, $file, $line);
                }
            }
        }

        return $errors;
    }

    private function getRuleError(
        string $astractType,
        string $concreteType,
        string $file,
        int $line
    ): \PHPStan\Rules\RuleError {
        $message = sprintf('Container binding of type %s does not implement %s.', $concreteType, $astractType);

        return RuleErrorBuilder::message($message)
            ->identifier('rules.containerBindingImplementsAbstract')
            ->line($line)
            ->file($file)
            ->build();
    }
}

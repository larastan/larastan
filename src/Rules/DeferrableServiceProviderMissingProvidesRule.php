<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

/**
 * @implements Rule<InClassNode>
 */
class DeferrableServiceProviderMissingProvidesRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @throws \PHPStan\ShouldNotHappenException
     * @throws \PHPStan\Reflection\MissingMethodFromReflectionException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        // This rule is only applicable to deferrable serviceProviders
        if (! $classReflection->isSubclassOf(ServiceProvider::class) || ! $classReflection->implementsInterface(DeferrableProvider::class)) {
            return [];
        }

        if (! $classReflection->hasNativeMethod('provides')) {
            throw new ShouldNotHappenException('If this scenario happens, the "provides" method is removed from the base Laravel ServiceProvider and this rule can be removed.');
        }

        $method = $classReflection->getNativeMethod('provides');

        // The provides method is overwritten somewhere in the class hierarchy
        if ($method->getDeclaringClass()->getName() !== ServiceProvider::class) {
            return [];
        }

        return [
            RuleErrorBuilder::message('ServiceProviders that implement the "DeferrableProvider" interface should implement the "provides" method that returns an array of strings or class-strings')
                ->line($node->getLine())
                ->build(),
        ];
    }
}

<?php
declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use PhpParser\Node;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

class DeferrableServiceProviderMissingProvidesRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var InClassNode $node */
        $classReflection = $node->getClassReflection();
        if ($classReflection->isSubclassOf(ServiceProvider::class) === false || $classReflection->implementsInterface(DeferrableProvider::class) === false) {
            return []; // This rule is only applicable to deferrable serviceProviders
        }

        if ($classReflection->hasNativeMethod('provides') === false) {
            throw new ShouldNotHappenException('If this scenario happens, the "provides" method is removed from the base laravel serviceProvider and this rule can be removed.');
        }

        $method = $classReflection->getMethod('provides', new OutOfClassScope());
        if ($method->getDeclaringClass()->getName() !== ServiceProvider::class) {
            return []; // The provides method is overwritten somewhere in the class hierarchy
        }

        return [
            RuleErrorBuilder::message('ServiceProviders that implement the "DeferrableProvider" interface should implement the "provides" method that returns an array of strings or class-strings')
                ->line($node->getLine())
                ->build(),
        ];
    }
}

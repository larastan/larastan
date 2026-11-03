<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Octane;

use Illuminate\Support\ServiceProvider;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;

use function is_array;
use function is_int;
use function is_string;

/**
 * Collect literal public service-provider maps without executing providers.
 * The container registers these maps after ServiceProvider::register().
 *
 * @internal
 *
 * @implements Collector<InClassNode, list<array{string, string, string, int}>>
 */
final class ServiceProviderBindingCollector implements Collector
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /** @return list<array{string, string, string, int}>|null */
    public function processNode(Node $node, Scope $scope): array|null
    {
        $class = $node->getClassReflection();

        if ($class->isInterface() || $class->isAbstract()) {
            return null;
        }

        $native = $class->getNativeReflection();

        if (! $native->isSubclassOf(ServiceProvider::class)) {
            return null;
        }

        $defaults = $native->getDefaultProperties();
        $found    = [];

        foreach (['bindings' => 'bind', 'singletons' => 'singleton'] as $propertyName => $method) {
            if (! $native->hasProperty($propertyName) || ! $native->getProperty($propertyName)->isPublic()) {
                continue;
            }

            $map = $defaults[$propertyName] ?? null;

            if (! is_array($map)) {
                continue;
            }

            foreach ($map as $abstract => $concrete) {
                // Application::register() accepts [Foo::class] in $singletons.
                if ($propertyName === 'singletons' && is_int($abstract)) {
                    $abstract = $concrete;
                }

                if (! is_string($abstract) || ! is_string($concrete)) {
                    continue;
                }

                $abstractName = $this->canonicalClass($abstract);
                $concreteName = $this->canonicalClass($concrete);

                if ($abstractName === null || $concreteName === null) {
                    continue;
                }

                $found[] = [$method, $abstractName, $concreteName, $node->getStartLine()];
            }
        }

        return $found === [] ? null : $found;
    }

    private function canonicalClass(string $name): string|null
    {
        return $this->reflectionProvider->hasClass($name)
            ? $this->reflectionProvider->getClass($name)->getName()
            : null;
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Octane;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function array_key_exists;
use function array_key_first;
use function count;
use function implode;
use function in_array;

/**
 * Whole-analysis check: a file-local rule cannot know whether an explicit
 * binding elsewhere overrides the lifetime of a constructor dependency.
 *
 * @implements Rule<CollectedDataNode>
 */
final class SingletonLifetimeRule implements Rule
{
    public function __construct(
        private ScopedDependencyDetector $detector,
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var array<string, list<array{method: string, concrete: string|null, file: string, line: int}>> $registrations */
        $registrations = [];

        foreach ($node->get(ContainerBindingCollector::class) as $file => $rows) {
            foreach ($rows as [$method, $abstract, $concrete, $line]) {
                $registrations[$abstract][] = [
                    'method' => $method,
                    'concrete' => $concrete,
                    'file' => $file,
                    'line' => $line,
                ];
            }
        }

        /** @var array<string, array{lifetime: 'scoped'|'singleton'|'bind'|'unknown', concrete: string|null}> $bindings */
        $bindings = [];

        foreach ($registrations as $abstract => $rows) {
            $methods   = [];
            $concretes = [];

            foreach ($rows as $row) {
                $methods[$row['method']]           = true;
                $concretes[$row['concrete'] ?? ''] = true;
            }

            // A registration made in another provider may execute in either
            // order. Do not claim to know its final lifetime or implementation.
            if (count($methods) !== 1 || count($concretes) !== 1) {
                $bindings[$abstract] = ['lifetime' => 'unknown', 'concrete' => null];
                continue;
            }

            $method   = array_key_first($methods);
            $concrete = $rows[0]['concrete'];

            $lifetime = match ($method) {
                'scoped', 'scopedif' => 'scoped',
                'singleton', 'singletonif' => 'singleton',
                'bind' => 'bind',
                default => 'unknown', // instance(): constructor is not available
            };

            $bindings[$abstract] = ['lifetime' => $lifetime, 'concrete' => $concrete];
        }

        /** @var array<string, true> $declaredSingletons */
        $declaredSingletons = [];
        /** @var list<RuleError> $errors */
        $errors = [];
        /** @var array<string, non-empty-list<string>|null> $cachedPaths */
        $cachedPaths = [];

        foreach ($node->get(SingletonAttributeCollector::class) as $file => $rows) {
            foreach ($rows as [$name, $line]) {
                $declaredSingletons[$name] = true;

                $lifetime = $bindings[$name]['lifetime'] ?? null;

                if (in_array($lifetime, ['scoped', 'unknown'], true) || ! $this->reflectionProvider->hasClass($name)) {
                    continue;
                }

                $class = $this->reflectionProvider->getClass($name);
                $path  = $this->findPath($class, $bindings, $cachedPaths);

                if ($path === null) {
                    continue;
                }

                $errors[] = $this->diagnostic($class, $path, $file, $line, true);
            }
        }

        foreach ($registrations as $abstract => $rows) {
            if (($bindings[$abstract]['lifetime'] ?? null) !== 'singleton') {
                continue;
            }

            foreach ($rows as $row) {
                if (! in_array($row['method'], ['singleton', 'singletonif'], true)) {
                    continue;
                }

                $concrete = $row['concrete'];

                if ($concrete === null || ! $this->reflectionProvider->hasClass($concrete)) {
                    continue;
                }

                $class = $this->reflectionProvider->getClass($concrete);

                if ($class->isInterface() || $class->isAbstract() || isset($declaredSingletons[$concrete])) {
                    continue;
                }

                $path = $this->findPath($class, $bindings, $cachedPaths);

                if ($path === null) {
                    continue;
                }

                $errors[] = $this->diagnostic($class, $path, $row['file'], $row['line'], false);
            }
        }

        return $errors;
    }

    /**
     * @param array<string, array{lifetime: 'scoped'|'singleton'|'bind'|'unknown', concrete: string|null}> $bindings
     * @param array<string, non-empty-list<string>|null>                                                   $cachedPaths
     *
     * @return non-empty-list<string>|null
     */
    private function findPath(ClassReflection $class, array $bindings, array &$cachedPaths): array|null
    {
        $name = $class->getName();

        if (! array_key_exists($name, $cachedPaths)) {
            $cachedPaths[$name] = $this->detector->findCaptiveDependency($class, $bindings);
        }

        return $cachedPaths[$name];
    }

    /** @param non-empty-list<string> $path */
    private function diagnostic(ClassReflection $class, array $path, string $file, int $line, bool $attribute): RuleError
    {
        return RuleErrorBuilder::message('Singleton ' . $class->getName() . ' has a constructor dependency on a scoped service: ' . implode(' -> ', $path) . '.')
            ->identifier('larastan.octaneCaptiveScopedDependency')
            ->tip($attribute
                ? 'Remove #[Singleton] or pass request-scoped information at call time instead of retaining it.'
                : 'Register the owner as scoped(), or resolve the current context when handling each request/job.')
            ->file($file)
            ->line($line)
            ->build();
    }
}

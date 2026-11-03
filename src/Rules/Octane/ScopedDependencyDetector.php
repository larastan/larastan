<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Octane;

use Illuminate\Http\Request;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\TypeCombinator;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

use function array_slice;
use function class_exists;
use function count;
use function str_contains;
use function str_replace;
use function strcasecmp;

/**
 * Conservative counterpart to .NET DI scope validation.
 *
 * Only required constructor-injected object dependencies with an
 * unambiguous class type are followed, including nullable required classes.
 * Runtime factories, unresolved interface bindings and dynamic container
 * aliases are intentionally not guessed.
 *
 * @internal
 */
final class ScopedDependencyDetector
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function isSingleton(ClassReflection $class): bool
    {
        return $this->hasAttribute($class, 'Illuminate\Container\Attributes\Singleton');
    }

    /**
     * Follow the actual constructed concrete behind a singleton abstract.
     *
     * @param array<string, array{lifetime: 'scoped'|'singleton'|'bind'|'unknown', concrete: string|null}> $bindings
     *
     * @return non-empty-list<string>|null
     */
    public function findCaptiveDependencyForRoot(ClassReflection $root, array $bindings): array|null
    {
        $current = $root;
        $path    = [$root->getName()];
        $seen    = [$root->getName() => true];

        while (true) {
            $binding = $bindings[$current->getName()] ?? null;

            if ($binding === null) {
                break;
            }

            // An opaque factory, an instance or a conflicting registration
            // does not establish which constructor the container will call.
            if ($binding['lifetime'] === 'unknown' || $binding['concrete'] === null) {
                return null;
            }

            $concrete = $binding['concrete'];

            if ($concrete === $current->getName()) {
                break;
            }

            if (
                isset($seen[$concrete])
                || ! $this->reflectionProvider->hasClass($concrete)
            ) {
                return null;
            }

            $current = $this->reflectionProvider->getClass($concrete);

            if ($current->isInterface() || $current->isAbstract()) {
                return null;
            }

            $path[]                    = $current->getName();
            $seen[$current->getName()] = true;

            // Laravel resolves distinct concretes via Container::resolve();
            // their native scoped lifetime still applies to that dependency.
            if ($this->isScoped($current, $bindings[$current->getName()] ?? null)) {
                return $path;
            }
        }

        $suffix = $this->findCaptiveDependency($current, $bindings);

        if ($suffix === null) {
            return null;
        }

        /** @var non-empty-list<string> $result */
        $result = [...array_slice($path, 0, -1), ...$suffix];

        return $result;
    }

    /**
     * @param array<string, array{lifetime: 'scoped'|'singleton'|'bind'|'unknown', concrete: string|null}> $bindings
     *
     * @return non-empty-list<string>|null
     */
    public function findCaptiveDependency(ClassReflection $class, array $bindings = []): array|null
    {
        /** @var list<array{class: ClassReflection, path: non-empty-list<string>}> $queue */
        $queue = [['class' => $class, 'path' => [$class->getName()]]];

        /** @var array<string, true> $seen */
        $seen = [$class->getName() => true];

        // Breadth-first traversal gives the shortest statically known chain.
        // Unlike a depth-first global visited set, a class cannot be visited
        // first through a longer path and hide a shorter valid dependency.
        // Distinct visited classes and path length are strictly bounded.
        for ($index = 0; $index < count($queue); $index++) {
            $current = $queue[$index]['class'];
            $path    = $queue[$index]['path'];

            // The root is a singleton by definition of this validation.
            // An explicitly bound singleton may also carry #[Scoped];
            // inspect its dependencies, never report the root as captive.
            if (! $current->hasNativeMethod('__construct')) {
                continue;
            }

            $variants = $current->getNativeMethod('__construct')->getVariants();

            if (count($variants) !== 1) {
                continue;
            }

            foreach ($variants[0]->getParameters() as $parameter) {
                // An optional class uses its default only when it is not bound
                // to the container. Laravel binds the current HTTP Request on
                // each request, so even ?Request $request = null is injected.
                // Other optional class bindings can be dynamic: skip those.
                if ($parameter->isVariadic()) {
                    continue;
                }

                $type = TypeCombinator::removeNull($parameter->getType());

                if (! $type->isObject()->yes()) {
                    continue;
                }

                $dependencies = $type->getObjectClassReflections();

                if (count($dependencies) !== 1) {
                    continue;
                }

                $dependency     = $dependencies[0];
                $dependencyName = $dependency->getName();
                $binding        = $bindings[$dependencyName] ?? null;

                // Conflicting or dynamically materialised bindings have no
                // statically reliable lifetime or constructor.
                if (($binding['lifetime'] ?? null) === 'unknown') {
                    continue;
                }

                // Laravel injects optional class parameters only when the
                // abstract has a binding. The two Request aliases are bound
                // by the HTTP application independently of user registration.
                if (
                    $parameter->isOptional()
                    && $dependencyName !== Request::class
                    && $dependencyName !== SymfonyRequest::class
                    && $binding === null
                ) {
                    continue;
                }

                if ($this->isScoped($dependency, $binding)) {
                    return [...$path, $dependencyName];
                }

                // A factory or supplied instance may return a completely different
                // implementation. Only constructor-autowired concrete classes can
                // be followed; the abstract's constructor proves nothing here.
                // Keep the lifetime check above: scoped() with a factory still
                // creates a request/job-scoped service.
                if ($binding !== null && $binding['concrete'] === null) {
                    continue;
                }

                $target   = $dependency;
                $nextPath = [...$path, $dependencyName];

                // A known interface-to-concrete binding is traversable even
                // when the interface has no constructor of its own. The
                // concrete's own attributes remain relevant because Laravel
                // resolves a distinct concrete through Container::resolve().
                $concreteName = $binding['concrete'] ?? null;

                if ($concreteName !== null && $concreteName !== $dependencyName) {
                    if (! $this->reflectionProvider->hasClass($concreteName)) {
                        continue;
                    }

                    $target     = $this->reflectionProvider->getClass($concreteName);
                    $nextPath[] = $concreteName;

                    // A distinct concrete is resolved through Container::resolve()
                    // (not build()), so its native scoped lifetime still applies.
                    if ($this->isScoped($target, $bindings[$concreteName] ?? null)) {
                        return $nextPath;
                    }
                }

                if ($target->isInterface() || $target->isAbstract()) {
                    continue;
                }

                $file = $target->getFileName();

                // Do not guess transitive dependencies through vendor code
                // or PHP extensions.
                if ($file === null || str_contains(str_replace('\\', '/', $file), '/vendor/')) {
                    continue;
                }

                $name = $target->getName();

                if (isset($seen[$name])) {
                    continue;
                }

                $seen[$name] = true;
                $queue[]     = ['class' => $target, 'path' => $nextPath];
            }
        }

        return null;
    }

    /** @param array{lifetime: 'scoped'|'singleton'|'bind'|'unknown', concrete: string|null}|null $binding */
    private function isScoped(ClassReflection $class, array|null $binding): bool
    {
        // These exact classes are Laravel's active request aliases in an HTTP
        // application. Ordinary user-defined Request subclasses are not.
        if ($class->getName() === Request::class || $class->getName() === SymfonyRequest::class) {
            return true;
        }

        if ($binding !== null) {
            if ($binding['lifetime'] === 'scoped') {
                return true;
            }

            if ($binding['lifetime'] === 'singleton') {
                // Explicit singleton() overrides #[Scoped] for this abstract.
                return false;
            }
        }

        // Container::isShared() does not infer attributes for an interface:
        // class_exists() is false for interfaces. An explicit scoped()
        // registration is needed to establish that lifetime.
        return ! $class->isInterface()
            && ! $this->isSingleton($class)
            && $this->hasAttribute($class, 'Illuminate\Container\Attributes\Scoped');
    }

    private function hasAttribute(ClassReflection $class, string $attribute): bool
    {
        // Laravel 11 does not define native lifetime attribute classes.
        // Reflection exposes unresolved source annotations anyway, but these
        // cannot affect Container::isShared() at runtime.
        if (! class_exists($attribute)) {
            return false;
        }

        // Attribute names are case-insensitive in PHP. Compare resolved
        // names exactly as Laravel's named ReflectionClass lookup does.
        foreach ($class->getNativeReflection()->getAttributes() as $reflectionAttribute) {
            if (strcasecmp($reflectionAttribute->getName(), $attribute) === 0) {
                return true;
            }
        }

        return false;
    }
}

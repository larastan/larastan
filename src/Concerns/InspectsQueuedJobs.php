<?php

declare(strict_types=1);

namespace Larastan\Larastan\Concerns;

use PhpParser\Node\Name;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

trait InspectsQueuedJobs
{
    /**
     * Whether the class uses the given trait, directly or through a parent class
     * or another trait.
     *
     * `getTraits(true)` resolves traits used by the class, by its parent classes,
     * and by those traits, so an inherited trait counts.
     */
    private function usesTrait(ClassReflection $classReflection, string $traitName): bool
    {
        foreach ($classReflection->getTraits(true) as $trait) {
            if ($trait->getName() === $traitName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the class can be dispatched as it stands. Interfaces and traits are
     * not classes, and an abstract class is never dispatched directly: a concrete
     * subclass supplies (or inherits) whatever the rule asks for, and is checked
     * on its own.
     */
    private function isDispatchableClass(ClassReflection $classReflection): bool
    {
        return ! $classReflection->isInterface()
            && ! $classReflection->isTrait()
            && ! $classReflection->isAbstract();
    }

    /**
     * Whether the static call target names the given facade. Matches the facade
     * itself, a subclass of it, and the root namespace alias Laravel registers
     * for it (`\Bus`, `\DB`, ...).
     */
    private function isFacade(Name $class, string $facade, string $alias): bool
    {
        $className = $class->toString();

        if ($className === $facade || $className === $alias) {
            return true;
        }

        return (new ObjectType($facade))->isSuperTypeOf(new ObjectType($className))->yes();
    }
}

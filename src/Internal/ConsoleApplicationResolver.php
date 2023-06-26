<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Internal;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

use function get_class;

/** @internal */
final class ConsoleApplicationResolver
{
    private ?Application $application = null;

    /**
     * @return Command[]
     */
    public function findCommands(ClassReflection $classReflection): array
    {
        $consoleApplication = $this->getApplication();

        $classType = new ObjectType($classReflection->getName());

        if (! (new ObjectType('Illuminate\Console\Command'))->isSuperTypeOf($classType)->yes()) {
            return [];
        }

        $commands = [];

        foreach ($consoleApplication->all() as $name => $command) {
            if (! $classType->isSuperTypeOf(new ObjectType(get_class($command)))->yes()) {
                continue;
            }

            $commands[$name] = $command;
        }

        return $commands; // @phpstan-ignore-line
    }

    private function getApplication(): Application
    {
        if ($this->application === null) {
            $this->application = new Application(app(Container::class), app(Dispatcher::class), app()->version());
        }

        return $this->application;
    }
}

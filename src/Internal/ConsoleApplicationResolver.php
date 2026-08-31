<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use PHPStan\Reflection\ClassReflection;

use function app;
use function array_key_exists;
use function is_a;

/** @internal */
final class ConsoleApplicationResolver
{
    private Application|null $application = null;

    /** @var array<string, Command[]> */
    private array $commandsByClass = [];

    /** @return Command[] */
    public function findCommands(ClassReflection $classReflection): array
    {
        $className = $classReflection->getName();

        if (array_key_exists($className, $this->commandsByClass)) {
            return $this->commandsByClass[$className];
        }

        if (! $classReflection->is(Command::class)) {
            return $this->commandsByClass[$className] = [];
        }

        $commands = [];

        foreach ($this->getApplication()->all() as $name => $command) {
            if (! is_a($command, $className)) {
                continue;
            }

            $commands[$name] = $command;
        }

        return $this->commandsByClass[$className] = $commands; // @phpstan-ignore-line
    }

    private function getApplication(): Application
    {
        if ($this->application === null) {
            $this->application = new Application(app(Container::class), app(Dispatcher::class), app()->version());
            $this->application->setContainerCommandLoader();
        }

        return $this->application;
    }
}

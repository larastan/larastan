<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Larastan\Larastan\Concerns\HasContainer;
use PHPStan\Reflection\ClassReflection;
use Throwable;

use function app;
use function array_key_exists;
use function is_a;

/** @internal */
final class ConsoleApplicationResolver
{
    use HasContainer;

    private Application|null $application = null;

    /** @var array<mixed>|null */
    private array|null $commands = null;

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

        foreach ($this->getCommands() as $name => $command) {
            if (! is_a($command, $className)) {
                continue;
            }

            $commands[$name] = $command;
        }

        return $this->commandsByClass[$className] = $commands; // @phpstan-ignore-line
    }

    /**
     * Commands registered through the console kernel (its `$commands` property and, since Laravel 13.36,
     * `load()` and `command()`) are only added to the kernel's own Artisan application, so ask the kernel.
     *
     * @return array<mixed>
     */
    private function getCommands(): array
    {
        if ($this->commands !== null) {
            return $this->commands;
        }

        $kernel = $this->resolve(Kernel::class);

        if ($kernel instanceof Kernel) {
            try {
                return $this->commands = $kernel->all();
            } catch (Throwable) {
                // The kernel cannot be asked for its commands, e.g. because one of them cannot be resolved
                // in the analysis environment. Fall back to a standalone Artisan application, which only
                // sees the commands registered through Artisan::starting().
            }
        }

        return $this->commands = $this->getApplication()->all();
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

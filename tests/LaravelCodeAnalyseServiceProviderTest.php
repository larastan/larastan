<?php

namespace Tests;

use function get_class;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use NunoMaduro\LaravelCodeAnalyse\Console\CodeAnalyseCommand;
use NunoMaduro\LaravelCodeAnalyse\LaravelCodeAnalyseServiceProvider;

class LaravelCodeAnalyseServiceProviderTest extends TestCase
{
    public function testCommandAddedOnConsole(): void
    {
        $app = $this->createMockApplication();
        $app->method('runningInConsole')
            ->willReturn(true);
        $app->method('runningUnitTests')
            ->willReturn(false);
        (new LaravelCodeAnalyseServiceProvider($app))->register();

        $this->assertContains(CodeAnalyseCommand::class, $this->getCommandClasses());
    }

    public function testCommandNotAddedInHttp(): void
    {
        $app = $this->createMockApplication();
        $app->method('runningInConsole')
            ->willReturn(false);
        $app->method('runningUnitTests')
            ->willReturn(false);
        (new LaravelCodeAnalyseServiceProvider($app))->register();

        $this->assertNotContains(CodeAnalyseCommand::class, $this->getCommandClasses());
    }

    public function testCommandNotAddedInTests(): void
    {
        $app = $this->createMockApplication();
        $app->method('runningInConsole')
            ->willReturn(true);
        $app->method('runningUnitTests')
            ->willReturn(true);
        (new LaravelCodeAnalyseServiceProvider($app))->register();

        $this->assertNotContains(CodeAnalyseCommand::class, $this->getCommandClasses());
    }

    /**
     * Creates a new instance of Laravel Application.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockApplication(): Application
    {
        return $this->createPartialMock(Application::class, ['runningInConsole', 'runningUnitTests']);
    }

    /**
     * @return array
     */
    private function getCommandClasses(): array
    {
        return collect(Artisan::all())->map(
            function ($command) {
                return get_class($command);
            }
        )->toArray();
    }
}

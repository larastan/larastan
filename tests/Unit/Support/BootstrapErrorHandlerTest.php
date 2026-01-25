<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Exception;
use Larastan\Larastan\Support\BootstrapErrorHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Output\BufferedOutput;

use function getcwd;

class BootstrapErrorHandlerTest extends TestCase
{
    public function testHandleUserCodeError(): void
    {
        $output  = new BufferedOutput();
        $handler = new BootstrapErrorHandler($output);

        $exception = new Exception('User code error');

        // Mock getFile to return a path that looks like user code (not in vendor)
        $reflection   = new ReflectionClass($exception);
        $fileProperty = $reflection->getProperty('file');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($exception, getcwd() . '/app/Providers/AppServiceProvider.php');

        $handler->handle($exception);

        $result = $output->fetch();

        $this->assertStringContainsString('Application bootstrap failed', $result);
        $this->assertStringContainsString('PHPStan was unable to bootstrap your application due to an error in your code.', $result);
        $this->assertStringContainsString('Error: User code error', $result);
        $this->assertStringContainsString('Stack trace:', $result);
    }

    public function testHandleFrameworkError(): void
    {
        $output  = new BufferedOutput();
        $handler = new BootstrapErrorHandler($output);

        $exception = new Exception('Framework error');

        // Mock getFile to return a path that looks like framework code (in vendor)
        $reflection   = new ReflectionClass($exception);
        $fileProperty = $reflection->getProperty('file');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($exception, getcwd() . '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php');

        $handler->handle($exception);

        $result = $output->fetch();

        $this->assertStringContainsString('Laravel framework bootstrap failed', $result);
        $this->assertStringContainsString('PHPStan was unable to bootstrap your application because Laravel failed to start.', $result);
        $this->assertStringContainsString('Check your environment variables in the .env file', $result);
        $this->assertStringContainsString('Error: Framework error', $result);
        $this->assertStringContainsString('Stack trace:', $result);
    }

    public function testStackTraceUsesRelativePaths(): void
    {
        $output  = new BufferedOutput();
        $handler = new BootstrapErrorHandler($output);

        $exception = new Exception('Path error');

        $handler->handle($exception);

        $result = $output->fetch();

        $projectRoot = getcwd();
        // The stack trace should not contain the absolute project root if getcwd() worked
        $this->assertStringNotContainsString($projectRoot . '/', $result);
    }

    public function testHandleNoAnsi(): void
    {
        $output  = new BufferedOutput();
        $handler = new BootstrapErrorHandler($output, decorated: false);

        $exception = new Exception('User code error');

        // Mock getFile to return a path that looks like user code
        $reflection   = new ReflectionClass($exception);
        $fileProperty = $reflection->getProperty('file');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($exception, getcwd() . '/app/Providers/AppServiceProvider.php');

        $handler->handle($exception);

        $result = $output->fetch();

        $this->assertStringContainsString('Tip: Fix the misconfiguration', $result);
        $this->assertStringNotContainsString("\u{1F4A1}", $result);
        $this->assertFalse($output->isDecorated());
    }

    public function testHandleAnsi(): void
    {
        $output = new BufferedOutput();
        $output->setDecorated(true);
        $handler = new BootstrapErrorHandler($output, decorated: true);

        $exception = new Exception('User code error');

        // Mock getFile to return a path that looks like user code
        $reflection   = new ReflectionClass($exception);
        $fileProperty = $reflection->getProperty('file');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($exception, getcwd() . '/app/Providers/AppServiceProvider.php');

        $handler->handle($exception);

        $result = $output->fetch();

        $this->assertStringContainsString("\u{1F4A1} Fix the misconfiguration", $result);
        $this->assertStringNotContainsString('Tip: Fix the misconfiguration', $result);
        $this->assertTrue($output->isDecorated());
    }
}

<?php

declare(strict_types=1);

namespace Tests;

use Nette\Neon\Neon;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Console\Kernel;
use NunoMaduro\Larastan\LarastanServiceProvider;
use Symfony\Component\Console\Output\BufferedOutput;

class FeaturesTest extends TestCase
{
    use RegisteredExtensions;

    private $kernel;

    public function setUp(): void
    {
        parent::setUp();

        $app = $this->createApplication();

        (new LarastanServiceProvider($app))->register();

        $this->kernel = $app->make(Kernel::class);

        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan', 0755, true);
        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan/config', 0755, true);
        @File::copy(__DIR__.'/../bootstrap.php', __DIR__.'/../vendor/nunomaduro/larastan/bootstrap.php');
        @File::copy(__DIR__.'/../config/mixins.php', __DIR__.'/../vendor/nunomaduro/larastan/config/mixins.php');
        @File::copy(__DIR__.'/../config/statics.php', __DIR__.'/../vendor/nunomaduro/larastan/config/statics.php');
    }

    public function getFeatures(): array
    {
        $calls = [];
        $baseDir = __DIR__.DIRECTORY_SEPARATOR.'Features'.DIRECTORY_SEPARATOR;

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ((new Finder())->in($baseDir)->files() as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $fullPath = realpath((string) $file);
            $calls[str_replace($baseDir, '', $fullPath)] = [$fullPath];
        }

        return $calls;
    }

    /**
     * @dataProvider getFeatures
     */
    public function testFeatures(string $file): void
    {
        $extension = $this->getExtensionFromFilePath($file);

        $this->analyze($file, false);

        if ($this->extensionIsRegistered($extension)) {
            $configurationPath = $this->disableExtension($extension);

            $this->analyze($file, true, $configurationPath);
        }
    }

    private function analyze(string $file, bool $shouldFail = false, $configurationPath = null)
    {
        $result = $this->kernel->call('code:analyse', [
            '--level' => 'max',
            '--paths' => $file,
            '--bin-path' => __DIR__.'/../vendor/phpstan/phpstan/bin',
            '--autoload-file' => __DIR__.'/../vendor/autoload.php',
            '--error-format' => 'raw',
            '--no-tty' => true,
            '--no-progress' => true,
            '--configuration' => $configurationPath ?? $this->extensionPath
        ], $output = new BufferedOutput);

        if ($configurationPath) {
            File::delete($configurationPath);
        }

        if ($result != $shouldFail) {
            $message = 'Analysis fails, please check '.$file.'.'.PHP_EOL.PHP_EOL.$output->fetch();

            $this->fail($message);
        }

        $this->assertTrue(true);
    }

    public function disableExtension(string $extensionClass): string
    {
        $extensionFileContent = file_get_contents($this->extensionPath);

        $extension = Neon::decode($extensionFileContent);

        foreach ($extension['services'] as $index => $service) {
            if (Str::endsWith($service['class'], $extensionClass)) {
                array_splice($extension['services'], $index, 1);

                break;
            }
        }

        $path = str_replace('.neon', Str::random(16).'.neon', $this->extensionPath);
        file_put_contents($path, Neon::encode($extension, Neon::BLOCK));

        return $path;
    }

    private function extensionIsRegistered(string $extension)
    {
        return in_array('NunoMaduro\\Larastan\\'.$extension, $this->getRegisteredExtensions());
    }

    protected function getExtensionFromFilePath(string $file): string
    {
        $fileWithoutExtension = str_replace('.php', '', $file);
        $extensionClassName = str_replace('/', '\\', $fileWithoutExtension);

        return array_reverse(explode('tests\\Features\\', $extensionClassName))[0];
    }
}

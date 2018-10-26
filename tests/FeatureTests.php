<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Console\Kernel;
use NunoMaduro\Larastan\LarastanServiceProvider;
use Symfony\Component\Console\Output\BufferedOutput;

class FeatureTests extends TestCase
{
    protected function getBasePath()
    {
        return __DIR__.'/testApp';
    }

    public function appFilesProvider()
    {
        $calls = [];
        $baseDir = __DIR__.'/testApp/app/TestFiles/';

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ((new Finder())->in($baseDir)->files() as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $fullPath = (string) $file;
            $key = str_replace($baseDir, '', $fullPath);
            $calls[$key] = [$fullPath, str_replace('.php', '.expected.txt', $fullPath)];
        }

        return $calls;
    }

    /**
     * @dataProvider appFilesProvider
     */
    public function testAlltestAppFiles($file, $expectedFileResult)
    {
        $actualErrors = $this->analyze($file);

        $actualErrors = str_replace($file.':', '', $actualErrors);
        $actualErrors = str_replace($file, '', $actualErrors);
        $actualErrors = trim($actualErrors);

        $expectedErrors = file_exists($expectedFileResult) ? trim(file_get_contents($expectedFileResult)) : '';

        $this->assertSame($expectedErrors, $actualErrors);
    }

    private function analyze(string $file): string
    {
        $app = $this->createApplication();
        (new LarastanServiceProvider($app))->register();
        $kernel = $app->make(Kernel::class);

        $output = new BufferedOutput();

        $kernel->call('code:analyse', [
            '--level' => 'max',
            '--paths' => $file,
            '--bin-path' => __DIR__.'/../vendor/bin',
            '--autoload-file' => __DIR__.'/../vendor/autoload.php',
            '--no-tty' => true,
            '--error-format' => 'raw',
            '--debug' => true,
        ], $output);

        return $output->fetch();
    }
}

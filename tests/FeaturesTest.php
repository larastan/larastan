<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FeaturesTest extends TestCase
{
    public function getFeatures(): array
    {
        $calls = [];
        $baseDir = __DIR__.DIRECTORY_SEPARATOR.'Features'.DIRECTORY_SEPARATOR;

        /** @var SplFileInfo $file */
        foreach ((new Finder())->in($baseDir)->files()->name('*.php') as $file) {
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
        if ($this->analyze($file) === 0) {
            $this->assertTrue(true);
        }
    }

    private function analyze(string $file): int
    {
        if (Str::contains($file, 'Features/Laravel8') && version_compare(app()->version(), '8.0.0', '<')) {
            return 0;
        }

        $result = $this->execLarastan($file);

        if (! $result || $result['totals']['errors'] > 0 || $result['totals']['file_errors'] > 0) {
            $this->fail(json_encode($result, JSON_PRETTY_PRINT));
        }

        return 0;
    }
}

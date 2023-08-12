<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use PHPStan\PhpDoc\StubFilesExtension;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

final class LarastanStubFilesExtension implements StubFilesExtension
{

    /** @var bool */
    private $checkRawLiteralString;

    public function __construct(bool $checkRawLiteralString)
    {
        $this->checkRawLiteralString = $checkRawLiteralString;
    }

    /**
     * @inheritDoc
     */
    public function getFiles(): array
    {

        $baseDirectories = [__DIR__.'/../stubs'];

        if ($this->checkRawLiteralString) {
            $baseDirectories[] = __DIR__.'/../stubs/feature-literal-string';
        }

        $stubDirs = [];

        foreach ($baseDirectories as $baseDirectory) {
            $stubDirs[] = $baseDirectory.'/common';

            $stubDirectories = Finder::create()->directories()->name('/^\d+/')->in($baseDirectory)->depth(0);

            // Include only applicable versions
            $stubDirectories
                ->filter(fn (SplFileInfo $file) => version_compare($file->getFilename(), LARAVEL_VERSION, '<='))
                ->sort(fn (SplFileInfo $a, SplFileInfo $b) => version_compare($a->getFilename(), $b->getFilename()));

            $stubDirs = array_merge($stubDirs, array_keys(iterator_to_array($stubDirectories)));
        }

        $stubFiles = Finder::create()->files()->name('*.stub')->in($stubDirs);

        $files = [];
        foreach ($stubFiles as $stubFile) {
            $files[$stubFile->getRelativePathname()] = $stubFile->getRealPath();
        }

        return array_values($files);
    }
}

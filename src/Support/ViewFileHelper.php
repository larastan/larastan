<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Generator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Larastan\Larastan\Concerns\HasContainer;
use PHPStan\File\FileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use function array_merge;
use function array_values;
use function count;
use function explode;
use function is_dir;
use function iterator_to_array;
use function str_contains;
use function str_replace;

use const DIRECTORY_SEPARATOR;

final class ViewFileHelper
{
    use HasContainer;

    /** @param  list<non-empty-string> $viewDirectories */
    public function __construct(private array $viewDirectories, private FileHelper $fileHelper)
    {
        if (count($viewDirectories) !== 0) {
            return;
        }

        $finder = $this->resolve(ViewFactory::class)->getFinder();

        $viewDirectories = array_merge(
            $finder->getPaths(),
            ...array_values($finder->getHints()),
        );

        $this->viewDirectories = $viewDirectories; // @phpstan-ignore-line
    }

    public function getAllViewFilePaths(): Generator
    {
        foreach ($this->viewDirectories as $viewDirectory) {
            $absolutePath = $this->fileHelper->absolutizePath($viewDirectory);

            if (! is_dir($absolutePath)) {
                continue;
            }

            $views = iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath)),
                    '/\.blade\.php$/i',
                ),
            );

            foreach ($views as $view) {
                yield $view->getPathname();
            }
        }
    }

    public function getAllViewNames(): Generator
    {
        foreach ($this->viewDirectories as $viewDirectory) {
            $absolutePath = $this->fileHelper->absolutizePath($viewDirectory);

            if (! is_dir($absolutePath)) {
                continue;
            }

            $views = iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath)),
                    '/\.blade\.php$/i',
                ),
            );

            foreach ($views as $view) {
                if (str_contains($view->getPathname(), 'views' . DIRECTORY_SEPARATOR . 'vendor') || str_contains($view->getPathname(), 'views' . DIRECTORY_SEPARATOR . 'errors')) {
                    continue;
                }

                $viewName = explode($viewDirectory . DIRECTORY_SEPARATOR, $view->getPathname());

                yield str_replace([DIRECTORY_SEPARATOR, '.blade.php'], ['.', ''], $viewName[1]);
            }
        }
    }
}

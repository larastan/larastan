<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Larastan\Larastan\Collectors\UsedTranslationFacadeCollector;
use Larastan\Larastan\Collectors\UsedTranslationFunctionCollector;
use Larastan\Larastan\Collectors\UsedTranslationTranslatorCollector;
use Larastan\Larastan\Collectors\UsedTranslationViewCollector;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Finder\SplFileInfo;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function in_array;
use function is_array;
use function is_dir;
use function json_decode;
use function lang_path;
use function str_contains;
use function strlen;

use const DIRECTORY_SEPARATOR;

/** @implements Rule<CollectedDataNode> */
final class NoMissingTranslationsRule implements Rule
{
    /** @param string[] $translationDirectories */
    public function __construct(
        private UsedTranslationViewCollector $usedTranslationViewCollector,
        private Filesystem $filesystem,
        private array $translationDirectories,
    ) {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return RuleError[] */
    public function processNode(Node $node, Scope $scope): array
    {
        $paths = $this->translationDirectories ?: [lang_path()];

        /** @var array<string, array{0: string, 1: int}[]>[] $collectors */
        $collectors = [
            $node->get(UsedTranslationFunctionCollector::class),
            $node->get(UsedTranslationTranslatorCollector::class),
            $node->get(UsedTranslationFacadeCollector::class),
            $this->usedTranslationViewCollector->getUsedTranslations(),
        ];

        $availableTranslations = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = $this->filesystem->allFiles($path);

            $translations = array_map(function (SplFileInfo $file): array {
                $translations = [];

                if ($file->getExtension() === 'php') {
                    $prefix = Str::of($file->getRelativePathname())
                        ->explode(DIRECTORY_SEPARATOR)
                        ->slice(1, -1) // Trim locale and filename
                        ->join('/');

                    $root = strlen($prefix) > 0
                        ? $prefix . '/' . $file->getFilenameWithoutExtension()
                        : $file->getFilenameWithoutExtension();

                    $array = $this->filesystem->getRequire($file->getPathname());

                    $translations = array_merge([$root], $this->keys($array, $root));
                } elseif ($file->getExtension() === 'json') {
                    $translations = array_keys(
                        json_decode($this->filesystem->get($file->getPathname()), true),
                    );
                }

                return $translations;
            }, $files);

            $availableTranslations = array_merge($availableTranslations, ...$translations);
        }

        $usedTranslations = [];

        foreach ($collectors as $collector) {
            foreach ($collector as $file => $translations) {
                if (! array_key_exists($file, $usedTranslations)) {
                    $usedTranslations[$file] = [];
                }

                $usedTranslations[$file] = array_merge($usedTranslations[$file], $translations);
            }
        }

        $errors = [];

        foreach ($usedTranslations as $file => $translations) {
            foreach ($translations as [$translation, $line]) {
                if (in_array($translation, $availableTranslations, true) || str_contains($translation, '::')) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message('Translation "' . $translation . '" has not been found.')
                    ->file($file)
                    ->line($line)
                    ->identifier('larastan.missingTranslations')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return string[]
     */
    protected function keys(array $array, string $prefix): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix . '.' . $key;

            $results[] = $newKey;

            if (! is_array($value)) {
                continue;
            }

            $results = array_merge($results, $this->keys($value, $newKey));
        }

        return $results;
    }
}

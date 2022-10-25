<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use NunoMaduro\Larastan\Collectors\UsedTranslationFunctionsCollector;
use NunoMaduro\Larastan\Collectors\UsedTranslationsInViewsCollector;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function collect;
use function PHPStan\dumpType;

/** @implements Rule<CollectedDataNode> */
final class UnusedTranslationsRule implements Rule
{
    /** @var list<string>|null */
    private ?array $usedTranslationsInViews = null;

    /** @var list<string>|null */
    private ?array $allTranslations = null;

    /** @param list<string> $translationDirectories */
    public function __construct(private UsedTranslationsInViewsCollector $usedTranslationsInViewsCollector, private array $translationDirectories, private Filesystem $filesystem)
    {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->usedTranslationsInViews === null) {
            $this->usedTranslationsInViews = $this->usedTranslationsInViewsCollector->getUsedTranslations();
        }

        if ($this->translationDirectories === []) {
            $this->translationDirectories = [lang_path()];
        }

        if ($this->allTranslations === null) {
            foreach ($this->translationDirectories as $translationPath) {
                if (! is_dir($translationPath)) {
                    $this->allTranslations = [];
                    continue;
                }

                $files = collect($this->filesystem->allFiles($translationPath))
                    ->filter(fn ($file) => $file->getExtension() === 'php')
                    ->mapWithKeys(fn ($file) => [$file->getFilenameWithoutExtension() => $this->filesystem->getRequire($file->getPathname())])
                    ->all();

                $this->allTranslations = array_merge(array_keys(Arr::dot($files)), $this->allTranslations ?? []);
            }
        }

        $usedTranslations = collect([
            $node->get(UsedTranslationFunctionsCollector::class),
            $this->usedTranslationsInViews,
        ])->flatten()->unique()->toArray();


        $unusedTranslations = array_diff($this->allTranslations, array_filter($usedTranslations));

        $errors = [];
        foreach ($unusedTranslations as $translation) {
            $errors[] = RuleErrorBuilder::message(sprintf('"%s" translation is not used in the project.', $translation))
                ->file(Str::before($translation, '.') . '.php')
                ->line(0)
                ->build();
        }

        return $errors;
    }
}

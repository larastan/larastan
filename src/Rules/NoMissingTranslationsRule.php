<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\Translator;
use Larastan\Larastan\Collectors\UsedTranslationFacadeCollector;
use Larastan\Larastan\Collectors\UsedTranslationFunctionCollector;
use Larastan\Larastan\Collectors\UsedTranslationTranslatorCollector;
use Larastan\Larastan\Collectors\UsedTranslationViewCollector;
use Larastan\Larastan\Concerns\HasContainer;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function array_key_exists;
use function array_merge;

/** @implements Rule<CollectedDataNode> */
final class NoMissingTranslationsRule implements Rule
{
    use HasContainer;

    public function __construct(private UsedTranslationViewCollector $usedTranslationViewCollector)
    {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return RuleError[] */
    public function processNode(Node $node, Scope $scope): array
    {
        $collectors = [
            $node->get(UsedTranslationFunctionCollector::class),
            $node->get(UsedTranslationTranslatorCollector::class),
            $node->get(UsedTranslationFacadeCollector::class),
            $this->usedTranslationViewCollector->getUsedTranslations(),
        ];

        $usedTranslations = [];

        foreach ($collectors as $collector) {
            foreach ($collector as $file => $translations) {
                if (! array_key_exists($file, $usedTranslations)) {
                    $usedTranslations[$file] = [];
                }

                $usedTranslations[$file] = array_merge($usedTranslations[$file], $translations);
            }
        }

        /** @var Translator $translator */
        $translator = $this->resolve(TranslatorContract::class);

        $errors = [];

        foreach ($usedTranslations as $file => $translations) {
            foreach ($translations as [$translation, $line]) {
                if ($translator->has($translation)) {
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
}

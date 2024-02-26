<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use JsonException;

use function basename;
use function file_get_contents;
use function getenv;
use function is_file;
use function is_string;
use function json_decode;
use function trim;

use const JSON_THROW_ON_ERROR;

/** @internal */
final class ComposerHelper
{
    /** @return array<string, mixed> */
    public static function getComposerConfig(string $root): array|null
    {
        $composerJsonPath = self::getComposerJsonPath($root);

        if (! is_file($composerJsonPath)) {
            return null;
        }

        try {
            $composerJsonContents = @file_get_contents($composerJsonPath);

            if ($composerJsonContents === false) {
                return null;
            }

            return json_decode($composerJsonContents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    private static function getComposerJsonPath(string $root): string
    {
        $envComposer = getenv('COMPOSER');
        $fileName    = is_string($envComposer) ? $envComposer : 'composer.json';

        return $root . '/' . basename(trim($fileName));
    }

    /** @param  array<string, mixed> $composerConfig */
    public static function getVendorDirFromComposerConfig(string $root, array $composerConfig): string
    {
        $vendorDirectory = $composerConfig['config']['vendor-dir'] ?? 'vendor';

        return $root . '/' . trim($vendorDirectory, '/');
    }
}

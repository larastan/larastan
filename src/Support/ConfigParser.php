<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use FilesystemIterator;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\DependencyEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_key_exists;
use function array_slice;
use function config_path;
use function count;
use function explode;
use function glob;
use function implode;
use function is_dir;
use function is_numeric;
use function property_exists;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

final class ConfigParser
{
    /** @var list<string> */
    private array $configPaths = [];

    /**
     * Config file paths keyed like Laravel's config keys: `nested/stripe.php`
     * in a config directory is keyed `nested.stripe`.
     *
     * @var array<string, string>
     */
    private array $configFiles = [];

    /**
     * Key prefixes with config files below them, whose values Laravel merges
     * from several files.
     *
     * @var array<string, true>
     */
    private array $mergedConfigKeys = [];

    /** @var array<string, Type> */
    private array $parsedConfigs = [];

    /** @var array<string, Node\Stmt\Return_> */
    private array $parsedConfigFiles = [];

    /** @var array<string, true> */
    private array $unparsableConfigFiles = [];

    /** @var array<string, list<string>> */
    private array $configFileCandidates = [];

    /** @param list<non-empty-string> $configPaths */
    public function __construct(
        private FileHelper $fileHelper,
        private Parser $parser,
        private FileTypeMapper $fileTypeMapper,
        array $configPaths,
        #[AutowiredParameter]
        private bool $treatPhpDocTypesAsCertain,
    ) {
        foreach ($configPaths as $configPath) {
            $configPath = $this->fileHelper->absolutizePath($configPath);

            // An existing directory is taken literally, so glob characters in its path are not expanded
            foreach ((is_dir($configPath) ? [$configPath] : (glob($configPath) ?: [])) as $directory) {
                $this->configPaths[] = $directory;
            }
        }

        $this->loadConfigFiles();
    }

    /**
     * @param ConstantStringType[]    $constantStrings
     * @param Scope&DependencyEmitter $scope
     *
     * @return Type[]
     */
    public function getTypes(array $constantStrings, Scope $scope): array
    {
        $returnTypes = [];

        foreach ($constantStrings as $constantString) {
            $key = $constantString->getValue();

            // Every analysed file reading the key depends on the config files, parsed or served from the cache.
            foreach ($this->getConfigFileCandidates($key) as $configFilePath) {
                $scope->fileDependency($configFilePath);
            }

            if (array_key_exists($key, $this->parsedConfigs)) {
                $returnTypes[] = $this->parsedConfigs[$key];
                continue;
            }

            if (array_key_exists($key, $this->mergedConfigKeys)) {
                return [];
            }

            $configFile = $this->resolveConfigFile($key);

            if ($configFile === null) {
                return [];
            }

            [$configFileKey, $configKeyParts] = $configFile;

            if (array_key_exists($configFileKey, $this->unparsableConfigFiles)) {
                return [];
            }

            if (array_key_exists($configFileKey, $this->parsedConfigFiles)) {
                $cachedConfigFile = $this->parsedConfigFiles[$configFileKey];
            } else {
                $cachedConfigFile = $this->parseConfigFile($this->configFiles[$configFileKey]);

                // We could not parse the file or couldn't find the return array
                if ($cachedConfigFile === null) {
                    $this->unparsableConfigFiles[$configFileKey] = true;

                    return [];
                }

                $this->parsedConfigFiles[$configFileKey] = $cachedConfigFile;
            }

            // Check if we have a type from the docblock
            $docComment = $cachedConfigFile->getDocComment();

            if ($docComment !== null && $this->treatPhpDocTypesAsCertain) {
                $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                    $scope->getFile(),
                    $scope->getClassReflection()?->getName(),
                    $scope->getTraitReflection()?->getName(),
                    $scope->getFunctionName(),
                    $docComment->getText(),
                );

                $returnTag = $resolvedPhpDoc->getReturnTag();

                if ($returnTag !== null) {
                    $type = $returnTag->getType();

                    foreach ($configKeyParts as $part) {
                        $offset = is_numeric($part) ? new ConstantIntegerType((int) $part) : new ConstantStringType($part);

                        $type = $type->getOffsetValueType($offset);
                    }

                    $this->parsedConfigs[$key] = $type;
                    $returnTypes[]             = $type;
                    continue;
                }
            }

            if (! $cachedConfigFile->expr instanceof Array_) {
                continue;
            }

            $arrayNode = $cachedConfigFile->expr;

            if ($configKeyParts === []) {
                $type = $scope->getType($arrayNode);

                $this->parsedConfigs[$key] = $type;
                $returnTypes[]             = $type;

                continue;
            }

            $ret   = null;
            $items = $arrayNode->items;

            foreach ($configKeyParts as $configKeyPart) {
                foreach ($items as $item) {
                    if (! $item->key instanceof Node\Scalar) {
                        continue 3;
                    }

                    if (! property_exists($item->key, 'value')) {
                        continue;
                    }

                    $itemKey = (string) $item->key->value;

                    if ($itemKey !== $configKeyPart) {
                        continue;
                    }

                    if ($item->value instanceof Array_) {
                        $items = $item->value->items;
                    }

                    $ret = $item->value;
                }
            }

            if ($ret === null) {
                continue;
            }

            $type = $scope->getType($ret);

            $this->parsedConfigs[$key] = $type;
            $returnTypes[]             = $type;
        }

        return $returnTypes;
    }

    private function loadConfigFiles(): void
    {
        $this->configFiles      = [];
        $this->mergedConfigKeys = [];

        foreach ($this->configPaths as $configPath) {
            if (! is_dir($configPath)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath, FilesystemIterator::SKIP_DOTS));

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relativePath = substr($files->getSubPathname(), 0, -strlen('.php'));
                $configKey    = str_replace(['/', DIRECTORY_SEPARATOR], '.', $relativePath);

                // The first config directory that has a file for a key wins
                $this->configFiles[$configKey] ??= $file->getPathname();

                $keyParts = explode('.', $configKey);

                for ($length = count($keyParts) - 1; $length > 0; $length--) {
                    $this->mergedConfigKeys[implode('.', array_slice($keyParts, 0, $length))] = true;
                }
            }
        }
    }

    /**
     * Finds the file that holds a config key's value. Laravel sets each file
     * under its key in load order, so the file with the longest key that is a
     * prefix of the requested key determines its value.
     *
     * @return array{string, list<string>}|null the file's key and the remaining key parts
     */
    private function resolveConfigFile(string $key): array|null
    {
        $keyParts = explode('.', $key);

        for ($length = count($keyParts); $length > 0; $length--) {
            $configFileKey = implode('.', array_slice($keyParts, 0, $length));

            if (array_key_exists($configFileKey, $this->configFiles)) {
                return [$configFileKey, array_slice($keyParts, $length)];
            }
        }

        return null;
    }

    /**
     * Where a file holding the config key's value can be: one path for every
     * prefix of the key in every config directory, existing or not, so a
     * created file that changes which one resolveConfigFile() finds is noticed.
     *
     * Files created below the key's own directory, which make its value a
     * merged one, and new directories matching a glob config path are not
     * covered: there is no fixed set of paths to watch for them.
     *
     * @return list<string>
     */
    private function getConfigFileCandidates(string $key): array
    {
        if (array_key_exists($key, $this->configFileCandidates)) {
            return $this->configFileCandidates[$key];
        }

        $candidates = [];
        $keyParts   = explode('.', $key);

        for ($length = count($keyParts); $length > 0; $length--) {
            $relativePath = implode('/', array_slice($keyParts, 0, $length)) . '.php';

            foreach ($this->configPaths as $configPath) {
                $candidates[] = $configPath . '/' . $relativePath;
            }
        }

        return $this->configFileCandidates[$key] = $candidates;
    }

    private function parseConfigFile(string $path): Node\Stmt\Return_|null
    {
        try {
            $stmts = $this->parser->parseFile($path);
        } catch (ParserErrorsException) {
            return null;
        }

        /** @var Node\Stmt\Return_|null $returnNode */
        $returnNode = (new NodeFinder())->findFirstInstanceOf($stmts, Node\Stmt\Return_::class);

        return $returnNode;
    }

    /** @return list<string> */
    public function getConfigPaths(): array
    {
        // Fallback to default config path if no config paths are set
        if ($this->configFiles === []) {
            $this->configPaths = [config_path()];
            $this->loadConfigFiles();
        }

        return $this->configPaths;
    }
}

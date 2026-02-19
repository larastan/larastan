<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function array_key_exists;
use function array_shift;
use function config_path;
use function explode;
use function is_dir;
use function is_numeric;
use function iterator_to_array;
use function property_exists;
use function str_ends_with;

final class ConfigParser
{
    /** @var list<string> */
    private array $configPaths = [];

    /** @var array<string, SplFileInfo> */
    private array $configFiles = [];

    /** @var array<string, Type> */
    private array $parsedConfigs = [];

    /** @var array<string, Node\Stmt\Return_> */
    private array $parsedConfigFiles = [];

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
            $this->configPaths[] = $this->fileHelper->absolutizePath($configPath);
        }

        $this->configFiles = $this->getConfigFiles();
    }

    /**
     * @param ConstantStringType[] $constantStrings
     *
     * @return Type[]
     */
    public function getTypes(array $constantStrings, Scope $scope): array
    {
        $returnTypes = [];

        foreach ($constantStrings as $constantString) {
            $key = $constantString->getValue();

            if (array_key_exists($key, $this->parsedConfigs)) {
                $returnTypes[] = $this->parsedConfigs[$key];
                continue;
            }

            $configKeyParts = explode('.', $key);
            $configFileName = array_shift($configKeyParts);

            if (array_key_exists($configFileName, $this->parsedConfigFiles)) {
                $cachedConfigFile = $this->parsedConfigFiles[$configFileName];
            } else {
                $cachedConfigFile = $this->parseConfigFile($configFileName);

                // We could not parse the file or couldn't find the return array
                if ($cachedConfigFile === null) {
                    return [];
                }

                $this->parsedConfigFiles[$configFileName] = $cachedConfigFile;
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

            $returnTypes[] = $scope->getType($ret);
        }

        return $returnTypes;
    }

    /** @return array<string, SplFileInfo> */
    private function getConfigFiles(): array
    {
        /** @var array<string, SplFileInfo> $configFiles */
        $configFiles = [];

        foreach ($this->configPaths as $configPath) {
            if (! is_dir($configPath)) {
                continue;
            }

            $configFiles += iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath)),
                    '/\.php$/i',
                ),
            );
        }

        return $configFiles;
    }

    private function parseConfigFile(string $configFileName): Node\Stmt\Return_|null
    {
        foreach ($this->configFiles as $configFile) {
            if (str_ends_with($configFile->getPathname(), $configFileName . '.php')) {
                try {
                    $stmts = $this->parser->parseFile($configFile->getPathname());
                } catch (ParserErrorsException) {
                    continue;
                }

                /** @var Node\Stmt\Return_|null $returnNode */
                $returnNode = (new NodeFinder())->findFirstInstanceOf($stmts, Node\Stmt\Return_::class);

                if ($returnNode === null) {
                    continue;
                }

                $this->parsedConfigFiles[$configFileName] = $returnNode;

                return $returnNode;
            }
        }

        return null;
    }

    /** @return list<string> */
    public function getConfigPaths(): array
    {
        // Fallback to default config path if no config paths are set
        if ($this->configFiles === []) {
            $this->configPaths = [config_path()];
            $this->configFiles = $this->getConfigFiles();
        }

        return $this->configPaths;
    }
}

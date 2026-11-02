<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use FilesystemIterator;
use Larastan\Larastan\Internal\RecursionGuard;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\StatementContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_key_exists;
use function array_keys;
use function array_pop;
use function array_search;
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

    /**
     * Each config file's return statement, the statements before it and its namespace.
     *
     * @var array<string, array{Return_, list<Node\Stmt>, string|null}>
     */
    private array $parsedConfigFiles = [];

    /**
     * The scope at each config file's return statement, or null when the
     * statements before it always terminate.
     *
     * @var array<string, Scope|null>
     */
    private array $returnScopes = [];

    /**
     * Files the statements before a config file's return depend on, such as
     * the config files they read.
     *
     * @var array<string, list<string>>
     */
    private array $configFileDependencies = [];

    /**
     * Files a config key's value depends on besides the files that can hold
     * the key, such as config files its expression reads.
     *
     * @var array<string, list<string>>
     */
    private array $keyDependencies = [];

    /**
     * Dependencies collected for the config files and keys being typed, so
     * a cached result can declare them again to every file reading it.
     *
     * @var list<array<string, true>>
     */
    private array $dependencyCollectors = [];

    /** @var array<string, true> */
    private array $unparsableConfigFiles = [];

    /** @var array<string, list<string>> */
    private array $configFileCandidates = [];

    /** @param list<non-empty-string> $configPaths */
    public function __construct(
        private FileHelper $fileHelper,
        private Parser $parser,
        private FileTypeMapper $fileTypeMapper,
        private ScopeFactory $scopeFactory,
        private NodeScopeResolver $nodeScopeResolver,
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
     * @param Scope&DependencyTracker $scope
     *
     * @return Type[]
     */
    public function getTypes(array $constantStrings, Scope $scope): array
    {
        $returnTypes = [];

        foreach ($constantStrings as $constantString) {
            $key = $constantString->getValue();

            // Every analysed file reading the key depends on the config files, parsed or served from the cache.
            $this->trackFileDependencies($scope, $this->getConfigFileCandidates($key));

            if (array_key_exists($key, $this->parsedConfigs)) {
                $this->trackFileDependencies($scope, $this->keyDependencies[$key] ?? []);
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

            if (! array_key_exists($configFileKey, $this->parsedConfigFiles)) {
                $parsedConfigFile = $this->parseConfigFile($this->configFiles[$configFileKey]);

                // We could not parse the file or couldn't find the return array
                if ($parsedConfigFile === null) {
                    $this->unparsableConfigFiles[$configFileKey] = true;

                    return [];
                }

                $this->parsedConfigFiles[$configFileKey] = $parsedConfigFile;
            }

            [$return] = $this->parsedConfigFiles[$configFileKey];

            // Check if we have a type from the docblock
            $docComment = $return->getDocComment();

            if ($docComment !== null && $this->treatPhpDocTypesAsCertain) {
                // Names in the docblock are resolved with the config file's imports
                $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                    $this->configFiles[$configFileKey],
                    null,
                    null,
                    null,
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

            if (! $return->expr instanceof Array_) {
                continue;
            }

            $value = $return->expr;

            foreach ($configKeyParts as $configKeyPart) {
                // A key below a value that is not a literal array has no expression to type
                if (! $value instanceof Array_) {
                    continue 2;
                }

                $match = null;

                foreach ($value->items as $item) {
                    if (! $item->key instanceof Node\Scalar) {
                        continue 3;
                    }

                    if (! property_exists($item->key, 'value')) {
                        continue;
                    }

                    if ((string) $item->key->value !== $configKeyPart) {
                        continue;
                    }

                    // The last item with the key wins, as in PHP
                    $match = $item->value;
                }

                if ($match === null) {
                    continue 2;
                }

                $value = $match;
            }

            [$type, $dependencies] = $this->collectFileDependencies(function () use ($configFileKey, $value, $scope): Type|null {
                $returnScope = $this->getReturnScope($configFileKey);

                if ($returnScope === null) {
                    return null;
                }

                $this->trackFileDependencies($scope, $this->configFileDependencies[$configFileKey] ?? []);

                return $returnScope->getType($value);
            });

            // The config file is being typed further up the call stack, or never reaches its return
            if ($type === null) {
                continue;
            }

            // An undefined variable, such as one an included file assigns, is unknown rather than an error
            $type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
                if ($type instanceof ErrorType) {
                    return new MixedType();
                }

                return $traverse($type);
            });

            $this->trackFileDependencies($scope, $dependencies);

            $this->keyDependencies[$key] = $dependencies;
            $this->parsedConfigs[$key]   = $type;
            $returnTypes[]               = $type;
        }

        return $returnTypes;
    }

    /**
     * The scope at the config file's return statement, as the statements before
     * it leave it. The file is typed on its own, never in the scope of a file
     * reading it, so the variables, imports and magic constants are its own.
     *
     * Returns null when the statements before the return always terminate, or
     * while the file's statements are being processed, when they read the file
     * itself.
     */
    private function getReturnScope(string $configFileKey): Scope|null
    {
        if (array_key_exists($configFileKey, $this->returnScopes)) {
            return $this->returnScopes[$configFileKey];
        }

        $path = $this->fileHelper->normalizePath($this->configFiles[$configFileKey]);

        $result = RecursionGuard::run('larastan.configFile.' . $path, function () use ($configFileKey, $path): array {
            [$return, $statements, $namespace] = $this->parsedConfigFiles[$configFileKey];

            $scope = $this->scopeFactory->create(ScopeContext::create($path));

            if ($namespace !== null) {
                $scope = $scope->enterNamespace($namespace);
            }

            if ($statements === []) {
                $this->configFileDependencies[$configFileKey] = [];

                return [$scope];
            }

            [$statementResult, $dependencies] = $this->collectFileDependencies(fn () => $this->nodeScopeResolver->processStmtNodes(
                $return,
                $statements,
                $scope,
                static function (): void {
                },
                StatementContext::createTopLevel(),
            ));

            $this->configFileDependencies[$configFileKey] = $dependencies;

            return [$statementResult->isAlwaysTerminating() ? null : $statementResult->getScope()];
        });

        if ($result === null) {
            return null;
        }

        return $this->returnScopes[$configFileKey] = $result[0];
    }

    /**
     * Declares the files on the scope and on the config files and keys being
     * typed, whose cached results declare them again later.
     *
     * @param Scope&DependencyTracker $scope
     * @param list<string>            $files
     */
    private function trackFileDependencies(Scope $scope, array $files): void
    {
        foreach ($files as $file) {
            $scope->trackFileDependency($file);

            foreach (array_keys($this->dependencyCollectors) as $index) {
                $this->dependencyCollectors[$index][$file] = true;
            }
        }
    }

    /**
     * @param callable(): T $callback
     *
     * @return array{T, list<string>} the callback's result and the files declared while it ran
     *
     * @template T
     */
    private function collectFileDependencies(callable $callback): array
    {
        $this->dependencyCollectors[] = [];

        try {
            $result = $callback();
        } finally {
            $dependencies = array_keys(array_pop($this->dependencyCollectors));
        }

        return [$result, $dependencies];
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

    /**
     * Finds the value the config file returns: its only return statement
     * outside functions and classes, at the top level of the file or of its
     * namespace, so it always runs once the statements before it do.
     *
     * @return array{Return_, list<Node\Stmt>, string|null}|null the return statement, the statements before it and the file's namespace
     */
    private function parseConfigFile(string $path): array|null
    {
        try {
            $stmts = $this->parser->parseFile($path);
        } catch (ParserErrorsException) {
            return null;
        }

        $visitor = new class extends NodeVisitorAbstract {
            /** @var list<Return_> */
            public array $returns = [];

            public function enterNode(Node $node): int|null
            {
                if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof Return_) {
                    $this->returns[] = $node;
                }

                return null;
            }
        };

        (new NodeTraverser($visitor))->traverse($stmts);

        if (count($visitor->returns) !== 1) {
            return null;
        }

        $return     = $visitor->returns[0];
        $namespace  = null;
        $statements = [];

        foreach ($stmts as $stmt) {
            if (! $stmt instanceof Node\Stmt\Namespace_) {
                $statements[] = $stmt;
                continue;
            }

            if ($namespace !== null) {
                return null;
            }

            $namespace = $stmt->name?->toString() ?? '';

            foreach ($stmt->stmts as $namespacedStmt) {
                $statements[] = $namespacedStmt;
            }
        }

        $returnIndex = array_search($return, $statements, true);

        if ($returnIndex === false) {
            return null;
        }

        return [$return, array_slice($statements, 0, $returnIndex), $namespace === '' ? null : $namespace];
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

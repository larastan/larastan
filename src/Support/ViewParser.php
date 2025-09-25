<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use PhpParser\Node\Stmt;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

use function array_key_exists;

final class ViewParser
{
    /** @var array<string, Stmt[]> */
    protected array $nodes = [];

    public function __construct(private Parser $parser)
    {
    }

    /** @return Stmt[] */
    public function getNodes(string $path): array
    {
        if (! array_key_exists($path, $this->nodes)) {
            $this->nodes[$path] = $this->parseFile($path);
        }

        return $this->nodes[$path];
    }

    /** @return Stmt[] */
    protected function parseFile(string $path): array
    {
        try {
            return $this->parser->parseFile($path);
        } catch (ParserErrorsException) {
            return [];
        }
    }
}

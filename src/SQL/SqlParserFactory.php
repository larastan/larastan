<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

use Closure;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

use function class_exists;

#[AutowiredService(name: 'sqlParserFactory')]
final class SqlParserFactory
{
    private SqlParser|null $cachedParser = null;

    /** @var Closure():bool */
    private Closure $phpMyAdminDetector;

    public function __construct(
        #[AutowiredParameter(ref: '@iamcalSqlParser')]
        private readonly IamcalSqlParser $iamcalSqlParser,
        callable|null $phpMyAdminDetector = null,
    ) {
        if ($phpMyAdminDetector !== null) {
            $this->phpMyAdminDetector = Closure::fromCallable($phpMyAdminDetector);
        } else {
            $this->phpMyAdminDetector = static fn (): bool => class_exists('PhpMyAdmin\\SqlParser\\Parser');
        }
    }

    public function create(): SqlParser
    {
        if ($this->cachedParser !== null) {
            return $this->cachedParser;
        }

        if ($this->isPhpMyAdminParserAvailable()) {
            $this->cachedParser = new PhpMyAdminSqlParser($this->iamcalSqlParser);
        } else {
            $this->cachedParser = $this->iamcalSqlParser;
        }

        return $this->cachedParser;
    }

    private function isPhpMyAdminParserAvailable(): bool
    {
        return ($this->phpMyAdminDetector)();
    }
}

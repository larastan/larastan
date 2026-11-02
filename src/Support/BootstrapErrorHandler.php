<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use NunoMaduro\Collision\Provider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Whoops\Inspector\Inspector;
use Whoops\Inspector\InspectorFactory;

use function array_map;
use function array_merge;
use function class_exists;
use function function_exists;
use function getcwd;
use function htmlspecialchars;
use function implode;
use function preg_match;
use function preg_split;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;
use function Termwind\render;
use function Termwind\renderUsing;
use function trim;

use const ENT_QUOTES;
use const PHP_EOL;

final class BootstrapErrorHandler
{
    private const USER_TITLE      = 'Application bootstrap failed';
    private const FRAMEWORK_TITLE = 'Laravel framework bootstrap failed';

    public function __construct(
        private OutputInterface|null $output = null,
        private bool|null $decorated = null,
    ) {
    }

    public function handle(Throwable $throwable): void
    {
        $output      = $this->output ?? new ConsoleOutput();
        $errorOutput = $output instanceof ConsoleOutput ? $output->getErrorOutput() : $output;

        if ($this->decorated !== null) {
            $output->setDecorated($this->decorated);
            $errorOutput->setDecorated($this->decorated);
        }

        $decorated = $errorOutput->isDecorated();

        if ($decorated && $this->renderWithCollision($throwable, $errorOutput)) {
            return;
        }

        $this->renderWithSymfonyStyle($throwable, $errorOutput);
    }

    private function renderWithCollision(Throwable $throwable, OutputInterface $output): bool
    {
        if (! class_exists(Provider::class) || ! function_exists('Termwind\\render')) {
            return false;
        }

        $isUserCodeError = $this->isUserCodeError($throwable);

        try {
            $message = $isUserCodeError
                ? $this->formatUserCodeError($throwable, includeErrorDetails: false)
                : $this->formatFrameworkError($throwable, includeErrorDetails: false);

            $title        = $isUserCodeError ? self::USER_TITLE : self::FRAMEWORK_TITLE;
            $titleClasses = $isUserCodeError ? 'bg-red-500 text-white font-bold' : 'bg-yellow-500 text-black font-bold';

            $body = $this->buildCollisionBody($message);

            renderUsing($output);

            render(<<<HTML
                <div class="mx-2 mt-1">
                    <div class="px-1 $titleClasses">
                        {$title}
                    </div>
                    <div class="px-1">
                        {$body}
                    </div>
                    <div class="mt-1 text-gray">Exception Details</div>
                    <hr class="border-t text-gray"/>
                </div>

            HTML);

            $provider = new Provider();
            $handler  = $provider->register()
                ->getHandler()
                ->setOutput($output);

            $inspector = match (true) {
                class_exists(Inspector::class) => new Inspector($throwable),
                class_exists(InspectorFactory::class) => (new InspectorFactory())->create($throwable),
                default => null,
            };

            if ($inspector !== null) {
                $handler->setInspector($inspector);
            }

            $handler->handle($throwable);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function renderWithSymfonyStyle(Throwable $throwable, OutputInterface $output): void
    {
        $decorated       = $output->isDecorated();
        $isUserCodeError = $this->isUserCodeError($throwable);
        $message         = $isUserCodeError
            ? $this->formatUserCodeError($throwable, decorated: $decorated)
            : $this->formatFrameworkError($throwable, decorated: $decorated);

        $title = $isUserCodeError ? self::USER_TITLE : self::FRAMEWORK_TITLE;
        $style = $isUserCodeError ? 'fg=white;bg=red' : 'fg=black;bg=yellow';

        $io = new SymfonyStyle(new ArrayInput([]), $output);

        $io->block($title, null, $style, ' ', true);

        foreach ($this->splitLines($message) as $line) {
            match (true) {
                $line === '' => $io->newLine(),
                str_starts_with($line, 'Error:') => $io->writeln('<error>' . $line . '</error>'),
                str_starts_with($line, 'Stack trace:') => $io->writeln('<options=bold>' . $line . '</options=bold>'),
                preg_match('/^#\d+/', $line) === 1 => $io->writeln('<fg=gray>' . $line . '</fg=gray>'),
                default => $io->writeln($line),
            };
        }
    }

    private function isUserCodeError(Throwable $throwable): bool
    {
        $file = $throwable->getFile();

        if ($file === '') {
            return false;
        }

        return ! str_contains($this->normalizePath($file), '/vendor/');
    }

    private function formatUserCodeError(Throwable $throwable, bool $includeErrorDetails = true, bool $decorated = true): string
    {
        $parts = [
            'PHPStan was unable to bootstrap your application due to an error in your code.',
            '',
            sprintf('%s Fix the misconfiguration in your application code and run PHPStan again.', $this->getTipSymbol($decorated)),
        ];

        return $this->appendErrorDetails($parts, $throwable, $includeErrorDetails);
    }

    private function formatFrameworkError(Throwable $throwable, bool $includeErrorDetails = true, bool $decorated = true): string
    {
        $parts = [
            'PHPStan was unable to bootstrap your application because Laravel failed to start.',
            '',
            'Larastan launches your Laravel application during analysis to provide smarter results.',
            'The framework reported an error while starting, so the analysis could not continue.',
            '',
            sprintf('%s Try the following:', $this->getTipSymbol($decorated)),
            ' - Check your environment variables in the .env file',
            ' - Check the Laravel logs in storage/logs/laravel.log for more details',
            ' - Run composer dump-autoload to ensure classes are indexed',
            ' - Verify service provider registration and configuration',
            ' - Verify the application boots by running php artisan about',
        ];

        return $this->appendErrorDetails($parts, $throwable, $includeErrorDetails);
    }

    /** @param list<string> $parts */
    private function appendErrorDetails(array $parts, Throwable $throwable, bool $includeErrorDetails): string
    {
        if ($includeErrorDetails) {
            $parts = array_merge($parts, [
                '',
                sprintf('Error: %s', $this->formatErrorMessage($throwable)),
                '',
                'Stack trace:',
                $this->formatStackTrace($throwable),
            ]);
        }

        return implode(PHP_EOL, $parts);
    }

    private function formatErrorMessage(Throwable $throwable): string
    {
        $message = trim($throwable->getMessage());

        return $message !== '' ? $message : sprintf('Unhandled %s with no message', $throwable::class);
    }

    private function formatStackTrace(Throwable $throwable): string
    {
        $projectRoot = getcwd();
        $trace       = $this->normalizePath($throwable->getTraceAsString());

        return $projectRoot !== false
            ? str_replace($this->normalizePath($projectRoot) . '/', '', $trace)
            : $trace;
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /** @return array<int, string> */
    private function splitLines(string $message): array
    {
        return preg_split('/\r\n|\r|\n/', $message) ?: [];
    }

    private function buildCollisionBody(string $message): string
    {
        return implode('', array_map(fn (string $line): string => match (true) {
            $line === '' => '<div class="mb-1"></div>',
            str_starts_with($line, ' - ') => '<div class="pl-3">• ' . $this->escapeForHtml(substr($line, 3)) . '</div>',
            default => '<div>' . $this->escapeForHtml($line) . '</div>',
        }, $this->splitLines($message)));
    }

    private function escapeForHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function getTipSymbol(bool $decorated): string
    {
        return $decorated ? "\u{1F4A1}" : 'Tip:';
    }
}

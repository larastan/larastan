<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Console;

use PHPStan\Command\AnalyseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
final class OptionsResolver
{
    /**
     * The default level.
     */
    private const DEFAULT_LEVEL = 5;

    /**
     * The default memory limit.
     */
    private const DEFAULT_MEMORY_LIMIT = '2048M';

    /**
     * @var AnalyseCommand
     */
    private $command;

    /**
     * @var InputDefinition
     */
    private $definition;

    /**
     * OptionsResolver constructor.
     *
     * @param AnalyseCommand $command
     */
    public function __construct(AnalyseCommand $command)
    {
        $this->command = $command;
    }

    /**
     * @return InputDefinition
     */
    public function getDefinition(): InputDefinition
    {
        $definition = new InputDefinition([
            new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
            new InputOption('paths-file', null, InputOption::VALUE_REQUIRED, 'Path to a file with a list of paths to run analysis on'),
            new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
            new InputOption('level', 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
            new InputOption('no-progress', null, InputOption::VALUE_NONE, 'Do not show progress bar, only results'),
            new InputOption('debug', null, InputOption::VALUE_NONE, 'Show debug information - which file is analysed, do not catch internal errors'),
            new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
            new InputOption('error-format', null, InputOption::VALUE_REQUIRED, 'Format in which to print the result of the analysis', 'table'),
            new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'),
            new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with XDebug for debugging purposes'),
        ]);

        $definition->setArguments([]);

        $definition->getOption('level')
            ->setDefault((string) self::DEFAULT_LEVEL);

        $definition->getOption('autoload-file')
            ->setDefault(base_path('vendor/autoload.php'));

        $definition->getOption('configuration')
            ->setDefault($this->defaultConfiguration());

        $definition->getOption('memory-limit')
            ->setDefault(self::DEFAULT_MEMORY_LIMIT);

        $definition->addOption(
            new InputOption(
                'paths', 'p', InputOption::VALUE_REQUIRED, 'Paths with source code to run analysis on', 'app'
            )
        );

        $definition->addOption(
            new InputOption(
                'bin-path', null, InputOption::VALUE_REQUIRED, 'Folder where the PHPStan binary is located'
            )
        );

        $definition->addOption(
            new InputOption(
                'no-tty', null, InputOption::VALUE_NONE, 'Force disabling TTY'
            )
        );

        return $this->definition = $definition;
    }

    /**
     * Determines the default configuration.
     *
     * @return string
     */
    private function defaultConfiguration() : string
    {
        $supportedFiles = [
            'larastan.neon',
            'phpstan.neon',
            'phpstan.neon.dist',
        ];

        foreach ($supportedFiles as $file) {
            $filePath = base_path($file);

            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        return __DIR__.'/../../extension.neon';
    }
}

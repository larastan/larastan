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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

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
     * @var \PHPStan\Command\AnalyseCommand
     */
    private $command;

    /**
     * @var \Symfony\Component\Console\Input\InputDefinition
     */
    private $definition;

    /**
     * OptionsResolver constructor.
     *
     * @param \PHPStan\Command\AnalyseCommand $command
     */
    public function __construct(AnalyseCommand $command)
    {
        $this->command = $command;
    }

    /**
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    public function getDefinition(): InputDefinition
    {
        $definition = clone $this->command->getDefinition();
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

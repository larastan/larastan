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

use function implode;
use function is_array;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Console\Application as Artisan;

/**
 * @internal
 */
final class CodeAnalyseCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'code:analyse';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Analyses source code';

    /**
     * @var \NunoMaduro\Larastan\Console\OptionsResolver
     */
    private $optionsResolver;

    /**
     * CodeAnalyseCommand constructor.
     *
     * @param \NunoMaduro\Larastan\Console\OptionsResolver $optionsResolver
     */
    public function __construct(OptionsResolver $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDefinition($this->optionsResolver->getDefinition());
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $process = new Process($this->cmd());

        if (Process::isTtySupported()) {
            $process->setTty(true);
        }

        $process->setTimeout(null);

        $process->start();

        foreach ($process as $type => $data) {
            $this->output->writeln($data);
        }
    }

    /**
     * @return string
     */
    private function cmd(): string
    {
        $options = '';
        foreach ($this->optionsResolver->getDefinition()
                     ->getOptions() as $option) {
            if ($option->getName() === 'paths') {
                continue;
            }

            $this->input->getOption('memory-limit');

            $value = $this->option($name = $option->getName());

            $value = is_array($value) ? implode(',', $value) : $value;

            if ($option->acceptValue() && $value !== null) {
                $options .= " --$name=$value";
            } else {
                if ($this->option($name)) {
                    $options .= " --$name";
                }
            }
        }

        $pathsValue = $this->option('paths');
        if (is_array($pathsValue)) {
            $pathsValue = current($pathsValue);
        }

        $paths = array_map(
            function ($path) {
                return starts_with($path, DIRECTORY_SEPARATOR) || empty($path) ? $path : $this->laravel->basePath(
                    trim($path)
                );
            },
            explode(',', $pathsValue)
        );

        $params = [
            $this->laravel->basePath('vendor/bin/phpstan'),
            'analyse',
            implode(' ', $paths),
            $options,
        ];

        return implode(' ', $params);
    }
}

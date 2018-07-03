<?php

declare(strict_types=1);

/**
 * This file is part of Laravel Code Analyse.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\LaravelCodeAnalyse\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;

final class CodeAnalyseCommand extends Command
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $laravel;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'code:analyse {level=max}';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Analyses source code';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $level = is_string($this->argument('level')) ? $this->argument('level') : 'max';

        $params = [
            'php phpstan',
            'analyse',
            '--level='.$level,
            '--autoload-file='.$this->laravel->basePath('vendor/autoload.php'),
            '--configuration='.__DIR__.'/../../extension.neon',
            $this->laravel['path'],
        ];

        $process = new Process(implode(' ', $params), $this->laravel->basePath('vendor/bin'));

        if (Process::isTtySupported()) {
            $process->setTty(true);
        }

        $process->setTimeout(null);

        $process->start();

        foreach ($process as $type => $data) {
            $this->output->writeln($data);
        }
    }
}

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

use function implode;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Console\Application as Artisan;

final class CodeAnalyseCommand extends Command
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $laravel;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'code:analyse 
            {--level=1} 
            {--path= : Path to directory which need to analyse}
            {--memory-limit= : Memory limit}
    ';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Analyses source code';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $level = is_string($this->option('level')) ? $this->option('level') : 'max';

        $path = $this->laravel['path'];

        if ($this->hasOption('path') && $this->option('path')) {
            $path = base_path($this->option('path'));
        }

        $params = [
            static::phpstanBinary(),
            'analyse',
            '--level='.$level,
            '--autoload-file='.$this->laravel->basePath('vendor/autoload.php'),
            '--configuration='.__DIR__.'/../../extension.neon',
            $path,
        ];

        if ($this->hasOption('memory-limit') && $this->option('memory-limit')) {
            $params[] = '--memory-limit='.$this->option('memory-limit');
        }

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

    /**
     * @return string
     */
    private static function phpstanBinary(): string
    {
        return sprintf('%s %s', Artisan::phpBinary(), 'phpstan');
    }
}

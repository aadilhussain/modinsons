<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateOnDeploy extends Command
{
    protected $signature = 'deploy:migrate';

    protected $description = 'Runs pending migrations during a Vercel build; a no-op everywhere else (composer install locally, CI, etc.)';

    public function handle(): int
    {
        if (! env('VERCEL')) {
            $this->info('Not a Vercel build — skipping automatic migration.');

            return self::SUCCESS;
        }

        return $this->call('migrate', ['--force' => true]);
    }
}

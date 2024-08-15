<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Console\Isolatable;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'project:install', description: 'Install the project.')]
class InstallCommand extends Command implements Isolatable
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:install {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the project.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $force = $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        copy('.env.example', '.env');

        $this->callSilent('key:generate', ['--force' => $force]);
        $this->callSilent('migrate:refresh', ['--force' => $force]);
        $this->call('db:seed', ['--force' => $force]);
        $this->callSilent('storage:link');
        $this->callSilent('debugbar:clear');
        $this->callSilent('optimize:clear');

        return Command::SUCCESS;
    }
}

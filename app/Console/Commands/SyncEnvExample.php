<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncEnvExample extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-env-example';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 🔒 Block in production
        if (!app()->environment('local')) {
            $this->warn('❌ This command is only allowed in local environment.');
            return Command::FAILURE;
        }

        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (!file_exists($envPath)) {
            $this->error('.env file not found!');
            return Command::FAILURE;
        }

        $lines = file($envPath);
        $result = [];

        foreach ($lines as $line) {
            $trim = trim($line);

            // keep comments and empty lines
            if ($trim === '' || str_starts_with($trim, '#')) {
                $result[] = $line;
                continue;
            }

            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);
                $result[] = $key . "=\n"; // remove value
            } else {
                $result[] = $line;
            }
        }

        file_put_contents($examplePath, implode('', $result));

        $this->info('✅ .env.example synced successfully (safe, no secrets)');

        return Command::SUCCESS;
    }
}

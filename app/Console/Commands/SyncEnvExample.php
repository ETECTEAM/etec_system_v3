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
            $this->warn('❌ Only allowed in local environment.');
            return Command::FAILURE;
        }

        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (!file_exists($envPath)) {
            $this->error('.env file not found!');
            return Command::FAILURE;
        }

        // Read .env
        $envLines = file($envPath, FILE_IGNORE_NEW_LINES);

        // Read existing .env.example (if exists)
        $exampleLines = file_exists($examplePath)
            ? file($examplePath, FILE_IGNORE_NEW_LINES)
            : [];

        // Extract existing keys
        $existingKeys = [];
        foreach ($exampleLines as $line) {
            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);
                $existingKeys[$key] = true;
            }
        }

        $newLines = $exampleLines;

        foreach ($envLines as $line) {
            $trim = trim($line);

            // skip empty/comment
            if ($trim === '' || str_starts_with($trim, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);

                // only add if NOT exists
                if (!isset($existingKeys[$key])) {
                    $newLines[] = $key . '=';
                }
            }
        }

        file_put_contents($examplePath, implode("\n", $newLines) . "\n");

        $this->info('✅ .env.example updated (only new keys added)');

        return Command::SUCCESS;
    }
}

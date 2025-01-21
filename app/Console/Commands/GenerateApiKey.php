<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    protected $signature = 'api:generate-key';
    protected $description = 'Generate a new API key';

    public function handle()
    {
        $apiKey = Str::random(64);
        
        $this->info('Generated API Key: ' . $apiKey);
        $this->info('Make sure to add this to your .env file as API_KEY=' . $apiKey);
        
        return Command::SUCCESS;
    }
}

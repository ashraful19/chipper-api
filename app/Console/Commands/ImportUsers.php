<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import {url} {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $limit = $this->argument('limit');

        $response = Http::get($url);
        $users = $response->json();

        $this->info('Importing users from ' . $url);
        $this->info('Importing ' . count($users) . ' users');

        $users = array_slice($users, 0, (int) $limit);

        $bulkData = [];
        foreach ($users as $user) {
            $bulkData[] = [
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        foreach (array_chunk($bulkData, 100) as $chunk) {
            User::insertOrIgnore($chunk);
        }

        $this->info('Imported ' . count($bulkData) . ' users');

        return Command::SUCCESS;
    }
}

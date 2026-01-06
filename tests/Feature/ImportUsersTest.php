<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportUsersTest extends TestCase
{
    use DatabaseMigrations;

    public function test_can_import_users_from_json_url(): void
    {
        $mockUsers = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ],
        ];

        Http::fake([
            'https://example.com/users.json' => Http::response($mockUsers, 200),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 10,
        ])
            ->expectsOutput('Importing users from https://example.com/users.json')
            ->expectsOutput('Importing 2 users')
            ->expectsOutput('Imported 2 users')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_import_respects_limit_parameter(): void
    {
        $mockUsers = [
            ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com'],
            ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com'],
            ['id' => 3, 'name' => 'User 3', 'email' => 'user3@example.com'],
            ['id' => 4, 'name' => 'User 4', 'email' => 'user4@example.com'],
            ['id' => 5, 'name' => 'User 5', 'email' => 'user5@example.com'],
        ];

        Http::fake([
            'https://example.com/users.json' => Http::response($mockUsers, 200),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 3,
        ])
            ->expectsOutput('Importing 5 users')
            ->expectsOutput('Imported 3 users')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user3@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'user4@example.com']);
    }

    public function test_import_prevents_duplicates(): void
    {
        $mockUsers = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ];

        Http::fake([
            'https://example.com/users.json' => Http::response($mockUsers, 200),
        ]);

        User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'Existing John',
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 10,
        ])
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'Existing John',
        ]);
    }

    public function test_import_handles_large_datasets_with_chunking(): void
    {
        $mockUsers = [];
        for ($i = 1; $i <= 250; $i++) {
            $mockUsers[] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
            ];
        }

        Http::fake([
            'https://example.com/users.json' => Http::response($mockUsers, 200),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 250,
        ])
            ->expectsOutput('Importing 250 users')
            ->expectsOutput('Imported 250 users')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 250);
    }
}


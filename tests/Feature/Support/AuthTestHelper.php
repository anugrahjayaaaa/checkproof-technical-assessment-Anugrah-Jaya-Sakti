<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait AuthTestHelper
{
    protected User $testUser;

    protected function createDummySedeer()
    {
        $password = '#Password123';

        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'role' => 'administrator',
            'password' => Hash::make($password),
        ]);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
            'password' => Hash::make($password),
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@example.com',
            'role' => 'user',
            'password' => Hash::make($password),
        ]);
    }

    protected function createTestUser(array $attributes = []): User
    {
        return User::factory()->create([
            'email' => $attributes['email'] ?? 'admin@example.com',
            'password' => Hash::make($attributes['password'] ?? '#Password123'),
        ]);
    }

    protected function login(array $attributes = []): string
    {
        $response = $this->postJson('/api/login', [
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);

        $response->assertOk();

        return $response->json('data.token');
    }

    protected function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }
}

<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

trait AuthTestHelper
{
    protected const TEST_PASSWORD = '#Password123';

    protected const ADMIN_EMAIL = 'admin@example.com';
    protected const MANAGER_EMAIL = 'manager@example.com';
    protected const USER_EMAIL = 'user@example.com';

    protected function createSeededUsers(): void
    {
        $password = Hash::make(self::TEST_PASSWORD);

        User::factory()->create([
            'name' => 'Administrator',
            'email' => self::ADMIN_EMAIL,
            'role' => 'administrator',
            'password' => $password,
        ]);

        User::factory()->create([
            'name' => 'Manager',
            'email' => self::MANAGER_EMAIL,
            'role' => 'manager',
            'password' => $password,
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => self::USER_EMAIL,
            'role' => 'user',
            'password' => $password,
        ]);
    }

    protected function createTestUser(array $attributes = []): User
    {
        return User::factory()->create([
            'email' => $attributes['email'] ?? self::ADMIN_EMAIL,
            'password' => Hash::make($attributes['password'] ?? self::TEST_PASSWORD),
        ]);
    }

    protected function credentialsFor(string $role): array
    {
        $email = match ($role) {
            'administrator' => self::ADMIN_EMAIL,
            'manager' => self::MANAGER_EMAIL,
            'user' => self::USER_EMAIL,
            default => throw new \InvalidArgumentException("Unknown role: {$role}"),
        };

        return ['email' => $email, 'password' => self::TEST_PASSWORD];
    }

    protected function adminCredentials(): array
    {
        return $this->credentialsFor('administrator');
    }

    protected function loginAsRole(string $role): string
    {
        return $this->login($this->credentialsFor($role));
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

    protected function authJson(string $method, string $uri, string $token, array $data = []): TestResponse
    {
        return $this->withHeaders($this->authHeaders($token))
            ->json($method, $uri, $data);
    }
}

<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\AuthTestHelper;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    private string $endpoint = '/api/login';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestUser($this->adminCredentials());
    }

    public function test_login_with_valid_credentials(): void
    {
        $credentials = $this->adminCredentials();

        $response = $this->postJson($this->endpoint, $credentials);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Login successful.',
                'data' => [
                    'user' => [
                        'email' => $credentials['email']
                    ]
                ],
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'created_at'
                    ],
                    'token'
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_with_invalid_credentials(): void
    {
        $credentials = $this->adminCredentials();

        $response = $this->postJson($this->endpoint, [
            'email' => $credentials['email'],
            'password' => $credentials['password'] . 'invalid',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'data' => ['message' => 'Invalid credentials.'],
            ]);
    }

    public function test_login_with_invalid_email(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email' => 'email',
            'password' => $this->adminCredentials()['password'],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_missing_email(): void
    {
        $response = $this->postJson($this->endpoint, [
            'password' => $this->adminCredentials()['password'],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_missing_password(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email' => $this->adminCredentials()['email'],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}

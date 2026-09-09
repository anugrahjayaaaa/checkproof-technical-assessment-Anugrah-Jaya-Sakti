<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Support\AuthTestHelper;
use Tests\TestCase;


class LoginTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    private string $endpoint = '/api/login';
    private array $attributes = ['admin@example.com', '#Password123'];

    protected function setUp(): void
    {
        parent::setup();

        $this->createTestUser($this->attributes);
    }

    public function test_login_with_valid_credentials(): void
    {
        $response = $this->postJson(
            $this->endpoint,
            [
                'email' => $this->attributes[0],
                'password' => $this->attributes[1],
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Login successful.',
                'data' => [
                    'user' => [
                        'email' => $this->attributes[0],
                    ],
                ],
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'created_at',
                    ],
                    'token',
                ],
            ]);

        $this->assertNotEmpty(
            $response->json('data.token')
        );
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson(
            $this->endpoint,
            [
                'email' => $this->attributes[0],
                'password' => $this->attributes[1] . 'invalid',
            ]
        );

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials.',
            ])
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_login_with_invalid_email(): void
    {
        $response = $this->postJson(
            $this->endpoint,
            [
                'email' => 'email',
                'password' => $this->attributes[1] . 'invalid',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The email field must be a valid email address.',
                'errors' => [
                    'email' => [
                        'The email field must be a valid email address.'
                    ]
                ],
            ])
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }
}

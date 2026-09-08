<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserStoreTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/users';
    private string $email = 'john@example.com';

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'email' => $this->email,
            'password' => 'password123',
        ], $overrides);
    }

    public function test_user_store_successfully(): void
    {
        Mail::fake();

        $response = $this->postJson(
            $this->endpoint,
            $this->validPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'email',
                'name',
                'created_at',
            ])
            ->assertJsonMissing([
                'password' => 'password123',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => $this->email,
            'role' => 'user',
            'active' => true,
        ]);
    }

    public function test_validation_errors(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
            ]);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create([
            'email' => $this->email,
        ]);

        $response = $this->postJson(
            $this->endpoint,
            $this->validPayload()
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->postJson(
            $this->endpoint,
            $this->validPayload([
                'password' => '12345',
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_hashed_in_database(): void
    {
        $this->postJson(
            $this->endpoint,
            $this->validPayload()
        );

        $user = User::where('email', $this->email)->first();

        $this->assertNotNull($user);
        $this->assertTrue(
            \Hash::check('password123', $user->password)
        );
    }
}

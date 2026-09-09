<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\AuthTestHelper;
use Tests\TestCase;

class UserStoreTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    private string $adminToken, $managerToken, $userToken;
    private string $endpoint = '/api/users';
    private string $email = 'john@example.com';
    private string $password = '#Password123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createDummySedeer();


        $this->adminToken = $this->login([
            'email' => 'admin@example.com',
            'password' => $this->password,
        ]);

        $this->managerToken = $this->login([
            'email' => 'manager@example.com',
            'password' => $this->password,
        ]);

        $this->userToken = $this->login([
            'email' => 'user@example.com',
            'password' => $this->password,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'email' => $this->email,
            'password' => 'password123',
        ], $overrides);
    }

    public function test_admin_create_new_user(): void
    {
        Mail::fake();

        $response = $this->withHeaders($this->authHeaders($this->adminToken))
            ->postJson(
                $this->endpoint,
                $this->validPayload()
            );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'email',
                    'name',
                    'created_at',
                ],
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

    public function test_manager_create_new_user(): void
    {
        Mail::fake();

        $response = $this->withHeaders($this->authHeaders($this->managerToken))
            ->postJson(
                $this->endpoint,
                $this->validPayload()
            );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'email',
                    'name',
                    'created_at',
                ],
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

    public function test_user_create_new_user(): void
    {
        Mail::fake();

        $response = $this->withHeaders($this->authHeaders($this->userToken))
            ->postJson(
                $this->endpoint,
                $this->validPayload()
            );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ])
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_validation_errors(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->adminToken))
            ->postJson($this->endpoint, []);

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

        $response = $this->withHeaders($this->authHeaders($this->adminToken))
            ->postJson(
                $this->endpoint,
                $this->validPayload()
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->adminToken))
            ->postJson(
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
        $this->withHeaders($this->authHeaders($this->adminToken))
            ->postJson(
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

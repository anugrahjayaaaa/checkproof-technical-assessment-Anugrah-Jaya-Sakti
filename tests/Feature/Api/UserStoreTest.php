<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Support\AuthTestHelper;
use Tests\TestCase;

class UserStoreTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    private string $endpoint = '/api/users';
    private string $newUserEmail = 'john@example.com';

    private string $adminToken;
    private string $managerToken;
    private string $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSeededUsers();

        $this->adminToken = $this->loginAsRole('administrator');
        $this->managerToken = $this->loginAsRole('manager');
        $this->userToken = $this->loginAsRole('user');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'email' => $this->newUserEmail,
            'password' => self::TEST_PASSWORD,
        ], $overrides);
    }

    public static function createUserPermissionProvider(): array
    {
        return [
            'admin creates user' => ['administrator', 'user', true],
            'admin creates manager' => ['administrator', 'manager', true],
            'manager creates user' => ['manager', 'user', true],
            'user forbidden from creating' => ['user', 'user', false],
        ];
    }

    #[DataProvider('createUserPermissionProvider')]
    public function test_create_user_authorization(string $actor, string $createdRole, bool $allowed): void
    {
        Mail::fake();

        $response = $this->authJson('post', $this->endpoint, $this->loginAsRole($actor), $this->validPayload(['role' => $createdRole]));

        if ($allowed) {
            $response
                ->assertCreated()
                ->assertJsonStructure(['data' => [
                    'id',
                    'email',
                    'name',
                    'created_at'
                ]])
                ->assertJsonMissing(['password' => self::TEST_PASSWORD]);

            $this->assertDatabaseHas('users', [
                'email' => $this->newUserEmail,
                'role' => $createdRole,
                'active' => true,
            ]);
        } else {
            $response
                ->assertForbidden()
                ->assertJson([
                    'message' => 'This action is unauthorized.'
                ]);
        }
    }

    public function test_validation_errors(): void
    {
        $response = $this->authJson('post', $this->endpoint, $this->adminToken, []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password'
            ]);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create([
            'email' => $this->newUserEmail
        ]);

        $response = $this->authJson('post', $this->endpoint, $this->adminToken, $this->validPayload());

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->authJson(
            'post',
            $this->endpoint,
            $this->adminToken,
            $this->validPayload(['password' => '12345'])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_hashed_in_database(): void
    {
        $this->authJson('post', $this->endpoint, $this->adminToken, $this->validPayload());

        $user = User::where('email', $this->newUserEmail)->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check(self::TEST_PASSWORD, $user->password));
    }

    public static function lengthValidationProvider(): array
    {
        return [
            'name too short' => ['name', 'Jo'],
            'name too long' => ['name', str_repeat('A', 51)],
            'email too long' => ['email', str_repeat('a', 244) . '@example.com'],
            'password too long' => ['password', str_repeat('A', 256)],
        ];
    }

    #[DataProvider('lengthValidationProvider')]
    public function test_store_validates_field_length(string $field, $value): void
    {
        $response = $this->authJson(
            'post',
            $this->endpoint,
            $this->adminToken,
            $this->validPayload([$field => $value])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    }
}

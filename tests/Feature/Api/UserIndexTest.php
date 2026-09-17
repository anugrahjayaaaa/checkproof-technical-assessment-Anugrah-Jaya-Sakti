<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Support\AuthTestHelper;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    private string $endpoint = '/api/users';
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestUser($this->adminCredentials());
        $this->token = $this->login($this->adminCredentials());
    }

    public function test_return_active_users(): void
    {
        User::factory()->create(['active' => true]);
        User::factory()->create(['active' => false]);

        $response = $this->authJson('get', $this->endpoint, $this->token);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'users')
            ->assertJsonStructure([
                'page',
                'users' => [
                    '*' => [
                        'id',
                        'email',
                        'name',
                        'role',
                        'created_at',
                        'orders_count',
                        'can_edit',
                    ],
                ],
            ]);
    }

    public function test_search_users_by_name(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        $response = $this->authJson('get', $this->endpoint . '?search=John', $this->token);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.name', 'John Doe');
    }

    public function test_search_users_by_email(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        $response = $this->authJson('get', $this->endpoint . '?search=jane@example.com', $this->token);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.email', 'jane@example.com');
    }

    public function test_sort_users_by_name(): void
    {
        User::factory()->create([
            'name' => 'Zack'
        ]);

        User::factory()->create([
            'name' => 'Alice'
        ]);

        $response = $this->authJson('get', $this->endpoint . '?sortBy=name', $this->token);

        $response->assertOk();

        $names = collect($response->json('users'))->pluck('name')->values()->all();

        $this->assertSame($names, collect($names)->sort()->values()->all());
    }

    public function test_orders_count(): void
    {
        $user = User::factory()->create();
        
        Order::factory(3)->create([
            'user_id' => $user->id
        ]);

        $response = $this->authJson('get', $this->endpoint, $this->token);

        $userData = collect($response->json('users'))->firstWhere('id', $user->id);

        $this->assertEquals(3, $userData['orders_count']);
    }

    public function test_pagination(): void
    {
        User::factory(20)->create();

        $response = $this->authJson('get', $this->endpoint . '?page=2', $this->token);

        $response
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonCount(10, 'users');
    }

    public function test_empty_parameters(): void
    {
        User::factory(3)->create();

        $response = $this->authJson('get', $this->endpoint . '?search=&page=1&sortBy=', $this->token);

        $response
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonCount(4, 'users');
    }

    public static function invalidPageProvider(): array
    {
        return [
            'page zero' => ['?page=0'],
            'negative page' => ['?page=-1'],
            'non integer page' => ['?page=abc'],
        ];
    }

    #[DataProvider('invalidPageProvider')]
    public function test_index_rejects_invalid_page(string $query): void
    {
        $response = $this->authJson('get', $this->endpoint . $query, $this->token);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }
}

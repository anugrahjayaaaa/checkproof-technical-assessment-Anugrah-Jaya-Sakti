<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Support\AuthTestHelper;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelper;

    private string $token;
    private string $endpoint = '/api/users';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestUser([
            'email' => 'admin@example.com',
            'password' => '#Password123',
        ]);

        $this->token = $this->login([
            'email' => 'admin@example.com',
            'password' => '#Password123',
        ]);
    }

    public function test_return_active_users(): void
    {
        User::factory()->create(['active' => true]);
        User::factory()->create(['active' => false]);

        $response = $this->withHeaders($this->authHeaders($this->token))
            ->getJson($this->endpoint . '');

        $response
            ->assertOk()
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

        $this->assertCount(2, $response->json('users'));
    }

    public function test_search_users_by_name(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->token))
            ->getJson($this->endpoint . '?search=John');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.name', 'John Doe');
    }

    public function test_search_users_by_email(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->token))
            ->getJson($this->endpoint . '?search=jane@example.com');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.email', 'jane@example.com');
    }

    public function test_sort_users_by_name(): void
    {
        User::factory()->create(['name' => 'Zack']);
        User::factory()->create(['name' => 'Alice']);

        $response = $this->withHeaders($this->authHeaders($this->token))
            ->getJson($this->endpoint . '?sortBy=name');

        $response->assertOk();

        $names = collect($response->json('users'))
            ->pluck('name')
            ->values()
            ->all();

        $this->assertSame(
            $names,
            collect($names)->sort()->values()->all()
        );
    }

    public function test_orders_count(): void
    {
        $user = User::factory()->create();

        Order::factory(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($this->token))
            ->getJson($this->endpoint . '');

        $userData = collect($response->json('users'))
            ->firstWhere('id', $user->id);

        $this->assertEquals(3, $userData['orders_count']);
    }

    public function test_pagination(): void
    {
        User::factory(20)->create();

        $response = $this->withHeaders($this->authHeaders($this->token))
            ->getJson($this->endpoint . '?page=2');

        $response
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonCount(10, 'users');
    }

    public function test_empty_parameters(): void
    {
        User::factory(3)->create();

        $response = $this->withHeaders($this->authHeaders($this->token))
            ->getJson(
                $this->endpoint . '?search=&page=1&sortBy='
            );

        $response
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonCount(4, 'users');
    }

    public function test_index_with_page_zero(): void
    {
        $response =  $this->withHeaders($this->authHeaders($this->token))
            ->getJson(
                $this->endpoint . '?page=0'
            );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The page field must be at least 1.',
                'errors' => [
                    'page' => [
                        'The page field must be at least 1.',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'page',
                ],
            ]);
    }

    public function test_index_with_negative_page(): void
    {
        $response =  $this->withHeaders($this->authHeaders($this->token))
            ->getJson(
                $this->endpoint . '?page=-1'
            );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The page field must be at least 1.',
                'errors' => [
                    'page' => [
                        'The page field must be at least 1.',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'page',
                ],
            ]);
    }

    public function test_index_with_non_integer_page(): void
    {
        $response =  $this->withHeaders($this->authHeaders($this->token))
            ->getJson(
                $this->endpoint . '?page=abc'
            );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The page field must be an integer.',
                'errors' => [
                    'page' => [
                        'The page field must be an integer.',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'page',
                ],
            ]);
    }
}

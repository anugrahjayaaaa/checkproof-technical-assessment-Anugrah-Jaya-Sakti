<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_active_users(): void
    {
        User::factory()->create(['active' => true]);
        User::factory()->create(['active' => false]);

        $response = $this->getJson('/api/users');

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

        $this->assertCount(1, $response->json('users'));
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

        $response = $this->getJson('/api/users?search=John');

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

        $response = $this->getJson('/api/users?search=jane@example.com');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.email', 'jane@example.com');
    }

    public function test_sort_users_by_name(): void
    {
        User::factory()->create(['name' => 'Zack']);
        User::factory()->create(['name' => 'Alice']);

        $response = $this->getJson('/api/users?sortBy=name');

        $response
            ->assertOk()
            ->assertJsonPath('users.0.name', 'Alice')
            ->assertJsonPath('users.1.name', 'Zack');
    }

    public function test_orders_count(): void
    {
        $user = User::factory()->create();

        Order::factory(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/users');

        $userData = collect($response->json('users'))
            ->firstWhere('id', $user->id);

        $this->assertEquals(3, $userData['orders_count']);
    }

    public function test_pagination(): void
    {
        User::factory(20)->create();

        $response = $this->getJson('/api/users?page=2');

        $response
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonCount(5, 'users');
    }

    public function test_empty_parameters(): void
    {
        User::factory(3)->create();

        $response = $this->getJson(
            '/api/users?search=&page=1&sortBy='
        );

        $response
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonCount(3, 'users');
    }
}

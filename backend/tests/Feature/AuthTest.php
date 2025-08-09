<?php

namespace Tests\Feature;

use Illuminate->Foundation->Testing->RefreshDatabase;
use Illuminate->Foundation->Testing->WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * 測試用戶註冊成功。
     *
     * @return void
     */
    public function test_user_can_register()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email']])
                 ->assertJsonFragment(['message' => 'User registered successfully']);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
        ]);
    }

    /**
     * 測試用戶註冊失敗 (Email 已存在)。
     *
     * @return void
     */
    public function test_user_cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => $this->faker->name,
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * 測試用戶登入成功。
     *
     * @return void
     */
    public function test_user_can_login()
    {
        $password = 'password123';
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt($password),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => $password,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'token', 'user'])
                 ->assertJsonFragment(['message' => 'Login successful']);

        $this->assertNotNull($response->json('token'));
    }

    /**
     * 測試用戶登入失敗 (密碼錯誤)。
     *
     * @return void
     */
    public function test_user_cannot_login_with_wrong_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    /**
     * 測試用戶登出。
     *
     * @return void
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Successfully logged out']);

        // 嘗試用已登出的 token 訪問受保護路由，應失敗
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/api/user');
        $response->assertStatus(401);
    }
}

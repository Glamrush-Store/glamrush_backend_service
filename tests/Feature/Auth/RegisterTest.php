<?php

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register with valid data', function () {
    $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['user' => ['id', 'name', 'email', 'phone', 'created_at']],
        ])
        ->assertJsonMissingPath('data.token')
        ->assertJsonPath('data.user.email', 'test@example.com');

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('user can register with optional phone', function () {
    $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.phone', '+1234567890');
});

test('registration fails with duplicate email', function () {
    User::create([
        'name' => 'Existing User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/v1/auth/register', [
        'name' => 'New User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration fails with invalid input', function () {
    $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

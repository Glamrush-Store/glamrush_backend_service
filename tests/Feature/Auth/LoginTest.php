<?php

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can login with correct credentials', function () {
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['user' => ['id', 'name', 'email', 'phone', 'created_at']],
        ])
        ->assertJsonMissingPath('data.token');

    $this->assertAuthenticatedAs(User::where('email', 'test@example.com')->first(), 'web');
});

test('login fails with wrong password', function () {
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Invalid credentials.');
});

test('login fails with unknown email', function () {
    $response = $this->withHeader('Origin', 'http://localhost:3000')->postJson('/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Invalid credentials.');
});

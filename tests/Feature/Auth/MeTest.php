<?php

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can fetch their profile', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+1234567890',
        'password' => bcrypt('password123'),
    ]);

    $this->actingAs($user, 'web');

    $response = $this->withHeader('Origin', 'http://localhost:3000')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'phone', 'created_at'],
        ])
        ->assertJsonPath('data.email', 'test@example.com')
        ->assertJsonPath('data.phone', '+1234567890');
});

test('unauthenticated request to me returns 401', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

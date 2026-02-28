<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can fetch their profile', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+1234567890',
        'password' => bcrypt('password123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/me');

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

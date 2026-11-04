<?php

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can logout', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->actingAs($user, 'web');

    $response = $this->withHeader('Origin', 'http://localhost:3000')
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(204);

    $this->assertGuest('web');
});

test('unauthenticated request to logout returns 401', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
});

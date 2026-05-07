<?php

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

function makeSocialUser(string $id, string $name, string $email): object
{
    $socialUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $socialUser->shouldReceive('getId')->andReturn($id);
    $socialUser->shouldReceive('getName')->andReturn($name);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->token = 'mock-access-token';
    $socialUser->refreshToken = null;
    $socialUser->expiresIn = null;

    return $socialUser;
}

function mockSocialiteDriver(object $socialUser): void
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('userFromToken')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

test('new social user is created and receives token', function () {
    $socialUser = makeSocialUser('google-uid-123', 'Google User', 'social@example.com');
    mockSocialiteDriver($socialUser);

    $response = $this->postJson('/api/v1/auth/social/google', [
        'token' => 'valid-google-token',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['token', 'user' => ['id', 'name', 'email', 'phone', 'created_at']],
        ]);

    expect(User::where('email', 'social@example.com')->exists())->toBeTrue();
});

test('existing social user receives token without creating new user', function () {
    $socialUser = makeSocialUser('google-uid-123', 'Google User', 'social@example.com');
    mockSocialiteDriver($socialUser);

    // First call creates the user
    $this->postJson('/api/v1/auth/social/google', ['token' => 'valid-google-token'])
        ->assertStatus(201);

    // Reset Socialite mock for second call
    Mockery::close();
    $socialUser2 = makeSocialUser('google-uid-123', 'Google User', 'social@example.com');
    mockSocialiteDriver($socialUser2);

    // Second call finds existing user
    $response = $this->postJson('/api/v1/auth/social/google', ['token' => 'valid-google-token']);

    $response->assertStatus(201);

    expect(User::where('email', 'social@example.com')->count())->toBe(1);
});

test('existing user by email gets social account linked', function () {
    $user = User::create([
        'name' => 'Existing User',
        'email' => 'social@example.com',
        'password' => bcrypt('password123'),
    ]);

    $socialUser = makeSocialUser('google-uid-456', 'Existing User', 'social@example.com');
    mockSocialiteDriver($socialUser);

    $response = $this->postJson('/api/v1/auth/social/google', [
        'token' => 'valid-google-token',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.email', 'social@example.com');

    expect(User::where('email', 'social@example.com')->count())->toBe(1);
});

test('invalid social token returns 422', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('userFromToken')->andThrow(new Exception('Invalid token'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->postJson('/api/v1/auth/social/google', [
        'token' => 'invalid-token',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

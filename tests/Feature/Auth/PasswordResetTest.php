<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('forgot password always returns 200', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertStatus(200);
});

test('forgot password sends code for existing user', function () {
    Notification::fake();

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('oldpassword'),
    ]);

    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(200);

    expect(DB::table('password_reset_codes')->where('email', 'test@example.com')->exists())->toBeTrue();
});

test('full password reset flow works', function () {
    Notification::fake();

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('oldpassword'),
    ]);

    // Step 1: Request code
    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'test@example.com',
    ])->assertStatus(200);

    // Retrieve the plain code from DB by checking against hash
    $record = DB::table('password_reset_codes')->where('email', 'test@example.com')->first();
    expect($record)->not->toBeNull();

    // We can't retrieve the plain code after hashing, so we insert a known code directly
    $plainCode = '123456';
    DB::table('password_reset_codes')->where('email', 'test@example.com')->update([
        'code' => Hash::make($plainCode),
        'expires_at' => Carbon::now()->addMinutes(15),
    ]);

    // Step 2: Verify code
    $this->postJson('/api/v1/auth/password/verify', [
        'email' => 'test@example.com',
        'code' => $plainCode,
    ])->assertStatus(200);

    // Step 3: Reset password
    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertStatus(200);

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    expect(DB::table('password_reset_codes')->where('email', 'test@example.com')->exists())->toBeFalse();
});

test('verify with wrong code returns 422', function () {
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('oldpassword'),
    ]);

    DB::table('password_reset_codes')->insert([
        'email' => 'test@example.com',
        'code' => Hash::make('123456'),
        'verified' => false,
        'expires_at' => Carbon::now()->addMinutes(15),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $this->postJson('/api/v1/auth/password/verify', [
        'email' => 'test@example.com',
        'code' => '999999',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('verify with expired code returns 422', function () {
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('oldpassword'),
    ]);

    DB::table('password_reset_codes')->insert([
        'email' => 'test@example.com',
        'code' => Hash::make('123456'),
        'verified' => false,
        'expires_at' => Carbon::now()->subMinutes(1),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $this->postJson('/api/v1/auth/password/verify', [
        'email' => 'test@example.com',
        'code' => '123456',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('reset without verification returns 422', function () {
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('oldpassword'),
    ]);

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

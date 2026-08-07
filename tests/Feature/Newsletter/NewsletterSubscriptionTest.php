<?php

use App\Domain\Newsletter\Enums\NewsletterSubscriptionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\NewsletterSubscriber;
use App\Mail\Newsletter\ConfirmNewsletterSubscriptionMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();
    RateLimiter::clear('');
});

test('a visitor can request a newsletter subscription', function () {
    $response = $this->postJson('/api/v1/newsletter/subscriptions', [
        'email' => '  Customer@Example.com ',
        'source' => 'storefront-footer',
    ]);

    $response->assertAccepted()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    $subscriber = NewsletterSubscriber::query()->sole();

    expect($subscriber->email)->toBe('customer@example.com')
        ->and($subscriber->status)->toBe(NewsletterSubscriptionStatus::Pending)
        ->and($subscriber->source)->toBe('storefront-footer')
        ->and($subscriber->confirmation_token_hash)->toHaveLength(64)
        ->and($subscriber->unsubscribe_token_hash)->toHaveLength(64)
        ->and($subscriber->consent_ip_hash)->toHaveLength(64)
        ->and($subscriber->consent_user_agent)->not->toBeNull();

    Mail::assertQueued(ConfirmNewsletterSubscriptionMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));
});

test('a subscriber can confirm using the emailed token', function () {
    $confirmationUrl = null;

    $this->postJson('/api/v1/newsletter/subscriptions', [
        'email' => 'customer@example.com',
    ])->assertAccepted();

    Mail::assertQueued(ConfirmNewsletterSubscriptionMail::class, function ($mail) use (&$confirmationUrl): bool {
        $confirmationUrl = $mail->confirmationUrl;

        return true;
    });

    $this->getJson($confirmationUrl)
        ->assertOk()
        ->assertJsonPath('data.status', 'subscribed');

    $subscriber = NewsletterSubscriber::query()->sole();

    expect($subscriber->status)->toBe(NewsletterSubscriptionStatus::Subscribed)
        ->and($subscriber->confirmed_at)->not->toBeNull();
});

test('an expired confirmation token is rejected', function () {
    $token = Str::random(64);

    NewsletterSubscriber::query()->create([
        'email' => 'customer@example.com',
        'status' => NewsletterSubscriptionStatus::Pending,
        'confirmation_token_hash' => hash('sha256', $token),
        'unsubscribe_token_hash' => hash('sha256', Str::random(64)),
        'confirmation_expires_at' => now()->subMinute(),
    ]);

    $this->getJson('/api/v1/newsletter/subscriptions/confirm/'.$token)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');
});

test('a subscription can be unsubscribed with its private token', function () {
    $token = Str::random(64);

    NewsletterSubscriber::query()->create([
        'email' => 'customer@example.com',
        'status' => NewsletterSubscriptionStatus::Subscribed,
        'confirmation_token_hash' => hash('sha256', Str::random(64)),
        'unsubscribe_token_hash' => hash('sha256', $token),
        'confirmation_expires_at' => now()->addDay(),
        'confirmed_at' => now(),
    ]);

    $this->postJson('/api/v1/newsletter/subscriptions/unsubscribe', [
        'token' => $token,
    ])->assertOk()
        ->assertJsonPath('data.status', 'unsubscribed');

    $subscriber = NewsletterSubscriber::query()->sole();

    expect($subscriber->status)->toBe(NewsletterSubscriptionStatus::Unsubscribed)
        ->and($subscriber->unsubscribed_at)->not->toBeNull();
});

test('subscribing again after unsubscribe requires confirmation again', function () {
    NewsletterSubscriber::query()->create([
        'email' => 'customer@example.com',
        'status' => NewsletterSubscriptionStatus::Unsubscribed,
        'unsubscribe_token_hash' => hash('sha256', Str::random(64)),
        'unsubscribed_at' => now(),
    ]);

    $this->postJson('/api/v1/newsletter/subscriptions', [
        'email' => 'customer@example.com',
    ])->assertAccepted();

    $subscriber = NewsletterSubscriber::query()->sole();

    expect($subscriber->status)->toBe(NewsletterSubscriptionStatus::Pending)
        ->and($subscriber->unsubscribed_at)->toBeNull();

    Mail::assertQueued(ConfirmNewsletterSubscriptionMail::class);
});

test('subscribing an existing subscriber is idempotent and does not send another email', function () {
    NewsletterSubscriber::query()->create([
        'email' => 'customer@example.com',
        'status' => NewsletterSubscriptionStatus::Subscribed,
        'unsubscribe_token_hash' => hash('sha256', Str::random(64)),
        'confirmed_at' => now(),
    ]);

    $this->postJson('/api/v1/newsletter/subscriptions', [
        'email' => 'customer@example.com',
    ])->assertAccepted();

    Mail::assertNothingQueued();
    expect(NewsletterSubscriber::query()->count())->toBe(1);
});

test('resend responses do not disclose whether an address is pending', function () {
    $this->postJson('/api/v1/newsletter/subscriptions/resend-confirmation', [
        'email' => 'unknown@example.com',
    ])->assertAccepted()
        ->assertJsonPath('data', null);

    Mail::assertNothingQueued();
});

test('newsletter input is validated', function () {
    $this->postJson('/api/v1/newsletter/subscriptions', [
        'email' => 'not-an-email',
        'source' => str_repeat('x', 101),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'source']);
});

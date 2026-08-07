<?php

namespace App\Domain\Newsletter\Services;

use App\Domain\Newsletter\Enums\NewsletterSubscriptionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\NewsletterSubscriber;
use App\Mail\Newsletter\ConfirmNewsletterSubscriptionMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class NewsletterSubscriptionService
{
    public function subscribe(
        string $email,
        ?string $source,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        $email = Str::lower(trim($email));
        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();

        if ($subscriber?->status === NewsletterSubscriptionStatus::Subscribed) {
            return;
        }

        $confirmationToken = Str::random(64);
        $attributes = [
            'status' => NewsletterSubscriptionStatus::Pending,
            'source' => $source,
            'confirmation_token_hash' => $this->hashToken($confirmationToken),
            'confirmation_expires_at' => now()->addMinutes(config('newsletter.confirmation_ttl_minutes')),
            'consented_at' => now(),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
            'consent_ip_hash' => $ipAddress
                ? hash_hmac('sha256', $ipAddress, (string) config('app.key'))
                : null,
            'consent_user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
        ];

        if ($subscriber) {
            $subscriber->update($attributes);
        } else {
            $subscriber = NewsletterSubscriber::query()->create([
                'email' => $email,
                'unsubscribe_token_hash' => $this->hashToken(Str::random(64)),
                ...$attributes,
            ]);
        }

        $this->sendConfirmation($subscriber, $confirmationToken);
    }

    public function resendConfirmation(string $email): void
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('email', Str::lower(trim($email)))
            ->where('status', NewsletterSubscriptionStatus::Pending->value)
            ->first();

        if (! $subscriber) {
            return;
        }

        $confirmationToken = Str::random(64);
        $subscriber->update([
            'confirmation_token_hash' => $this->hashToken($confirmationToken),
            'confirmation_expires_at' => now()->addMinutes(config('newsletter.confirmation_ttl_minutes')),
        ]);

        $this->sendConfirmation($subscriber, $confirmationToken);
    }

    public function confirm(string $token): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('confirmation_token_hash', $this->hashToken($token))
            ->first();

        if (! $subscriber || ($subscriber->confirmation_expires_at?->isPast() ?? true)) {
            throw ValidationException::withMessages([
                'token' => ['The confirmation link is invalid or has expired.'],
            ]);
        }

        if ($subscriber->status !== NewsletterSubscriptionStatus::Subscribed) {
            $subscriber->update([
                'status' => NewsletterSubscriptionStatus::Subscribed,
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        return $subscriber->refresh();
    }

    public function unsubscribe(string $token): void
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token_hash', $this->hashToken($token))
            ->first();

        if (! $subscriber) {
            throw ValidationException::withMessages([
                'token' => ['The unsubscribe token is invalid.'],
            ]);
        }

        if ($subscriber->status !== NewsletterSubscriptionStatus::Unsubscribed) {
            $subscriber->update([
                'status' => NewsletterSubscriptionStatus::Unsubscribed,
                'unsubscribed_at' => now(),
            ]);
        }
    }

    private function sendConfirmation(NewsletterSubscriber $subscriber, string $token): void
    {
        $url = route('newsletter.subscriptions.confirm', ['token' => $token]);

        Mail::to($subscriber->email)->queue(new ConfirmNewsletterSubscriptionMail($url));
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}

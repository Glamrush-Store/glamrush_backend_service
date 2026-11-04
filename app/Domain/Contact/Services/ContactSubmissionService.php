<?php

namespace App\Domain\Contact\Services;

use App\Infrastructure\Persistence\Eloquent\Models\ContactSubmission;
use App\Mail\Contact\ContactSubmissionReceivedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final class ContactSubmissionService
{
    public function submit(
        string $storefrontCategoryId,
        ?int $customerAccountId,
        array $data,
        ?string $ipAddress,
        ?string $userAgent,
    ): string {
        if (($data['website'] ?? '') !== '') {
            return (string) Str::ulid();
        }

        $ipHash = $ipAddress ? hash_hmac('sha256', $ipAddress, (string) config('app.key')) : null;
        $fingerprint = hash('sha256', implode('|', [
            $storefrontCategoryId,
            mb_strtolower($data['email']),
            mb_strtolower(preg_replace('/\s+/u', ' ', $data['message']) ?? $data['message']),
            $ipHash,
        ]));

        [$submission, $created] = DB::transaction(function () use ($storefrontCategoryId, $customerAccountId, $data, $ipHash, $fingerprint, $userAgent) {
            $submission = ContactSubmission::query()->firstOrCreate(
                ['duplicate_fingerprint' => $fingerprint, 'deduplication_bucket' => now()->format('YmdHi')],
                [
                    'storefront_category_id' => $storefrontCategoryId,
                    'customer_account_id' => $customerAccountId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'subject' => $data['subject'] ?? null,
                    'message' => $data['message'],
                    'status' => 'new',
                    'source' => $data['source'] ?? null,
                    'metadata' => array_filter([
                        'ip_hash' => $ipHash,
                        'user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
                    ], fn ($value) => $value !== null),
                ],
            );

            return [$submission, $submission->wasRecentlyCreated];
        });

        if ($created) {
            $this->queueNotification($submission);
        }

        return (string) $submission->id;
    }

    private function queueNotification(ContactSubmission $submission): void
    {
        $recipient = config('contact.notification_address');
        if (! is_string($recipient) || $recipient === '') {
            return;
        }

        try {
            Mail::to($recipient)->queue(new ContactSubmissionReceivedMail($submission));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

<?php

namespace App\Infrastructure\Payment\Flutterwave;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class FlutterwaveClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string)config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'), '/');
    }

    public function generateReference(): string
    {
        return 'TXN-' . Str::upper(Str::random(10)) . '-' . time();
    }

    public function initializePayment(array $data): array
    {
        try {
            return Http::withToken($this->secretKey())
                ->post("{$this->baseUrl}/payments", $data)
                ->json();
        } catch (Exception $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }

    private function secretKey(): string
    {
        $key = config('services.flutterwave.secret_key');

        if (!is_string($key) || $key === '') {
            throw new RuntimeException('Flutterwave secret key is not configured.');
        }

        return $key;
    }

    public function verifyTransaction(string $transactionId): array
    {
        return Http::withToken($this->secretKey())
            ->get("{$this->baseUrl}/transactions/{$transactionId}/verify")
            ->json();
    }

    public function verifyByReference(string $txRef): array
    {
        return Http::withToken($this->secretKey())
            ->get("{$this->baseUrl}/transactions", [
                'tx_ref' => $txRef,
            ])
            ->json();
    }

    public function verifyWebhookSignature(?string $signature): bool
    {
        $secretHash = config('services.flutterwave.secret_hash');

        if (!is_string($secretHash) || $secretHash === '' || $signature === null) {
            return false;
        }

        return hash_equals($secretHash, $signature);
    }

    public function getBanks(string $country = 'NG'): array
    {
        return Http::withToken($this->secretKey())
            ->get("{$this->baseUrl}/banks/{$country}")
            ->json();
    }

    public function verifyBankAccount(array $data): array
    {
        return Http::withToken($this->secretKey())
            ->post("{$this->baseUrl}/accounts/resolve", $data)
            ->json();
    }

    public function createSubAccount(array $data): array
    {
        return Http::withToken($this->secretKey())
            ->post("{$this->baseUrl}/subaccounts", $data)
            ->json();
    }

    public function getSubAccounts(): array
    {
        return Http::withToken($this->secretKey())
            ->get("{$this->baseUrl}/subaccounts")
            ->json();
    }

    public function initiateTransfer(array $data): array
    {
        return Http::withToken($this->secretKey())
            ->post("{$this->baseUrl}/transfers", [
                'account_bank' => $data['bank_code'],
                'account_number' => $data['account_number'],
                'amount' => $data['amount'] / 100,
                'currency' => $data['currency'] ?? 'NGN',
                'reference' => $data['reference'],
                'narration' => $data['narration'] ?? 'Payout',
            ])
            ->json();
    }

    public function verifyTransfer(string $id): array
    {
        return Http::withToken($this->secretKey())
            ->get("{$this->baseUrl}/transfers/{$id}")
            ->json();
    }
}

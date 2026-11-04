<?php

namespace App\Domain\Setting\Services;

final class NotificationRecipientResolver
{
    public function __construct(
        private readonly RuntimeSettingService $settings,
    ) {}

    /**
     * @param  list<string|null>  $additional
     * @return list<string>
     */
    public function resolve(string $settingKey, string $fallbackConfigKey, array $additional = []): array
    {
        $configured = $this->settings->value($settingKey, config($fallbackConfigKey, ''));
        $values = is_array($configured) ? $configured : preg_split('/[,;\r\n]+/', (string) $configured);

        return collect([...$additional, ...($values ?: [])])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }
}

<?php

return [
    'lock_seconds' => (int) env('IDEMPOTENCY_LOCK_SECONDS', 60),
    'wait_seconds' => (int) env('IDEMPOTENCY_WAIT_SECONDS', 10),
];

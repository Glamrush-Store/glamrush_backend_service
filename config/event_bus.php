<?php

return [
    'default' => env('EVENT_BUS_DRIVER', 'laravel'),

    'drivers' => [
        'laravel' => [],

        'rabbitmq' => [
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => (int) env('RABBITMQ_PORT', 5672),
            'username' => env('RABBITMQ_USERNAME', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
            'exchange' => env('RABBITMQ_EVENT_EXCHANGE', 'glamrush.events'),
            'queue' => env('RABBITMQ_EVENT_QUEUE', 'glamrush-backend.events'),
            'routing_key_prefix' => env('RABBITMQ_ROUTING_KEY_PREFIX', 'glamrush'),
            'binding_key' => env('RABBITMQ_BINDING_KEY', 'glamrush.#'),
            'prefetch_count' => (int) env('RABBITMQ_PREFETCH_COUNT', 10),
            'connection_timeout' => (float) env('RABBITMQ_CONNECTION_TIMEOUT', 3),
            'read_write_timeout' => (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 6),
            'confirm_timeout' => (float) env('RABBITMQ_CONFIRM_TIMEOUT', 5),
            'heartbeat' => (int) env('RABBITMQ_HEARTBEAT', 30),
            'ssl' => (bool) env('RABBITMQ_SSL', false),
            'ssl_options' => [
                'cafile' => env('RABBITMQ_SSL_CA_FILE'),
                'local_cert' => env('RABBITMQ_SSL_CERT_FILE'),
                'local_key' => env('RABBITMQ_SSL_KEY_FILE'),
                'verify_peer' => (bool) env('RABBITMQ_SSL_VERIFY_PEER', true),
            ],
        ],

        'kafka' => [
            'brokers' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('KAFKA_BROKERS', '127.0.0.1:9092')),
            ))),
            'topic' => env('KAFKA_EVENT_TOPIC', 'glamrush.events'),
            'consumer_group' => env('KAFKA_CONSUMER_GROUP', 'glamrush-backend'),
            'auto_offset_reset' => env('KAFKA_AUTO_OFFSET_RESET', 'earliest'),
            'consume_timeout_ms' => (int) env('KAFKA_CONSUME_TIMEOUT_MS', 1000),
            'flush_timeout_ms' => (int) env('KAFKA_FLUSH_TIMEOUT_MS', 10000),
            'options' => [
                'security.protocol' => env('KAFKA_SECURITY_PROTOCOL'),
                'sasl.mechanisms' => env('KAFKA_SASL_MECHANISMS'),
                'sasl.username' => env('KAFKA_SASL_USERNAME'),
                'sasl.password' => env('KAFKA_SASL_PASSWORD'),
                'ssl.ca.location' => env('KAFKA_SSL_CA_LOCATION'),
            ],
        ],
    ],
];

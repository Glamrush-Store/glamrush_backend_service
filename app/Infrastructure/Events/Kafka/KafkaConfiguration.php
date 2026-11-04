<?php

namespace App\Infrastructure\Events\Kafka;

use LogicException;
use RdKafka\Conf;

final class KafkaConfiguration
{
    public static function producer(array $config): Conf
    {
        self::ensureExtensionIsLoaded();

        $conf = new Conf;
        $conf->set('metadata.broker.list', implode(',', $config['brokers']));
        $conf->set('acks', 'all');
        $conf->set('enable.idempotence', 'true');

        self::apply($conf, $config['options'] ?? []);

        return $conf;
    }

    public static function consumer(array $config): Conf
    {
        self::ensureExtensionIsLoaded();

        $conf = new Conf;
        $conf->set('metadata.broker.list', implode(',', $config['brokers']));
        $conf->set('group.id', $config['consumer_group']);
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.offset.reset', $config['auto_offset_reset']);

        self::apply($conf, $config['options'] ?? []);

        return $conf;
    }

    private static function ensureExtensionIsLoaded(): void
    {
        if (! extension_loaded('rdkafka')) {
            throw new LogicException(
                'The Kafka event driver requires the rdkafka PHP extension. '.
                'Install librdkafka and enable ext-rdkafka before selecting EVENT_BUS_DRIVER=kafka.',
            );
        }
    }

    private static function apply(Conf $conf, array $options): void
    {
        foreach ($options as $name => $value) {
            if ($value !== null && $value !== '') {
                $conf->set((string) $name, (string) $value);
            }
        }
    }
}

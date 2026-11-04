<?php

namespace App\Infrastructure\Events\RabbitMq;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;

final readonly class RabbitMqConnectionFactory
{
    public function __construct(private array $config) {}

    public function connect(): AbstractConnection
    {
        $config = new AMQPConnectionConfig;
        $config->setHost($this->config['host']);
        $config->setPort($this->config['port']);
        $config->setUser($this->config['username']);
        $config->setPassword($this->config['password']);
        $config->setVhost($this->config['vhost']);
        $config->setConnectionTimeout($this->config['connection_timeout']);
        $config->setReadTimeout($this->config['read_write_timeout']);
        $config->setWriteTimeout($this->config['read_write_timeout']);
        $config->setHeartbeat($this->config['heartbeat']);
        $config->setIsSecure((bool) ($this->config['ssl'] ?? false));

        if ($config->isSecure()) {
            $ssl = $this->config['ssl_options'] ?? [];
            $config->setSslCaCert($ssl['cafile'] ?: null);
            $config->setSslCert($ssl['local_cert'] ?: null);
            $config->setSslKey($ssl['local_key'] ?: null);
            $config->setSslVerify((bool) ($ssl['verify_peer'] ?? true));
            $config->setSslVerifyName((bool) ($ssl['verify_peer'] ?? true));
        }

        return AMQPConnectionFactory::create($config);
    }
}

<?php

namespace OpenEMR\Library\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// require_once __DIR__ . '/../../vendor/autoload.php';

class RabbitMQService
{
    private $connection;
    private $channel;
    private $exchange = "emr_exchange";

    public function __construct()
    {
        // Read RabbitMQ URL from environment variable
        $rabbitmqUrl = $_ENV['RABBITMQ_URL'] ?? 'amqp://admin:admin@localhost:5672';

        // Parse the URL
        $parsedUrl = parse_url($rabbitmqUrl);
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = $parsedUrl['port'] ?? 5672;
        $user = $parsedUrl['user'] ?? 'admin';
        $password = $parsedUrl['pass'] ?? 'admin';
        $vhost = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '/';

        // Establish connection
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channel = $this->connection->channel();

        $this->channel->exchange_declare($this->exchange, "direct", false, true, false);

    }

    public function sendMessage($data, string $routingKey)
    {
        try {

            $msg = new AMQPMessage(
                json_encode($data),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $this->channel->basic_publish($msg, $this->exchange, $routingKey);
            error_log("Message sent to exchange: {$this->exchange} with routing key: {$routingKey}");

        } catch (\Exception $e) {
            error_log("Failed to publish message: " . $e->getMessage());
            throw $e;
        }
    }

    public function receiveMessages(string $queueName, callable $callback)
    {
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->queue_bind($queueName, $this->exchange, $queueName);

        $this->channel->basic_consume(
            $queueName,    // queue name
            '',           // consumer tag
            false,        // no local
            false,         // no ack
            false,        // exclusive
            false,        // no wait
            $callback     // callback function
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }


    public function close()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

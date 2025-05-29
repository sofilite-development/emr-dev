<?php

require_once(__DIR__ . "/../../src/Common/Session/SessionUtil.php");
OpenEMR\Common\Session\SessionUtil::portalSessionStart();

// Set site ID before any database operations
$site_id = 'default';
if (empty($site_id) || preg_match('/[^A-Za-z0-9\\-.]/', $site_id)) {
    die("Site ID '" . htmlspecialchars($site_id, ENT_NOQUOTES) . "' contains invalid characters.");
}
$_SESSION['site_id'] = $site_id;

// Skip authentication for this script
$ignoreAuth = true;

require_once(__DIR__ . "/RabbitMQService.php");
require_once __DIR__ . '/../../vendor/autoload.php';
require_once(__DIR__ . "/../../interface/globals.php");
require_once(__DIR__ . "/../../library/sql.inc.php");


use OpenEMR\Library\RabbitMQ\RabbitMQService;

function handleProcessedOrder()
{

    echo "Connecting to RabbitMQ...\n";
    $rabbitmq = new RabbitMQService();
    echo "Connected.\n";

    try {
        $callback = function ($msg) {
            // echo "Callback triggered!\n";
            $data = json_decode($msg->body, true);
            // echo "Received message: " . $msg->body . "\n";

            if (!$data) {
                error_log("Failed to parse anatomical panel JSON data");
                $msg->reject(false); // Reject the message without requeue
                return;
            }

            if (!$data["externalOrderId"]) {
                error_log("externalOrderId is required");
                $msg->reject(false); // Reject the message without requeue
                return;
            }

            try {
                // Check if order exists
                $existingOrder = sqlQuery(
                    "SELECT id FROM processed_order WHERE order_id = ?",
                    array($data['externalOrderId'])
                );

                if ($existingOrder) {
                    // Update existing order
                    $sql = "UPDATE processed_order SET order_data = ? WHERE order_id = ?";
                    sqlStatement($sql, array(
                        json_encode($data),
                        $data['externalOrderId']
                    ));
                    echo "Updated existing order ID: " . "\n";
                } else {
                    // Insert new order
                    $sql = "INSERT INTO processed_order (order_id, order_data) VALUES (?, ?)";
                    sqlStatement($sql, array(
                        $data['externalOrderId'],
                        json_encode($data)
                    ));
                    echo "Created new order ID: " . "\n";
                }

                // Acknowledge the message
                $msg->ack();
                echo "Successfully processed order ID: " . $data['externalOrderId'] . "\n";
            } catch (Exception $dbError) {
                error_log("Database Error: " . $dbError->getMessage());
                // In case of database error, reject the message
                $msg->reject(false);
            }
        };

        echo "Listening for messages...\n";
        $rabbitmq->receiveMessages('order_processed_by_lab', $callback);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        error_log("RabbitMQ Consumer Error: " . $e->getMessage());
    } finally {
        $rabbitmq->close();
        echo "Closed connection.\n";
    }
}

handleProcessedOrder()

    ?>
<?php
session_start();
$_GET['site'] = 'default';
$ignoreAuth = true;
$fake_register_globals = false;
require_once(__DIR__ . '/../../../../interface/globals.php');
require_once(__DIR__ . '/helper.php');
require_once(__DIR__ . '/../../../../vendor/autoload.php');

$secretKey      = $_ENV['STRIPE_SECRET_KEY'] ?? '';
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? "";

\Stripe\Stripe::setApiKey($secretKey);

header('Content-Type: application/json');

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit('Invalid payload');
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit('Invalid signature');
}

$paymentIntent = $event->data->object;
switch ($event->type) {
    case 'payment_intent.succeeded':
        // error_log("✅ Payment succeeded for: " . $paymentIntent->id);
        break;

    case 'payment_intent.payment_failed':
        $error = $paymentIntent->last_payment_error->message ?? 'Unknown error';
        error_log("❌ Payment failed: $error");
        break;

    case 'charge.succeeded':
        $meta = $paymentIntent->metadata;
        $pid = $meta['patient_id'] ?? null;
        $encounter = $meta['encounter_id'] ?? null;
        $amount = $meta["amount"];

        if ($pid && $amount > 0) {
            $result = recordStripeFrontPaymentWithActivity($pid, $amount, $encounter);
            // error_log("✅ Stripe payment posted to EMR, session_id=$result");
        } else {
            error_log("⚠️ Missing PID or amount in metadata");
        }
        break;
    default:
        error_log("Unhandled event type: " . $event->type);
}

http_response_code(200);

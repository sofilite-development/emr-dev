<?php
require_once(__DIR__ . '/../../../../vendor/autoload.php');
require_once(__DIR__ . '/../helper.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../');
$dotenv->load();

// ✅ Load environment config
$secretKey      = $_ENV['STRIPE_SECRET_KEY'] ?? '';
$apiVersion     = $_ENV['STRIPE_API_VERSION'] ?? '2022-11-15';
$currency       = $_ENV['CURRENCY'] ?? 'usd';
$publishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? "";



\Stripe\Stripe::setApiKey($secretKey);

header('Content-Type: application/json');

// ✅ Your webhook secret from Stripe Dashboard

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    // ✅ Verify event is from Stripe
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit('Invalid payload');
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit('Invalid signature');
}

// ✅ Handle the event type
switch ($event->type) {
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;

        $customerId = $paymentIntent->customer;
        $amount = $paymentIntent->amount_received;
        $paymentIntentId = $paymentIntent->id;

        // ✅ You can link to encounter by metadata, or use customer mapping in DB
        // updateEncounterPaid($customerId, $amount, $paymentIntentId);

        // Log or update your database
        error_log("✅ Payment succeeded for: $paymentIntentId");

        break;

    case 'payment_intent.payment_failed':
        $error = $event->data->object->last_payment_error->message ?? 'Unknown error';
        error_log("❌ Payment failed: " . $error);
        break;

    default:
        // Log unhandled event
        error_log("Unhandled event type: " . $event->type);
}

http_response_code(200);

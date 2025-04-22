<?php
require_once(__DIR__ . "/../../../verify_session.php");
require_once(__DIR__ . '/../helper.php');

$secretKey = $_ENV['STRIPE_SECRET_KEY'];

\Stripe\Stripe::setApiKey($secretKey);
header('Content-Type: application/json');

$api_version = $_ENV['STRIPE_API_VERSION'];
$currency = $_ENV['CURRENCY'];
$publishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'];

$pid = $_SESSION['pid'];
$encounterId = $_GET['encounter_id'] ?? "";

try {

    $encounter = getEncounterBilling($pid, $encounterId);
    $amountsEncounter = $encounter["totals"];
    $dueAmount = $amountsEncounter["due"] * 100; //convert to cents

    // Step 1: Create Customer
    $customer = \Stripe\Customer::create();

    // Step 2: Create Ephemeral Key
    $ephemeralKey = \Stripe\EphemeralKey::create(
        ['customer' => $customer->id],
        ['stripe_version' => $api_version]
    );

    // Step 3: Create Payment Intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $dueAmount,
        'currency' => $currency,
        'customer' => $customer->id,
        'automatic_payment_methods' => ['enabled' => true],
    ]);

    // Step 4: Return credentials to frontend
    echo json_encode([
        'paymentIntent' => $paymentIntent->client_secret,
        'ephemeralKey' => $ephemeralKey->secret,
        'customer' => $customer->id,
        'publishableKey' => $publishableKey
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

<?php
require_once(__DIR__ . "/../../../verify_session.php");
require_once(__DIR__ . '/../helper.php');
require_once(__DIR__ . '/../../../../vendor/autoload.php'); // Ensure Stripe is loaded

header('Content-Type: application/json');

// ✅ Load environment config
$secretKey      = $_ENV['STRIPE_SECRET_KEY'] ?? '';
$apiVersion     = $_ENV['STRIPE_API_VERSION'] ?? '2022-11-15';
$currency       = $_ENV['CURRENCY'] ?? 'usd';
$publishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';

// ✅ Validate environment variables
if (!$secretKey || !$publishableKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe environment variables are not configured.']);
    exit;
}

\Stripe\Stripe::setApiKey($secretKey);

// ✅ Validate session and input
$pid = $_SESSION['pid'] ?? null;
$encounterId = $_GET['encounter_id'] ?? null;

if (!$pid || !$encounterId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing patient ID or encounter ID.']);
    exit;
}

// ✅ Fetch encounter billing
$encounter = getEncounterBilling($pid, $encounterId);
if (!$encounter || !isset($encounter['totals']['due'])) {
    http_response_code(404);
    echo json_encode(['error' => 'Encounter billing info not found.']);
    exit;
}

// ✅ Convert due to cents and validate
$dueAmount = (int) ($encounter['totals']['due'] * 100);
if ($dueAmount < 50) { // Stripe minimum is usually 0.50 USD
    http_response_code(400);
    echo json_encode(['error' => 'Payment amount is too low.']);
    exit;
}

try {
    // ✅ Step 1: Create customer
    $customer = \Stripe\Customer::create();

    // ✅ Step 2: Create ephemeral key
    $ephemeralKey = \Stripe\EphemeralKey::create(
        ['customer' => $customer->id],
        ['stripe_version' => $apiVersion]
    );

    // ✅ Step 3: Create payment intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $dueAmount,
        'currency' => $currency,
        'customer' => $customer->id,
        'automatic_payment_methods' => ['enabled' => true],
        'metadata' => [
            'amount' => (float) $encounter["totals"]["due"],
            'encounter_id' => (int) $encounterId,
            'from' => "apk",
            "patient_id" => (int) $pid
        ]
    ]);

    // ✅ Step 4: Return credentials
    echo json_encode([
        'paymentIntent' => $paymentIntent->client_secret,
        'ephemeralKey' => $ephemeralKey->secret,
        'customer' => $customer->id,
        'publishableKey' => $publishableKey
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

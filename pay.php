<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Taarifa za PalmPesa API
define('PALMPESA_BASE_URL', 'https://palmpesa.drmlelwa.co.tz');
define('PALMPESA_MERCHANT_ID', '487');
define('PALMPESA_API_KEY', 'uzczuHsDoFwPyQlJAyS3nBSzp2JSIxkrrXUR8opop9591mY6Qsfiklkh2M0q');

// ⚠️ BADILISHA HAPA: Weka Link halisi ya server yako ambapo utaweka webhook.php
define('WEBHOOK_URL', 'https://mrogbando.vercel.app/api/webhook.php');

// KAMA NI MAOMBI YA KUKAGUA STATUS (Polling Check)
if (isset($_GET['check_status'])) {
    $orderId = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['check_status']);
    $logFile = __DIR__ . '/palmpesa_webhook.log';
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        if (str_contains($logs, "SUCCESS processed for: $orderId")) {
            echo json_encode(['success' => true, 'status' => 'SUCCESS']);
            exit();
        }
    }
    echo json_encode(['success' => false, 'status' => 'PENDING']);
    exit();
}

// KUANZA PROCESS YA STK PUSH
$input = json_decode(file_get_contents('php://input'), true);

$phone  = $input['phone'] ?? null;
$amount = $input['amount'] ?? null;
$plan   = $input['plan'] ?? 'HOTSPOT';
$name   = $input['name'] ?? 'MR OG Customer';

if (!$phone || !$amount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Namba ya simu na kiasi vinahitajika.']);
    exit();
}

// Safisha namba ya simu kwenda 255XXXXXXXXX
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);
if (str_starts_with($cleanPhone, '0')) {
    $cleanPhone = '255' . substr($cleanPhone, 1);
}

if (count(explode(' ', trim($name))) < 2) {
    $name = trim($name) . " Customer";
}

$orderId = 'MROG_' . $plan . '_' . time() . '_' . rand(100, 999);

$payload = [
    'user_id'        => PALMPESA_MERCHANT_ID,
    'name'           => $name,
    'email'          => $input['email'] ?? 'user@mrogtool.com',
    'phone'          => $cleanPhone,
    'amount'         => (int)$amount,
    'transaction_id' => $orderId,
    'address'        => 'Tanzania',
    'postcode'       => '00000',
    'buyer_uuid'     => PALMPESA_MERCHANT_ID,
    'webhook'        => WEBHOOK_URL
];

$ch = curl_init(PALMPESA_BASE_URL . '/api/pay-via-mobile');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PALMPESA_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json'
    ]
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Network error: ' . $curlError]);
    exit();
}

$result = json_decode($response, true);
$msg    = strtolower($result['message'] ?? '');

if ($httpCode === 200 || str_contains($msg, 'sent') || str_contains($msg, 'success')) {
    echo json_encode([
        'success'  => true,
        'order_id' => $orderId,
        'message'  => "Ombi la malipo limetumwa kwenye namba $cleanPhone. Weka PIN kukamilisha."
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'Imeshindwa kuanzisha STK Push.'
    ]);
}
?>

<?php
header('Content-Type: application/json');

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Hifadhi logi ya data inayoingia kwa ajili ya ukaguzi
file_put_contents('palmpesa_webhook.log', date('[Y-m-d H:i:s] ') . $rawData . PHP_EOL, FILE_APPEND);

if (!$data) {
    echo json_encode(['status' => 'INVALID_JSON']);
    exit();
}

// Kuchukua status na Order ID kutoka PalmPesa
$status  = $data['status'] ?? $data['trade_status'] ?? $data['resultcode'] ?? $data['transaction_status'] ?? '';
$orderId = $data['orderId'] ?? $data['merchant_order_id'] ?? $data['transaction_id'] ?? $data['reference'] ?? '';

$statusUpper = strtoupper(trim((string)$status));
$isSuccess   = in_array($statusUpper, ['SUCCESS', 'COMPLETED', 'TRADE_SUCCESS', '000', '0', 'APPROVED', 'OK']);

if ($isSuccess && !empty($orderId)) {
    // Andika ushuhuda wa ushindi kwenye Log File ili pay.php ijue malipo yamekamilika
    file_put_contents('palmpesa_webhook.log', date('[Y-m-d H:i:s] ') . "SUCCESS processed for: $orderId" . PHP_EOL, FILE_APPEND);
}

// Daima mrudishie PalmPesa majibu chanya
echo json_encode(['status' => 'SUCCESS']);
?>
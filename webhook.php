<?php
header('Content-Type: application/json');

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

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
    // Malipo yamefanikiwa! 
    // Hapa ndipo MikroTik RouterOS API au database inatakiwa ipokee taarifa za kufungua bando.
}

// Daima mrudishie PalmPesa majibu chanya
echo json_encode(['status' => 'SUCCESS']);
?>

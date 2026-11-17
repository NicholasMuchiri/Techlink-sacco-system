
<?php

// Receive Safaricom STK Push callback
$rawData = file_get_contents('php://input');

// Save incoming callback for testing
$logFile = __DIR__ . '/callback_log.txt';

file_put_contents(
    $logFile,
    "\n\n" . date('Y-m-d H:i:s') . "\n" . $rawData,
    FILE_APPEND | LOCK_EX
);

// Decode JSON
$data = json_decode($rawData, true);

$result = $data['Body']['stkCallback'] ?? null;

if ($result !== null) {

    $checkoutID = $result['CheckoutRequestID'] ?? '';
    $resultCode = $result['ResultCode'] ?? null;
    $description = $result['ResultDesc'] ?? '';

    $status = ($resultCode === 0) ? 'SUCCESS' : 'FAILED';

    $summary = [
        'CheckoutRequestID' => $checkoutID,
        'ResultCode' => $resultCode,
        'ResultDesc' => $description,
        'Status' => $status
    ];

    file_put_contents(
        $logFile,
        "\n" . json_encode($summary, JSON_PRETTY_PRINT),
        FILE_APPEND | LOCK_EX
    );
}

// Acknowledge callback
http_response_code(200);

header('Content-Type: application/json');

echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Accepted'
]);

?>

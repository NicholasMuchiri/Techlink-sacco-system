
<?php
require_once 'config.php';

// Customer details (temporary test values)
$phone = '254788344012';
$amount = 1000000;

// Generate access token
$credentials = base64_encode(
    MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET
);

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => MPESA_BASE_URL .
    '/oauth/v1/generate?grant_type=client_credentials',
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credentials
    ],
    CURLOPT_RETURNTRANSFER => true
]); 

$tokenResponse = curl_exec($curl);

if ($tokenResponse === false) {
    die('Token error: ' . curl_error($curl));
}

curl_close($curl);

$tokenData = json_decode($tokenResponse, true);

if (empty($tokenData['access_token'])) {
    die('Could not generate token: ' . htmlspecialchars($tokenResponse));
}

$accessToken = $tokenData['access_token'];

// Generate timestamp and password
$timestamp = date('YmdHis');

$password = base64_encode(
    MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp
);

// STK Push request
$payload = [
    'BusinessShortCode' => MPESA_SHORTCODE,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => MPESA_SHORTCODE,
    'PhoneNumber' => $phone,
    'CallBackURL' => 'https://example.com/callback.php',
    'AccountReference' => 'TECHLINK',
    'TransactionDesc' => 'Sacco payment'
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => MPESA_BASE_URL .
    '/mpesa/stkpush/v1/processrequest',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($curl);

if ($response === false) {
    die('STK error: ' . curl_error($curl));
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "HTTP Status: " . $httpCode;
echo '<pre>';
echo htmlspecialchars($response);
echo '</pre>';
?>

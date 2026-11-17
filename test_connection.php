
<?php

require_once 'config.php';

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

$response = curl_exec($curl);

if ($response === false) {
    die('Error: ' . curl_error($curl));
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "HTTP Status: " . $httpCode;
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";

?>

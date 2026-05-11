<?php
header('Content-Type: application/json');

$logFile = __DIR__ . '/../contact.log';

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    logMsg("ERROR: .env file not found at $envPath");
    http_response_code(500);
    echo json_encode(['error' => '.env not found']);
    exit;
}

$env = parse_ini_file($envPath);
$BREVO_KEY = $env['BREVO_KEY'] ?? '';
$TO_EMAIL = 'miguel.diez@zeid10.com';
$FROM_EMAIL = 'miguel.diez@zeid10.com';

if (empty($BREVO_KEY)) {
    logMsg("ERROR: BREVO_KEY is empty");
    http_response_code(500);
    echo json_encode(['error' => 'API key missing']);
    exit;
}

logMsg("BREVO_KEY loaded (length: " . strlen($BREVO_KEY) . ")");

$input = json_decode(file_get_contents('php://input'), true);
logMsg("Input received: " . json_encode($input));

// Honeypot check
if (!empty($input['website'])) {
    logMsg("Honeypot triggered");
    echo json_encode(['ok' => true]);
    exit;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$message) {
    logMsg("ERROR: Missing fields - name:'$name' email:'$email' message:'$message'");
    http_response_code(400);
    echo json_encode(['error' => 'All fields required']);
    exit;
}

$data = [
    'sender' => ['name' => $name, 'email' => $FROM_EMAIL],
    'to' => [['email' => $TO_EMAIL]],
    'replyTo' => ['email' => $email],
    'subject' => "[Straplio] Contact from $name",
    'textContent' => "Name: $name\nEmail: $email\n\n$message"
];

logMsg("Sending to Brevo: " . json_encode($data));

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'content-type: application/json',
        "api-key: $BREVO_KEY"
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

logMsg("Brevo response (HTTP $httpCode): $response");
if ($curlError) {
    logMsg("CURL error: $curlError");
}

if ($httpCode >= 200 && $httpCode < 300) {
    logMsg("SUCCESS");
    echo json_encode(['ok' => true]);
} else {
    logMsg("FAILED");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send', 'debug' => $response]);
}

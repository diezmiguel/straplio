<?php
header('Content-Type: application/json');

$env = parse_ini_file(__DIR__ . '/../.env');
$BREVO_KEY = $env['BREVO_KEY'] ?? '';
$TO_EMAIL = 'miguel.diez@zeid10.com';
$FROM_EMAIL = 'miguel.diez@zeid10.com';

$input = json_decode(file_get_contents('php://input'), true);

// Honeypot check
if (!empty($input['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$message) {
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send']);
}

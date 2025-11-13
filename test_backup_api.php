<?php

// اختبار API للنسخ الاحتياطي اليدوي
// تأكد من تسجيل الدخول أولاً والحصول على Bearer Token

$apiUrl = 'http://localhost/msar-backend-12/public/api/backup/run';
$token = 'YOUR_BEARER_TOKEN_HERE'; // ضع الـ token هنا

$data = [
    'backup_type' => 'db' // أو 'files' أو 'both'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);

echo "Calling API: $apiUrl\n";
echo "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

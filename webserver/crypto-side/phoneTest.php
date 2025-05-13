<?php

$phoneNumber = '+19737676191'; // Replace with a valid phone number for testing
$textbeltEndpoint = 'https://textbelt.com/text';
$textbeltKey = 'ceebcfc81aa421fa088ecda7a8b6278dfb0eec38kke1V4X1XqDqTswtVX3NtpeuP'; // Using the free key

$message = "This is a test message from your crypto alert system!";

$ch = curl_init($textbeltEndpoint);
$data = array(
    'phone' => $phoneNumber,
    'message' => $message,
    'key' => $textbeltKey,
);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo 'Response from Textbelt: ' . $response;
}

curl_close($ch);

?>




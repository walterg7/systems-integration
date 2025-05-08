<?php
require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RabbitMQ\RabbitMQClient;
require '/var/www/rabbitmqphp_example/vendor/autoload.php';

echo "Starting script\n";

// Textbelt API endpoint
$textbeltEndpoint = 'https://textbelt.com/text';
$textbeltKey = 'textbelt'; // Using the free key as per their example

// Function to send email via PHPMailer (no changes needed)
function send_email($email, $message) {
    $mail = new PHPMailer(true);
    echo "Sending email to $email...\n";
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mikebutryn123@gmail.com';
        $mail->Password   = 'chhurfaapxwlbwqo'; //not my password :)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('alertcrypto@gmail.com', 'Crypto Alert');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Crypto Alert';
        $mail->Body  = $message;

        if ($mail->send()) {
            echo "Email sent successfully!\n";
            return true;
        } else {
            echo "Email not sent. Error: " . $mail->ErrorInfo . "\n";
            return false;
        }
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        echo "Email sending failed: " . $mail->ErrorInfo . "\n";
        return false;
    }
}

// Function to send SMS via Textbelt
function send_sms($phoneNumber, $message) {
    global $textbeltEndpoint, $textbeltKey;

    echo "Sending SMS to $phoneNumber via Textbelt...\n";

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false) {
        $error = curl_error($ch);
        error_log("Textbelt error for $phoneNumber: $error");
        echo "Textbelt error for $phoneNumber: $error\n";
        return false;
    }

    $responseData = json_decode($response, true);

    if ($responseData && $responseData['success']) {
        echo "SMS sent to $phoneNumber via Textbelt successfully!\n";
        return true;
    } else {
        error_log("Textbelt failed to send to $phoneNumber: " . json_encode($responseData));
        echo "Textbelt failed to send to $phoneNumber: " . json_encode($responseData) . "\n";
        return false;
    }
}

// Function to monitor price change
function check_price_change($symbol, $email, $notificationPreference, $phoneNumber = null) {
    echo "Checking price for $symbol...\n";
    $client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

    $request = ['action' => 'get_coin_price', 'symbol' => $symbol, 'email' => $email];
    $response = json_decode($client->sendRequest($request), true);

    // troubleshooting
    if (!$response) {
        echo "Failed to decode response\n";
        return;
    }

    if ($response && isset($response['price'])) {
        if ($response['price_changed']) {
            $old_price = $response['old_price'];
            $new_price = $response['price'];

            $message = "The price of $symbol changed from $old_price to $new_price.\n";
            $message .= "Market Cap: {$response['market_cap']}\n";
            $message .= "Supply: {$response['supply']}\nMax Supply: {$response['max_supply']}\n";
            $message .= "24h Volume: {$response['volume']}\nChange (24h): {$response['change_percent']}%\n";
            $message .= "Last Updated: {$response['last_updated']}";

            echo "Price change detected for $symbol!\n";

            if ($notificationPreference === 'email') {
                send_email($email, nl2br($message));
            } elseif ($notificationPreference === 'sms' && $phoneNumber) {
                send_sms($phoneNumber, $message);
            } else {
                echo "No notification preference set or phone number missing for SMS for $symbol.\n";
            }

        } else {
            echo "No price change for $symbol.\n";
        }
    } else {
        echo "Failed to retrieve price for $symbol\n";
    }
}

// Ensure script runs only when executed from CLI with arguments
if (isset($argv[1]) && isset($argv[2]) && isset($argv[3]) && isset($argv[4])) {
    $symbol = $argv[1];
    $email = $argv[2];
    $notificationPreference = $argv[3];
    $phoneNumber = $argv[4];

    while(true) {
        check_price_change($symbol, $email, $notificationPreference, $phoneNumber);
        sleep(30);
    }
} else {
    echo "Please enter the coin symbol, email, notification preference, and phone number as arguments\n";
    echo "Example: php check_price.php BTC test@email.com sms 5555555555\n";
}




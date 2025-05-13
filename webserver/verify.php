<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');

use Twilio\Rest\Client;
use RabbitMQ\RabbitMQClient;


$sid   = "AC465b81ce44333cbda684b93cd0e9695c";
$token = "26a3b610f72fc0d94b35d6f642f579fb";
$verifyServiceSid = "VA4e4e07159da1c6d135dc958d81cc56a2";

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.html');
    exit();
}

$username = $_SESSION['username'];
$client = new RabbitMQClient(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

$errorMessage = "";
$verificationSent = false;
$userPhoneNumber = null;
$changingPhoneNumber = isset($_GET['change_phone']);


if (isset($_POST['skip_2fa'])) {
    $_SESSION['is_verified'] = true;
    header('Location: crypto-side/home.php');
    exit();
}


if (!$changingPhoneNumber) {
    $request = json_encode([
        'action' => 'get_phonenum',
        'username' => $username
    ]);
    $response = $client->sendRequest($request);
    $response_decoded = json_decode($response, true);

    if ($response_decoded && isset($response_decoded['phonenum'])) {
        $userPhoneNumber = $response_decoded['phonenum'];
    } else {
        $errorMessage = "Could not retrieve phone number.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_phone_number']) && $changingPhoneNumber) {

        $newPhoneNumber = $_POST['new_phone_number'];
        if (substr($newPhoneNumber, 0, 2) !== '+1') {
            $newPhoneNumber = '+1' . preg_replace('/[^0-9]/', '', $newPhoneNumber);
        }
        $_SESSION['phone_number_to_verify'] = $newPhoneNumber;
        $userPhoneNumber = $newPhoneNumber;
        $changingPhoneNumber = false;
    } elseif (isset($_POST['phone_number']) && !$changingPhoneNumber && !isset($_POST['verification_code'])) {

        $phoneNumberToSend = $_POST['phone_number'];
        $_SESSION['phone_number_to_verify'] = $phoneNumberToSend;

        $twilio = new Client($sid, $token);

        try {
            $verification = $twilio->verify->v2->services($verifyServiceSid)
                                             ->verifications
                                             ->create($phoneNumberToSend, "sms");
            $verificationSent = true;
        } catch (\Twilio\Exceptions\RestException $e) {
            $errorMessage = "Error sending verification code: " . $e->getMessage();
        }
    } elseif (isset($_POST['verification_code']) && isset($_SESSION['phone_number_to_verify'])) {

        $verificationCode = $_POST['verification_code'];
        $phoneNumber = $_SESSION['phone_number_to_verify'];

        $twilio = new Client($sid, $token);

        try {
            $verificationCheck = $twilio->verify->v2->services($verifyServiceSid)
                                                 ->verificationChecks
                                                 ->create([
                                                     'to' => $phoneNumber,
                                                     'code' => $verificationCode
                                                 ]);

            if ($verificationCheck->status === 'approved') {
                unset($_SESSION['pending_2fa']);
                $_SESSION['is_verified'] = true;

                header('Location: crypto-side/home.php');
                exit();
            }


            else {
                $errorMessage = "Invalid verification code. Please try again.";
            }
        } catch (\Twilio\Exceptions\RestException $e) {
            $errorMessage = "Error verifying code: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Please enter your phone number to start verification.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Phone Number Verification</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-image: url('https://assets.euromoneydigital.com/dims4/default/cee9178/2147483647/strip/true/crop/800x450+0+20/resize/1200x675!/quality/90/?url=http%3A%2F%2Feuromoney-brightspot.s3.amazonaws.com%2Ffc%2F1c%2F8c6efc9a63666ed6bd5963e4b54f%2Fdigital.jpg');
        background-size: cover;
        background-position: center center;
        background-attachment: fixed;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }
    .verification-container {
        width: 90%;
        max-width: 400px;
        padding: 30px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        text-align: center;
    }
    h2 {
        color: #333;
        margin-bottom: 20px;
    }
    .error-message {
        color: #dc3545;
        margin-bottom: 15px;
        font-weight: bold;
    }
    .phone-display {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #e9ecef;
        border-radius: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .change-button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9em;
    }
    .change-button:hover {
        background-color: #0056b3;
    }
    .input-group {
        margin-bottom: 20px;
        text-align: left;
    }
    .input-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }
    .input-group input[type="tel"],
    .input-group input[type="text"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        font-size: 1em;
    }
    button[type="submit"] {
        background-color: #28a745;
        color: #fff;
        border: none;
        padding: 12px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        width: 100%;
        display: block;
        margin-top: 10px;
    }
    button[type="submit"]:hover {
        background-color: #218838;
    }
    .verification-sent-message {
        margin-top: 20px;
        color: #28a745;
        font-weight: bold;
    }
    .logout-link {
        display: block;
        margin-top: 25px;
        color: orange;
        text-decoration: none;
        font-weight: bold;
        font-size: 0.9em;
    }
    .logout-link:hover {
        text-decoration: underline;
    }
    .skip-button {
        background-color: #ffc107;
        color: #333;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        margin-top: 20px;
        width: 100%;
        display: block;
        box-sizing: border-box;
    }
    .skip-button:hover {
        background-color: #e0a800;
    }
</style>

</head>
<body>
    <div class="verification-container">
        <h2>Phone Number Verification</h2>

        <?php if ($errorMessage): ?>
            <p class="error-message"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <?php if ($changingPhoneNumber): ?>
            <form method="post">
                <div class="input-group">
                    <label for="new_phone_number">Enter New Phone Number (+1):</label>
                    <input type="tel" id="new_phone_number" name="new_phone_number" placeholder="+1">
                </div>
                <button type="submit">Update Phone Number</button>
            </form>
            <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="logout-link" style="color: blue;">Cancel Change</a></p>
        <?php elseif ($userPhoneNumber && !$verificationSent && !isset($_SESSION['phone_number_to_verify'])): ?>
            <div class="phone-display">
                Your current phone number: <strong><?php echo $userPhoneNumber; ?></strong>
                <button class="change-button" onclick="window.location.href='?change_phone=true'">Change</button>
            </div>
            <form method="post">
                <input type="hidden" name="phone_number" value="<?php echo $userPhoneNumber; ?>">
                <button type="submit">Send Code to This Number</button>
            </form>
        <?php elseif (!$userPhoneNumber && !$verificationSent && !isset($_SESSION['phone_number_to_verify'])): ?>
            <form method="post">
                <div class="input-group">
                    <label for="phone_number">Enter Your Phone Number (+1):</label>
                    <input type="tel" id="phone_number" name="phone_number" placeholder="+1" required>
                </div>
                <button type="submit">Send Code</button>
            </form>
        <?php elseif ($verificationSent || isset($_SESSION['phone_number_to_verify'])): ?>
            <form method="post">
                <div class="input-group">
                    <label for="verification_code">Enter Verification Code:</label>
                    <input type="text" id="verification_code" name="verification_code" required>
                </div>
                <button type="submit">Verify Code</button>
            </form>
            <p class="verification-sent-message">A verification code has been sent to <?php echo $_SESSION['phone_number_to_verify'] ?? $userPhoneNumber ?? 'your phone number'; ?>.</p>
        <?php endif; ?>

        <a href="?logout=true" class="logout-link">Logout and Clear Session</a>
    </div>
</body>
</html>



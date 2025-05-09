<?php
// notifications.php
session_start();
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['is_verified']) ||
    $_SESSION['is_verified'] !== true
) {
    header("Location: /index.html");
    exit();

}
$username = $_SESSION['username'];


require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RabbitMQ\RabbitMQClient;
require '/var/www/rabbitmqphp_example/vendor/autoload.php';


// PHPMailer
function send_email($email, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mikebutryn123@gmail.com';
        $mail->Password   = 'chhurfaapxwlbwqo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('alertcrypto@gmail.com', 'Crypto Alert');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Crypto Alert';
        $mail->Body  = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = $_POST['symbol'];
    $email = $_POST['email'];
    $notificationPreference = $_POST['notification_preference'] ?? 'email';
    $phoneNumber = $_POST['phone_number'] ?? null;

    $_SESSION['alerts'][] = [
        'symbol' => $symbol,
        'email' => $email,
        'username' => $username,
        'created_at' => date("Y-m-d H:i:s"),
        'notification_preference' => $notificationPreference,
        'phone_number' => $phoneNumber
    ];

    if ($notificationPreference === 'email') {
        send_email($email, "Alert set for $symbol! You will be notified of price changes via email.");
    } elseif ($notificationPreference === 'sms' && $phoneNumber) {
        $message = "Alert set for $symbol! You will be notified of price changes via SMS at $phoneNumber.";
        echo '<div class="alert alert-success mt-3">' . htmlspecialchars($message) . '</div>';
    }


    // Troubleshooting
    // Extract symbol from $symbol
    if (preg_match('/\((.*?)\)/', $symbol, $matches)) {
        $symbol = trim($matches[1]);
    }


    $check_price_script = '/var/www/webserver/crypto-side/check_price.php';
    $log_file = '/var/www/webserver/crypto-side/log.txt';


    $cmd = "nohup php $check_price_script $symbol $email \"$notificationPreference\" \"$phoneNumber\" > $log_file /dev/null 2>&1 &";
    exec($cmd, $output, $return_var);


    file_put_contents($log_file, "Executed: $cmd\nReturn: $return_var\nOutput: " . implode("\n", $output) . "\n", FILE_APPEND);
}


$alerts = $_SESSION['alerts'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/notifications.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .suggestions-box {
        border: 1px solid #ccc;
        background-color: #f9f9f9;
        position: absolute;
        width: 90%;
        z-index: 1000;
    }
    .suggestion-item {
        padding: 8px;
        cursor: pointer;
    }
    .suggestion-item:hover {
        background-color: #e9ecef;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Crypto Website</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
	<li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="compare.php">Compare</a></li>
        <li class="nav-item"><a class="nav-link" href="trade.php">Trade</a></li>
        <li class="nav-item"><a class="nav-link" href="portfolio.php">Portfolio</a></li>
    <li class="nav-item"><a class="nav-link" href="rss.php">News</a></li>
      </ul>
      <span class="navbar-text">
        <?= htmlspecialchars($username) ?>
        <a href="../logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </span>
    </div>
  </div>
</nav>

<div class="container">
    <h2>Set a Coin Alert</h2>
    <?php if (isset($message)): ?>
        <p style="color: green;"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="symbol">Coin Symbol (e.g., BTC):</label>
        <input type="text" id="symbol" name="symbol" required>
        <div id="suggestions" class="suggestions-box"></div>

        <label for="email">Your Email:</label>
        <input type="email" name="email" required>

        <div>
            <label>Notification Preference:</label><br>
            <input type="radio" id="email_pref" name="notification_preference" value="email" checked>
            <label for="email_pref">Email</label><br>
            <input type="radio" id="sms_pref" name="notification_preference" value="sms">
            <label for="sms_pref">SMS</label><br>
        </div>

        <div id="phone_number_field" style="display: none;">
            <label for="phone_number">Your Phone Number:</label>
            <input type="tel" id="phone_number" name="phone_number" placeholder="Enter phone number">
        </div>

        <button type="submit">Set Alert</button>
    </form>
</div>

<div class="container">
    <h2>My Active Alerts</h2>
    <table>
        <thead>
            <tr>
                <th>Coin</th>
                <th>Email</th>
                <th>Notification</th>
                <th>Phone (if SMS)</th>
                <th>Set On</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="5">No active alerts.</td></tr>
            <?php else: ?>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= htmlspecialchars($alert['symbol']) ?></td>
                        <td><?= htmlspecialchars($alert['email']) ?></td>
                        <td><?= htmlspecialchars($alert['notification_preference']) ?></td>
                        <td><?= htmlspecialchars($alert['phone_number']) ?></td>
                        <td><?= date("Y-m-d H:i", strtotime($alert['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const smsRadio = document.getElementById('sms_pref');
        const emailRadio = document.getElementById('email_pref');
        const phoneNumberField = document.getElementById('phone_number_field');

        smsRadio.addEventListener('change', function() {
            phoneNumberField.style.display = this.checked ? 'block' : 'none';
        });

        emailRadio.addEventListener('change', function() {
            phoneNumberField.style.display = this.checked ? 'none' : 'block';
        });
    });
</script>
<script src="js/egg.js"></script>

</body>
</html>




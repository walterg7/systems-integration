<?php
// notifications.php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../index.html');
    exit();
}
$username = $_SESSION['username'];


require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RabbitMQ\RabbitMQClient;
require '/var/www/rabbitmqphp_example/vendor/autoload.php';


// Function to send email via PHPMailer
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
        $mail->Body    = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}


// Handle alert form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = $_POST['symbol'];
    $email = $_POST['email'];


    $_SESSION['alerts'][] = [
        'symbol' => $symbol,
        'email' => $email,
        'username' => $username,
        'created_at' => date("Y-m-d H:i:s")
    ];


    // Send confirmation email when an alert is set
    send_email($email, "Alert set for $symbol! You will be notified of price changes.");


    // Troubleshooting
    // Extract symbol from $symbol
    if (preg_match('/\((.*?)\)/', $symbol, $matches)) {
        $symbol = trim($matches[1]); 
    }


    // Start background process for checking price, using absolute path
    $check_price_script = '/var/www/webserver/crypto-side/check_price.php';
    $log_file = '/var/www/webserver/crypto-side/log.txt';


    $cmd = "nohup php $check_price_script $symbol $email > $log_file /dev/null 2>&1 &";
    exec($cmd, $output, $return_var);


    file_put_contents($log_file, "Executed: $cmd\nReturn: $return_var\nOutput: " . implode("\n", $output) . "\n", FILE_APPEND);
}


// Fetch active alerts
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
<!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Crypto Website</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
       <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
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

<!-- Set a Coin Alert Form -->
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


        <button type="submit">Set Alert</button>
    </form>
</div>


<!-- Active Alerts -->
<div class="container">
    <h2>My Active Alerts</h2>
    <table>
        <thead>
            <tr>
                <th>Coin</th>
                <th>Email</th> 
                <th>Set On</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="3">No active alerts.</td></tr>
            <?php else: ?>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= htmlspecialchars($alert['symbol']) ?></td>
                        <td><?= htmlspecialchars($alert['email']) ?></td> 
                        <td><?= date("Y-m-d H:i", strtotime($alert['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>


</body>
</html>




<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

// portfolio.php
include 'config.php';
require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../index.html'); // Redirect to login if no session
    exit();
}

use RabbitMQ\RabbitMQClient;

$username = $_SESSION['username'];
$client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

// Fetch crypto data from DB
$crypto_request = json_encode([
    'action' => 'getTop100Crypto',
]);

$crypto_response = json_decode($client->sendRequest($crypto_request), true);

if ($crypto_response['status'] === 'success' && isset($crypto_response['data'])) {
    $crypto = $crypto_response['data']; 
} else {
    echo "Error: Unable to fetch cryptocurrency data.";
    exit();
}
 
// Fetch portfolio
$portfolio_request = json_encode([
    'action' => 'get_portfolio',
    'username' => $username
]);
$portfolio_response = json_decode($client->sendRequest($portfolio_request), true);

$portfolio = $portfolio_response['status'] === 'success' ? $portfolio_response['portfolio'] : [];

// Fetch balance
$balance_request = json_encode([
    'action' => 'get_balance',
    'username' => $username
]);
$balance_response = json_decode($client->sendRequest($balance_request), true);

$balance = $balance_response['status'] === 'success' ? $balance_response['balance'] : 0.00;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds'])) {
    $fund_request = json_encode([
        'action' => 'add_funds',
        'username' => $username,
        'amount' => 1000.00 // Example: Adding $1000 in fake funds
    ]);
    $fund_response = json_decode($client->sendRequest($fund_request), true);

    if ($fund_response['status'] === 'success') {
        $balance = $fund_response['new_balance']; // Update balance

    } else {
        $balance_error = "Failed to add funds.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/portfolio.js" defer></script>
</head>
<body>
<div class="navbar">
    <div class="nav-left">
        <a href="home.php">Home</a>
        <a href="trade.php">Trade</a>
        <a href="notifications.php">Notifications</a>
        <a href="rss.php">News</a>
    </div>

    <div class="nav-right">
        <span>Welcome, <?= htmlspecialchars($username); ?></span>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <h2>My Portfolio</h2>
    <table>
        <thead>
            <tr>
                <th>Coin</th>
                <th>Quantity</th>
                <th>Avg. Price (USD)</th>
                <th>Current Price (USD)</th>
                <th>Total Value (USD)</th>
                <th>Gain/Loss</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($portfolio as $coin): 
                foreach ($crypto as $crypto_data) {
                    if (strtolower($coin['coin_symbol']) === strtolower($crypto_data['symbol'])) {
                        $current_price = $crypto_data['priceUsd'];
                        break; 
                    }
                }
                $total_value = $coin['quantity'] * $current_price;
                $gain_loss = ($current_price - $coin['average_price']) * $coin['quantity'];
                $gain_loss_percentage = ($coin['average_price'] > 0) ? ($gain_loss / ($coin['average_price'] * $coin['quantity'])) * 100 : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($coin['coin_name']) ?> (<?= htmlspecialchars($coin['coin_symbol']) ?>)</td>
                <td><?= number_format($coin['quantity'], 4) ?></td>
                <td>$<?= number_format($coin['average_price'], 2) ?></td>
                <td>$<?= number_format($current_price, 2) ?></td>
                <td>$<?= number_format($total_value, 2) ?></td>
                <td style="color: <?= $gain_loss >= 0 ? 'green' : 'red' ?>;">
                    <?= number_format($gain_loss, 2) ?> (<?= number_format($gain_loss_percentage, 2) ?>%)
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="balance-info">
    <p>Current Balance: $<?= number_format($balance, 2) ?></p>
    <form method="POST">
        <input type="hidden" name="add_funds" value="1">
        <button type="submit" class="add-funds-btn">Add Funds</button>
    </form>
</div>

</body>
</html>

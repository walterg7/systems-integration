<?php
//ini_set('display_errors', 1);
//include 'config.php';
require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../index.html');
    exit();
}
$username = $_SESSION['username'];
use RabbitMQ\RabbitMQClient;


$client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['action']) && ($data['action'] === 'buy' || $data['action'] === 'sell')) {
        $data['username'] = $username;
        $request = json_encode($data);
        $response = json_decode($client->sendRequest($request), true);
        echo json_encode($response);
        exit;
    }
}


// Fetch transactions
$transaction_request = json_encode([
    'action' => 'getTransactions',
    'username' => $username
]);
$transaction_response = json_decode($client->sendRequest($transaction_request), true);


$transactions = $transaction_response['status'] === 'success' ? $transaction_response['transactions'] : [];


$portfolio_request = json_encode([
    'action' => 'get_portfolio',
    'username' => $username
]);
$portfolio_response = json_decode($client->sendRequest($portfolio_request), true);
$portfolio = $portfolio_response['status'] === 'success' ? $portfolio_response['portfolio'] : [];


$balance_request = json_encode([
    'action' => 'get_balance',
    'username' => $username
]);
$balance_response = json_decode($client->sendRequest($balance_request), true);
$balance = $balance_response['status'] === 'success' ? $balance_response['balance'] : 0;


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade</title>
<!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/trade.js" defer></script>
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
        <li class="nav-item"><a class="nav-link" href="portfolio.php">Portfolio</a></li>
        <li class="nav-item"><a class="nav-link" href="notifications.php">Notifications</a></li>
        <li class="nav-item"><a class="nav-link" href="rss.php">News</a></li>
      </ul>
      <span class="navbar-text">
        <?= htmlspecialchars($username) ?>
         <a href="../logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </span>
    </div>
  </div>
</nav>


<!-- Trading Form -->
<div class="container">
    <h2>Trading Crypto</h2>
    <form id="trade-form">
        <label for="coin">Select Coin:</label>
        <input type="text" id="coin" placeholder="e.g., Bitcoin (BTC)" autocomplete="off">
        <div id="suggestions" class="suggestions-box"></div>


        <label for="amount">Amount:</label>
        <input type="number" id="amount" step="0.0001" placeholder="Enter amount">


        <label>Type:</label>
        <input type="radio" name="trade-type" value="buy" checked> Buy
        <input type="radio" name="trade-type" value="sell"> Sell


        <p>Total Price: <span id="total-price">$0.00</span></p>


        <button type="submit">Execute Trade</button>
    </form>
</div>


<!-- Transaction History -->
<div class="container">
    <h2>Past Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>Coin</th>
                <th>Amount</th>
                <th>Price</th>
                <th>Type</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody id="transaction-history">
            <?php
            if ($transactions) {
                foreach ($transactions as $transaction) {
                    echo "<tr>
                            <td>{$transaction['coin_symbol']} ({$transaction['coin_name']})</td>
                            <td>{$transaction['amount']}</td>
                            <td>\${$transaction['price']}</td>
                            <td>{$transaction['action']}</td>
                            <td>{$transaction['timestamp']}</td>
                          </tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>


<script src="js/trade.js"></script>
</body>
</html>

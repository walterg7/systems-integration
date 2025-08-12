<?php
// compare.php
require_once(__DIR__ . '/../../RabbitMQ/RabbitMQLib.inc');
session_start();
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['is_verified']) ||
    $_SESSION['is_verified'] !== true
) {
    header("Location: /index.html");
    exit();

}

use RabbitMQ\RabbitMQClient;

$client = new RabbitMQClient(__DIR__ . '/../../RabbitMQ/RabbitMQ.ini', 'Database');


$crypto_request = json_encode([
    'action' => 'getTop100Crypto',
]);
$crypto_response = json_decode($client->sendRequest($crypto_request), true);

$crypto = [];
if ($crypto_response['status'] === 'success' && isset($crypto_response['data'])) {
    $crypto = $crypto_response['data'];
} else {
    echo "Error: Unable to fetch cryptocurrency data.";
}

$coin1_data = null;
$coin2_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['coin1_symbol']) && isset($_POST['coin2_symbol'])) {
        $coin1_symbol = strtolower($_POST['coin1_symbol']);
        $coin2_symbol = strtolower($_POST['coin2_symbol']);

        foreach ($crypto as $coin) {
            if (strtolower($coin['symbol']) === $coin1_symbol) {
                $coin1_data = $coin;
            }
            if (strtolower($coin['symbol']) === $coin2_symbol) {
                $coin2_data = $coin;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coin Comparison</title>
    <link rel="stylesheet" href="/../css/crypto.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .comparison-table th, .comparison-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .comparison-table th {
            background-color: #4db3a8;
        }
        .coin-select-form {
            margin-bottom: 20px;
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
		<li class="nav-item"><a class="nav-link" href="trade.php">Trade</a></li>
                <li class="nav-item"><a class="nav-link" href="portfolio.php">Portfolio</a></li>
		<li class="nav-item"><a class="nav-link" href="notifications.php">Notifications</a></li>
                <li class="nav-item"><a class="nav-link" href="forum.php">Forum</a></li>
                <li class="nav-item"><a class="nav-link" href="rss.php">News</a></li>
            </ul>
            <span class="navbar-text">
                <?= htmlspecialchars($_SESSION['username']) ?>
                <a href="../logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
            </span>
        </div>
    </div>
</nav>

<div class="container">
    <h2>Compare Cryptocurrencies</h2>
<form method="POST" class="coin-select-form">
    <div id="coin-selection-container">
        <div class="row mb-3 coin-selection">
            <div class="col-md-6">
                <label for="coin1_input" class="form-label">Select Coin:</label>
                <input class="form-control" list="coin_list" name="coin_symbol[]" placeholder="Type to search...">
                <datalist id="coin_list">
                    <?php foreach ($crypto as $coin): ?>
                        <option value="<?= htmlspecialchars($coin['symbol']) ?>"><?= htmlspecialchars($coin['name']) ?></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <?php if (isset($_POST['coin_symbol']) && count($_POST['coin_symbol']) > 1): ?>
                <?php for ($i = 1; $i < count($_POST['coin_symbol']); $i++): ?>
                    <div class="col-md-6">
                        <label for="coin<?= $i + 1 ?>_input" class="form-label">Select Coin:</label>
                        <input class="form-control" list="coin_list" name="coin_symbol[]" placeholder="Type to search..." value="<?= htmlspecialchars($_POST['coin_symbol'][$i]) ?>">
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
    <button type="button" id="add-coin-button" class="btn btn-secondary mb-3">Add Coin</button>
    <button type="submit" class="btn btn-primary">Compare</button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addCoinButton = document.getElementById('add-coin-button');
        const coinSelectionContainer = document.getElementById('coin-selection-container');

        addCoinButton.addEventListener('click', function() {
            const newCoinDiv = document.createElement('div');
            newCoinDiv.classList.add('row', 'mb-3', 'coin-selection');
            newCoinDiv.innerHTML = `
                <div class="col-md-6">
                    <label class="form-label">Select Coin:</label>
                    <input class="form-control" list="coin_list" name="coin_symbol[]" placeholder="Type to search...">
                </div>
            `;
            coinSelectionContainer.appendChild(newCoinDiv);
        });
    });
</script>
<script src="/../js/egg.js"></script>

<?php if (isset($_POST['coin_symbol']) && is_array($_POST['coin_symbol']) && count($_POST['coin_symbol']) > 0): ?>
    <h3 class="mt-4">Comparison</h3>
    <table class="comparison-table">
        <thead>
            <tr>
                <th>Metric</th>
                <?php
                $compared_coins_data = [];
                foreach ($_POST['coin_symbol'] as $symbol) {
                    foreach ($crypto as $coin) {
                        if (strtolower($coin['symbol']) === strtolower($symbol)) {
                            $compared_coins_data[] = $coin;
                            echo '<th>' . htmlspecialchars($coin['name']) . ' (' . htmlspecialchars($coin['symbol']) . ')</th>';
                            break;
                        }
                    }
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Current Price (USD)</td>
                <?php foreach ($compared_coins_data as $coin_data): ?>
                    <td>$<?= number_format($coin_data['priceUsd'], 2) ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Market Cap (USD)</td>
                <?php foreach ($compared_coins_data as $coin_data): ?>
                    <td>$<?= number_format($coin_data['marketCapUsd'], 0) ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>24h Volume (USD)</td>
                <?php foreach ($compared_coins_data as $coin_data): ?>
                    <td>$<?= number_format($coin_data['volumeUsd24Hr'], 0) ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Change (24h)</td>
                <?php foreach ($compared_coins_data as $coin_data): ?>
                    <td style="color: <?= $coin_data['changePercent24Hr'] >= 0 ? 'green' : 'red' ?>"><?= number_format($coin_data['changePercent24Hr'], 2) ?>%</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Supply</td>
                <?php foreach ($compared_coins_data as $coin_data): ?>
                    <td><?= number_format($coin_data['supply'], 0) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php if (isset($compared_coins_data[0]['maxSupply']) || array_some($compared_coins_data, function ($c) { return isset($c['maxSupply']); })): ?>
                <tr>
                    <td>Max Supply</td>
                    <?php foreach ($compared_coins_data as $coin_data): ?>
                        <td><?= isset($coin_data['maxSupply']) ? number_format($coin_data['maxSupply'], 0) : 'N/A' ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p>Please select at least one coin to compare.</p>
<?php endif; ?>


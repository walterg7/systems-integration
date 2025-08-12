<?php

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

$username = $_SESSION['username'];
$client = new RabbitMQClient(__DIR__ . '/../../RabbitMQ/RabbitMQ.ini', 'Database');

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
$balance_response = json_decode($client->sendRequest($balance_response), true);

$balance = $balance_response['status'] === 'success' ? $balance_response['balance'] : 0.00;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds'])) {
	$fund_request = json_encode([
    	'action' => 'add_funds',
    	'username' => $username,
    	'amount' => 1000.00
	]);
	$fund_response = json_decode($client->sendRequest($fund_request), true);

	if ($fund_response['status'] === 'success') {
    	$balance = $fund_response['new_balance'];
	} else {
    	$balance_error = "Failed to add funds.";
	}
}

$total_portfolio_value = 0;
$weighted_risk_sum = 0;

foreach ($portfolio as $coin) {
	foreach ($crypto as $crypto_data) {
    	if (strtolower($coin['coin_symbol']) === strtolower($crypto_data['symbol'])) {
        	$current_price = $crypto_data['priceUsd'];
        	$total_value = $coin['quantity'] * $current_price;
        	$total_portfolio_value += $total_value;
       	 
        	$risk_factor = abs(floatval($crypto_data['changePercent24Hr']));
        	$weighted_risk_sum += $risk_factor * $total_value;
        	$coin['change_percent'] = floatval($crypto_data['changePercent24Hr']);
        	break;
    	}
	}
}

$overall_risk = $total_portfolio_value > 0 ? $weighted_risk_sum / $total_portfolio_value : 0.0;

$risk_level_text = '';
if ($overall_risk < 1) {
	$risk_level_text = 'Low';
} elseif ($overall_risk < 3) {
	$risk_level_text = 'Medium';
} else {
	$risk_level_text = 'High';
}

usort($crypto, function ($a, $b) {
    return floatval($b['changePercent24Hr']) <=> floatval($a['changePercent24Hr']);
});

$safer_recommendations = array_slice(array_filter($crypto, function ($c) {
    $change = floatval($c['changePercent24Hr']);
    return $change <= 0.5 && $change >= -0.5;
}), 0, 5);

$riskier_recommendations = array_slice(array_filter($crypto, function ($c) {
    $change = floatval($c['changePercent24Hr']);
    return ($change >= 1 && $change <= 10) || ($change <= -1 && $change >= -10);
}), 0, 5);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Portfolio</title>
	<link rel="stylesheet" href="/../css/crypto.css">
	<script src="/../js/portfolio.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<style>
    	.recommendations {
        	margin-top: 20px;
        	border: 1px solid #ddd;
        	padding: 15px;
        	border-radius: 5px;
    	}
    	.recommendation-list {
        	list-style: none;
        	padding: 0;
    	}
    	.recommendation-list li {
        	margin-bottom: 5px;
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
    	<li class="nav-item"><a class="nav-link" href="notifications.php">Notifications</a></li>
            	<li class="nav-item"><a class="nav-link" href="forum.php">Forum</a></li>
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

	<div class="balance-info">
    	<p>Current Balance: $<?= number_format($balance, 2) ?></p>
    	<form method="POST">
        	<input type="hidden" name="add_funds" value="1">
        	<button type="submit" class="add-funds-btn">Add Funds</button>
    	</form>
	</div>

	<div class="risk-assessment">
    	<h3>Portfolio Risk Assessment</h3>
    	<p>Overall Risk Level: <strong><?= $risk_level_text ?></strong></p>
	</div>

	<div class="recommendations">
    	<h3>Coin Recommendations</h3>
    	<div id="recommendation-type">
        	<button id="safer-coins-btn" class="btn btn-outline-primary btn-sm active">Safer Coins</button>
        	<button id="riskier-coins-btn" class="btn btn-outline-primary btn-sm">Riskier Coins</button>
    	</div>
    	<ul id="coin-recommendations" class="recommendation-list">
        	<?php foreach ($safer_recommendations as $recommendation): ?>
            	<li><?= htmlspecialchars($recommendation['name']) ?> (<?= htmlspecialchars($recommendation['symbol']) ?>) (Change: <?= htmlspecialchars(number_format($recommendation['changePercent24Hr'], 2)) ?>%)</li>
        	<?php endforeach; ?>
    	</ul>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
    	const saferCoinsBtn = document.getElementById('safer-coins-btn');
    	const riskierCoinsBtn = document.getElementById('riskier-coins-btn');
    	const coinRecommendationsList = document.getElementById('coin-recommendations');

    	const safeRecommendations = <?php echo json_encode($safer_recommendations); ?>;
    	const riskyRecommendations = <?php echo json_encode($riskier_recommendations); ?>;

    	function displayRecommendations(recommendations) {
        	coinRecommendationsList.innerHTML = '';
        	if (recommendations.length > 0) {
            	recommendations.forEach(coin => {
                	const li = document.createElement('li');
                	li.textContent = `${coin.name} (${coin.symbol}) (Change: ${parseFloat(coin.changePercent24Hr).toFixed(2)}%)`;
                	coinRecommendationsList.appendChild(li);
            	});
        	} else {
            	const li = document.createElement('li');
            	li.textContent = 'No recommendations available based on current volatility.';
            	coinRecommendationsList.appendChild(li);
        	}
    	}

    	saferCoinsBtn.addEventListener('click', function() {
        	saferCoinsBtn.classList.add('active');
        	riskierCoinsBtn.classList.remove('active');
        	displayRecommendations(safeRecommendations);
    	});

    	riskierCoinsBtn.addEventListener('click', function() {
        	riskierCoinsBtn.classList.add('active');
        	saferCoinsBtn.classList.remove('active');
        	displayRecommendations(riskyRecommendations);
    	});

    	displayRecommendations(safeRecommendations);
	});
</script>
<script src="/../js/egg.js"></script>

</body>
</html>

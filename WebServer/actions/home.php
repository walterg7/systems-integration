<?php
// home.php
//include 'config.php';
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


$recommended_coins = isset($_SESSION['recommended_coins']) ? $_SESSION['recommended_coins'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Website</title>
<!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="/../css/crypto.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Add Chart.js -->
    <script src="/../js/app.js" defer></script>
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
        <li class="nav-item"><a class="nav-link" href="compare.php">Compare</a></li>
        <li class="nav-item"><a class="nav-link" href="trade.php">Trade</a></li>
        <li class="nav-item"><a class="nav-link" href="portfolio.php">Portfolio</a></li>
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
    <h2>Live Market Cryptocurrencies</h2>
    <input type="text" id="search-bar" placeholder="Search for a coin...">
    <label><input type="checkbox" id="filter-market-cap"> High Market Cap</label>
    <label><input type="checkbox" id="filter-positive-change"> Positive 24h Change</label>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Price (USD)</th>
                <th>24h Change (%)</th>
            </tr>
        </thead>
        <tbody id="crypto-list">
            <tr><td colspan="4">Loading...</td></tr>
        </tbody>
    </table>
</div>

	<div id="graphModal" style="display: none;">
    <div class="modal-content">
        <span id="closeModal">&times;</span>
        <h2>Price History of <span id="coin-name"></span></h2>
        <canvas id="coin-graph" width="400" height="200"></canvas>
	<canvas id="coin-graph" class="w-100" style="max-width: 100%; height: 300px;"></canvas>
	</div>
	  </div>
	   </div>
        <div id="coin-details">
            
             <p>Market Cap: <span id="market-cap">Loading...</span></p>
   	     <p>24h Trading Volume: <span id="trading-volume">Loading...</span></p>
   	     <p>Circulating Supply: <span id="circulating-supply">Loading...</span></p>
   	     <p>Rank: <span id="rank">Loading...</span></p>
        </div>
    </div>
</div>



<div class="container">
    <h2>Keep an Eye On</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Price (USD)</th>
                <th>24h Change (%)</th>
            </tr>
        </thead>
        <tbody id="watchlist">
            <?php if (empty($recommended_coins)): ?>
                <tr><td colspan="4">No recommended coins yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recommended_coins as $coin): ?>
                    <tr>
                        <td><?= htmlspecialchars($coin['rank']); ?></td>
                        <td><?= htmlspecialchars($coin['name']); ?> (<?= htmlspecialchars($coin['symbol']); ?>)</td>
                        <td>$<?= number_format($coin['priceUsd'], 2); ?></td>
                        <td><?= number_format($coin['changePercent24Hr'], 2); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="/../js/egg.js"></script>
</body>
</html>

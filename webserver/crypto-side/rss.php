<?php

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto News Feed</title>
    <link rel="stylesheet" href="css/TestyDashy.css">
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
        <li class="nav-item"><a class="nav-link" href="notifications.php">Notifications</a></li>
       
      </ul>
      <span class="navbar-text">
        <?= htmlspecialchars($username) ?>
         <a href="../logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </span>
    </div>
  </div>
</nav>

    <div id="news-container"></div>
    <button id="load-more" onclick="loadMoreNews()">Load More</button>

    <script src="js/newsJuice.js"></script>
</body>
</html>


<?php
require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
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
$client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

$pageTitle = "Forum";


$topics_request = json_encode(['action' => 'get_topics']);
$topics_response = json_decode($client->sendRequest($topics_request), true);
$topics = $topics_response['status'] === 'success' ? $topics_response['topics'] : [];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_new_topic'])) {
    $title = $_POST['topic_title'];
    $content = $_POST['topic_content'];

    if (!empty($title) && !empty($content)) {
        $create_topic_request = json_encode([
            'action' => 'create_topic',
            'username' => $username,
            'title' => $title,
            'content' => $content
        ]);
        $create_topic_response = json_decode($client->sendRequest($create_topic_request), true);

        if ($create_topic_response['status'] === 'success') {
            header("Location: forum.php");
            exit();
        } else {
            $error_message = $create_topic_response['message'];
        }
    } else {
        $error_message = "Please enter a title and content for your topic.";
    }
}


$showCreateForm = isset($_POST['show_create_form']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Crypto Website'; ?></title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/portfolio.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
                <li class="nav-item"><a class="nav-link" href="rss.php">News</a></li>
            </ul>
            <span class="navbar-text">
                <?= htmlspecialchars($username) ?>
                <a href="../logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
            </span>
        </div>
    </div>
</nav>

    <div class="container mt-4">
        <h1>Forum</h1>

        <?php if (!$showCreateForm): ?>
            <form method="post" class="mb-3">
                <button type="submit" class="btn btn-primary" name="show_create_form">Create New Discussion</button>
            </form>

            <h2>Discussions</h2>
            <?php if (empty($topics)): ?>
                <p>No topics have been created yet.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($topics as $topic): ?>
                        <li class="list-group-item">
                            <h5 class="mb-1"><a href="topic.php?id=<?php echo $topic['post_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($topic['title']); ?></a></h5>
                            <p class="text-muted mb-0">Posted by <?php echo htmlspecialchars($topic['username']); ?> on <?php echo date('F j, Y, g:i a', strtotime($topic['created_at'])); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <div class="new-topic-form mb-4 p-3 bg-light border rounded">
                <h2>Start a New Topic</h2>
                <?php if (isset($error_message)): ?>
                    <p class="error"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="topic_title" class="form-label">Title:</label>
                        <input type="text" class="form-control" id="topic_title" name="topic_title" size="50" required>
                    </div>
                    <div class="mb-3">
                        <label for="topic_content" class="form-label">Content:</label>
                        <textarea class="form-control" id="topic_content" name="topic_content" rows="5" cols="80" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" name="create_new_topic" value="Create Topic">Create Topic</button>
                </form>
            </div>
            <form method="post">
                <button type="submit" class="btn btn-secondary">Back to Topics</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>




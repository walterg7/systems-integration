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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: forum.php");
    exit();
}
$topic_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_new_reply'])) {
    $reply_content = $_POST['reply_content'];

    if (!empty($reply_content)) {
        $post_reply_request = json_encode([
            'action' => 'post_reply',
            'username' => $username,
            'parent_id' => $topic_id,
            'content' => $reply_content
        ]);
        $post_reply_response = json_decode($client->sendRequest($post_reply_request), true);

        if ($post_reply_response['status'] === 'success') {
            header("Location: topic.php?id=" . $topic_id); 
            exit();
        } else {
            $reply_error_message = $post_reply_response['message'];
        }
    } else {
        $reply_error_message = "Please enter your reply.";
    }
}


$topic_request = json_encode(['action' => 'get_topic_and_replies', 'topic_id' => $topic_id]);
$topic_response = json_decode($client->sendRequest($topic_request), true);

if ($topic_response['status'] !== 'success') {
    echo "Error: " . $topic_response['message'];
    exit();
}

$topic = $topic_response['topic'];
$replies = $topic_response['replies'];

$pageTitle = htmlspecialchars($topic['title'] ?? 'Topic'); 
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
                <li class="nav-item"><a class="nav-link" href="notifications.php">Notifications</a></li>
                <li class="nav-item"><a class="nav-link" href="rss.php">News</a></li>
                <li class="nav-item"><a class="nav-link" href="forum.php">Forum</a></li>
            </ul>
            <span class="navbar-text">
                <?= htmlspecialchars($username) ?>
                <a href="../logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
            </span>
        </div>
    </div>
</nav>

    <div class="container mt-4">
        <p><a href="forum.php" class="btn btn-secondary btn-sm">Back to Forum</a></p>

        <div class="topic-header mb-4 p-3 bg-light border rounded">
            <h2 class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></h2>
            <p class="topic-meta text-muted mb-2">Posted by <?php echo htmlspecialchars($topic['username']); ?> on <?php echo date('F j, Y, g:i a', strtotime($topic['created_at'])); ?></p>
            <div class="post border p-3 rounded">
                <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
            </div>
        </div>

        <h2>Replies</h2>
        <?php if (empty($replies)): ?>
            <p>No replies yet. Be the first to respond!</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($replies as $reply): ?>
                    <li class="list-group-item">
                        <p class="post-meta text-muted mb-1">Posted by <?php echo htmlspecialchars($reply['username']); ?> on <?php echo date('F j, Y, g:i a', strtotime($reply['created_at'])); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="reply-form mt-4 p-3 bg-light border rounded">
            <h3>Post a Reply</h3>
            <?php if (isset($reply_error_message)): ?>
                <p class="error"><?php echo $reply_error_message; ?></p>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="reply_content" class="form-label">Your Reply:</label>
                    <textarea class="form-control" id="reply_content" name="reply_content" rows="5" cols="80" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" name="post_new_reply" value="Post Reply">Post Reply</button>
            </form>
        </div>
    </div>

</body>
</html>




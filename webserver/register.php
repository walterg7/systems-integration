<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php'; // Ensure php-amqplib is installed

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        echo "All fields are required.";
        exit();
    }

    if ($password !== $confirm_password) {
        echo "Passwords do not match.";
        exit();
    }

    // Send the data via RabbitMQ (if needed for async processing)
    try {
        $connection = new AMQPStreamConnection('localhost', 5672, 'broker', '!IT490', 'VM1');
        $channel = $connection->channel();

        // Declare the registration queue
        $queue_name = 'registration_request';
        $channel->queue_declare($queue_name, false, true, false, false);

        // Declare the registration exchange
        $exchange_name = 'registration';
        $channel->exchange_declare($exchange_name, 'topic', false, true, false);

        // Create message (JSON format)
        $request_data = json_encode([
            'email' => $email,
            'username' => $username,
            'password' => $password, // Hash password before sending in production
        ]);

        // Send message to the registration exchange
        $msg = new AMQPMessage($request_data);
        $channel->basic_publish($msg, $exchange_name, '*');

        // Send confirmation email
        sendConfirmationEmail($email, $username);

        // Redirect to a confirmation page
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

function sendConfirmationEmail($email, $username) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.yourmailserver.com'; // Use your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com'; // SMTP username
        $mail->Password = 'your-email-password'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@example.com', 'Your Website');
        $mail->addAddress($email); // Add recipient email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Please confirm your email address';
        $mail->Body    = 'Hello ' . htmlspecialchars($username) . ',<br><br>'
                        . 'Thank you for registering! Please confirm your email address by clicking the link below:<br>'
                        . '<a href="http://yourwebsite.com/confirm_email.php?email=' . urlencode($email) . '">Confirm Email</a>';

        $mail->send();
        echo 'Confirmation email has been sent.';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>


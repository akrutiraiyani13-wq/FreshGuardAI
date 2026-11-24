<?php
session_start();
require_once 'includes/db.inc.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error = 'Please fill in your name, email, and message.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);
        $success = 'Thank you for reaching out! Your message has been saved and our team will respond shortly.';
    }
}

include 'includes/header.php';
?>

<div class="container section text-center">
    <h2>Contact Us</h2>
    <p>Got questions, feedback, or want to collaborate on FreshGuardAI? Reach out below!</p>

    <div style="max-width: 520px; margin: 25px auto; text-align:left; background:#fff; padding:30px; border-radius:12px; box-shadow:var(--shadow-md);">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="contact.php">
            <div style="margin-bottom:14px;">
                <label>Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($success ? '' : ($_POST['name'] ?? $_SESSION['user_name'] ?? '')); ?>" required>
            </div>
            <div style="margin-bottom:14px;">
                <label>Email *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($success ? '' : ($_POST['email'] ?? '')); ?>" required>
            </div>
            <div style="margin-bottom:18px;">
                <label>Message *</label>
                <textarea name="message" rows="4" required><?php echo htmlspecialchars($success ? '' : ($_POST['message'] ?? '')); ?></textarea>
            </div>
            <button type="submit" class="btn" style="width:100%;">Send Message</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

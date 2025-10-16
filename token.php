<?php
// token.php
session_start();
require_once 'config/database.php';
require_once 'auth/auth.php';

$message = '';
$message_type = '';
$token = '';

// Memeriksa apakah token ada di URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = sanitize_input($_GET['token']);
} else {
    // Jika token tidak ada, arahkan kembali ke login
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Reset Password</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Token Reset Password</h1>
                <p>Salin token di bawah ini untuk mereset password Anda.</p>
            </div>

            <?php if (!empty($token)): ?>
            <div class="alert alert-info" style="text-align: center; word-break: break-all;">
                <p><strong>TOKEN ANDA:</strong></p>
                <h3><?php echo htmlspecialchars($token); ?></h3>
                <p style="margin-top: 10px;">Token ini hanya berlaku 1 jam.</p>
            </div>
            <?php endif; ?>

            <form action="reset-password.php" method="GET">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <button type="submit" class="btn" style="width: 100%;">
                    Lanjut ke Reset Password
                </button>
            </form>

            <div class="register-link" style="margin-top: 20px;">
                <p><a href="index.php">Kembali ke Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>

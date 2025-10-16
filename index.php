<?php
// index.php
session_start();
require_once 'config/database.php';
require_once 'auth/auth.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$auth = new AuthHandler();
$message = '';
$message_type = '';

if ($auth->is_logged_in()) {
    redirect('dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        $user_type = $_POST['user_type'];

        if (empty($username) || empty($password)) {
            $message = 'Username dan password harus diisi';
            $message_type = 'error';
        } else {
            if ($auth->login($username, $password, $user_type)) {
                redirect('dashboard.php');
            } else {
                $message = 'Username atau password salah';
                $message_type = 'error';
            }
        }
    } elseif (isset($_POST['register'])) {
        $username = sanitize_input($_POST['reg_username']);
        $email = sanitize_input($_POST['reg_email']);
        $password = $_POST['reg_password'];
        $confirm_password = $_POST['reg_confirm_password'];

        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $message = 'Semua field harus diisi';
            $message_type = 'error';
        } elseif ($password !== $confirm_password) {
            $message = 'Password dan konfirmasi password tidak sama';
            $message_type = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Password minimal 6 karakter';
            $message_type = 'error';
        } else {
            $result = $auth->register($username, $email, $password);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    } 
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Marketplace Hewan</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🐾 BioVet</h1>
                <p style="margin-right: -100px; font-style: italic; margin-top: -20px;">dra.0.0</p>
                <p>Masuk ke akun Anda</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div id="login-form">
                <form method="POST">
                    <div class="role-selector">
                        <button type="button" class="role-btn active" data-role="user">User</button>
                        <button type="button" class="role-btn" data-role="admin">Admin</button>
                    </div>

                    <input type="hidden" name="user_type" id="user_type" value="user">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" name="login" class="btn" style="width: 100%;">
                        Masuk
                    </button>
                </form>

                <a href="#" class="forgot-password-link" onclick="showForgotPassword()">Lupa Password?</a>

                <div class="register-link">
                    <p>Belum punya akun? <a href="#" onclick="showRegister()">Daftar di sini</a></p>
                </div>
            </div>

            <div id="register-form" style="display: none;">
                <div class="login-header">
                    <h2>Daftar Akun Baru</h2>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label for="reg_username">Username</label>
                        <input type="text" id="reg_username" name="reg_username" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_email">Email</label>
                        <input type="email" id="reg_email" name="reg_email" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <input type="password" id="reg_password" name="reg_password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="reg_confirm_password">Konfirmasi Password</label>
                        <input type="password" id="reg_confirm_password" name="reg_confirm_password" required>
                    </div>

                    <button type="submit" name="register" class="btn btn-success" style="width: 100%;">
                        Daftar
                    </button>
                </form>

                <div class="register-link">
                    <p>Sudah punya akun? <a href="#" onclick="showLogin()">Masuk di sini</a></p>
                </div>
            </div>

            <div id="forgot-form" style="display: none;">
                <div class="login-header">
                    <h2>Reset Password</h2>
                </div>
                <div class="alert" id="forgot-message" style="display: none;"></div>

                <div id="step-1">
                    <form id="forgot-email-form">
                        <div class="role-selector">
                            <button type="button" class="role-btn-forgot active" data-role="user">User</button>
                            <button type="button" class="role-btn-forgot" data-role="admin">Admin</button>
                        </div>

                        <input type="hidden" name="forgot_user_type" id="forgot_user_type" value="user">

                        <div class="form-group">
                            <label for="forgot_email">Email</label>
                            <input type="email" id="forgot_email" name="forgot_email" required>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;">
                            Kirim Kode Reset
                        </button>
                    </form>
                </div>

                <div id="step-2" style="display: none;">
                    <form id="forgot-code-form">
                        <div class="form-group">
                            <label for="forgot_code">Masukkan Kode Verifikasi</label>
                            <input type="text" id="forgot_code" name="forgot_code" required maxlength="5" placeholder="Masukkan 5 digit kode">
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">
                            Verifikasi Kode
                        </button>
                    </form>
                </div>

                <div class="register-link">
                    <p><a href="#" onclick="showLogin()">Kembali ke Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('user_type').value = this.dataset.role;
            });
        });

        document.querySelectorAll('.role-btn-forgot').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.role-btn-forgot').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('forgot_user_type').value = this.dataset.role;
            });
        });

        function showRegister() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'block';
            document.getElementById('forgot-form').style.display = 'none';
        }

        function showLogin() {
            document.getElementById('login-form').style.display = 'block';
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('forgot-form').style.display = 'none';
        }

        function showForgotPassword() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('forgot-form').style.display = 'block';
        }

        document.getElementById('reg_confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('reg_password').value;
            const confirm = this.value;

            if (password !== confirm) {
                this.setCustomValidity('Password tidak sama');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('forgot-email-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('forgot_email').value;
            const user_type = document.getElementById('forgot_user_type').value;
            const messageDiv = document.getElementById('forgot-message');

            fetch('api/forgot-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `email=${encodeURIComponent(email)}&user_type=${encodeURIComponent(user_type)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.textContent = data.message;
                        messageDiv.className = 'alert alert-success';
                        messageDiv.style.display = 'block';
                        document.getElementById('step-1').style.display = 'none';
                        document.getElementById('step-2').style.display = 'block';
                        window.forgotEmail = email;
                        if (data.debug) {
                            console.log('PHPMailer Debug Output:');
                            console.log(data.debug);
                            alert('Lihat konsol browser untuk debug error SMTP!');
                        } // Simpan email di global scope
                    } else {
                        messageDiv.textContent = data.message;
                        messageDiv.className = 'alert alert-error';
                        messageDiv.style.display = 'block';
                    }
                });
        });

        document.getElementById('forgot-code-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const code = document.getElementById('forgot_code').value;
            const email = window.forgotEmail; // Ambil email dari global scope
            const messageDiv = document.getElementById('forgot-message');

            fetch('api/verify-reset-code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `email=${encodeURIComponent(email)}&code=${encodeURIComponent(code)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect ke halaman reset password dengan verifikasi di session
                        window.location.href = 'reset-password.php';
                    } else {
                        messageDiv.textContent = data.message;
                        messageDiv.className = 'alert alert-error';
                        messageDiv.style.display = 'block';
                    }
                });
        });
    </script>
</body>

</html>
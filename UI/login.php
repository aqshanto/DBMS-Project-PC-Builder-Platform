<?php
session_start();
require_once 'config.php';

// Already logged in? Go home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error    = "";
$username = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = get_db_connection();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Allow login with username OR email
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ? OR user_mail = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'] ?? 'user'; // ← store role

                $redirect = isset($_SESSION['redirect_after_login'])
                    ? $_SESSION['redirect_after_login'] : 'index.php';
                unset($_SESSION['redirect_after_login']);

                // Redirect admins to dashboard
                if ($_SESSION['role'] === 'admin' && $redirect === 'index.php') {
                    $redirect = 'admin/admin_dashboard.php';
                }

                header("Location: $redirect");
                exit();
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that username or email.";
        }
        $stmt->close();
    }
    $conn->close();
}

$redirect_msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — NextGen PC Builder</title>
    <style>
        :root {
            --bg: #031c34; --card: #083c6c; --accent: #0b609c;
            --text: #b6cddc; --white: #ffffff; --danger: #ef4444;
            --success: #10b981; --muted: #72a0b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg); color: var(--white);
            min-height: 100vh; display: flex; flex-direction: column;
            align-items: center; justify-content: center; padding: 2rem 1rem;
        }
        .logo { font-size: 2rem; font-weight: bold; margin-bottom: .3rem; }
        .logo span { color: var(--muted); }
        .tagline { color: var(--muted); font-size: .95rem; margin-bottom: 2rem; }

        .card {
            background: var(--card); border-radius: 16px; padding: 2.2rem 2.5rem;
            width: 100%; max-width: 420px; border: 1px solid #1a4a7a;
            box-shadow: 0 20px 40px rgba(0,0,0,.4);
        }
        .card h2 { font-size: 1.6rem; margin-bottom: 1.5rem; text-align: center; }

        .form-group { margin-bottom: 1.2rem; }
        label { display: block; font-size: .85rem; color: var(--muted); margin-bottom: .4rem; text-transform: uppercase; letter-spacing: .5px; }
        input {
            width: 100%; padding: 11px 14px; background: #042a50;
            border: 1px solid #1a4a7a; border-radius: 8px; color: var(--white);
            font-size: 1rem; outline: none; transition: border .2s;
        }
        input:focus { border-color: var(--accent); }

        .password-wrap { position: relative; }
        .toggle-pw {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1.1rem;
        }
        .toggle-pw:hover { color: var(--white); }

        .btn {
            width: 100%; padding: 13px; background: var(--accent); color: white;
            border: none; border-radius: 9px; font-size: 1.05rem; font-weight: bold;
            cursor: pointer; transition: background .2s; margin-top: .5rem;
        }
        .btn:hover { background: #0d73ba; }

        .alert {
            border-radius: 8px; padding: .9rem 1rem; margin-bottom: 1.2rem; font-size: .9rem;
        }
        .alert.error   { background: rgba(239,68,68,.12); border: 1px solid var(--danger);  color: #fca5a5; }
        .alert.info    { background: rgba(11,96,156,.2);  border: 1px solid var(--accent);  color: var(--text); }
        .alert.success { background: rgba(16,185,129,.12);border: 1px solid var(--success); color: #6ee7b7; }

        .divider { text-align: center; color: var(--muted); margin: 1.2rem 0; font-size: .9rem; }
        .link-btn {
            display: block; text-align: center; color: var(--accent);
            text-decoration: none; font-weight: bold; font-size: .95rem;
        }
        .link-btn:hover { text-decoration: underline; }
        .back-home { margin-top: 1.5rem; text-align: center; }
        .back-home a { color: var(--muted); font-size: .85rem; text-decoration: none; }
        .back-home a:hover { color: var(--white); }
    </style>
</head>
<body>

    <div class="logo">⚡ NextGen <span>PC Builder</span></div>
    <p class="tagline">Sign in to save and manage your builds</p>

    <div class="card">
        <h2>🔐 Sign In</h2>

        <?php if ($error): ?>
            <div class="alert error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($redirect_msg === 'saved'): ?>
            <div class="alert success">✅ Build saved! Please log in to view it.</div>
        <?php elseif ($redirect_msg === 'required'): ?>
            <div class="alert info">🔒 Please log in to save your build.</div>
        <?php elseif ($redirect_msg === 'logout'): ?>
            <div class="alert info">👋 You have been logged out successfully.</div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($username) ?>"
                       placeholder="Enter username or email" autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePw()">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn">🚀 Sign In</button>
        </form>

        <div class="divider">Don't have an account?</div>
        <a href="register.php" class="link-btn">Create Account →</a>
    </div>

    <div class="back-home">
        <a href="index.php">← Back to PC Builder</a>
    </div>

<script>
function togglePw() {
    const inp = document.getElementById('password');
    const btn = document.querySelector('.toggle-pw');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
}
</script>
</body>
</html>
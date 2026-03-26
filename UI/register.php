<?php
session_start();
require_once 'config.php';

// Already logged in? Go home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors   = [];
$success  = "";
$username = "";
$email    = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = get_db_connection();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    // --- VALIDATION ---
    if (empty($username))
        $errors[] = "Username is required.";
    elseif (strlen($username) < 3 || strlen($username) > 30)
        $errors[] = "Username must be 3–30 characters.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
        $errors[] = "Username can only contain letters, numbers, and underscores.";

    if (empty($email))
        $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Please enter a valid email address.";

    if (empty($password))
        $errors[] = "Password is required.";
    elseif (strlen($password) < 6)
        $errors[] = "Password must be at least 6 characters.";

    if ($password !== $confirm)
        $errors[] = "Passwords do not match.";

    // --- CHECK DUPLICATES ---
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR user_mail = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username or email is already taken. Please choose another.";
        }
        $stmt->close();
    }

    // --- REGISTER ---
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, user_mail, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hash);
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            // Auto login after register
            $_SESSION['user_id']  = $user_id;
            $_SESSION['username'] = $username;
            header("Location: index.php?welcome=1");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — NextGen PC Builder</title>
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
            width: 100%; max-width: 440px; border: 1px solid #1a4a7a;
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
        input.error-field { border-color: var(--danger); }

        .password-wrap { position: relative; }
        .toggle-pw {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--muted); cursor: pointer;
            font-size: 1.1rem; padding: 4px;
        }
        .toggle-pw:hover { color: var(--white); }

        .strength-bar { height: 4px; border-radius: 4px; margin-top: 6px; background: #1a3a5c; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 4px; width: 0; transition: width .3s, background .3s; }
        .strength-label { font-size: .75rem; color: var(--muted); margin-top: 4px; }

        .btn {
            width: 100%; padding: 13px; background: var(--accent); color: white;
            border: none; border-radius: 9px; font-size: 1.05rem; font-weight: bold;
            cursor: pointer; transition: background .2s; margin-top: .5rem;
        }
        .btn:hover { background: #0d73ba; }

        .errors {
            background: rgba(239,68,68,.12); border: 1px solid var(--danger);
            border-radius: 8px; padding: 1rem; margin-bottom: 1.2rem;
        }
        .errors p { color: #fca5a5; font-size: .9rem; margin: .2rem 0; }
        .errors p::before { content: "• "; }

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
    <p class="tagline">Create your account to save builds</p>

    <div class="card">
        <h2>📝 Create Account</h2>

        <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="regForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($username) ?>"
                       placeholder="e.g. techbuilder99" autocomplete="username"
                       class="<?= in_array('Username is required.', $errors) ? 'error-field' : '' ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($email) ?>"
                       placeholder="you@example.com" autocomplete="email"
                       class="<?= in_array('Email is required.', $errors) ? 'error-field' : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="Minimum 6 characters" autocomplete="new-password"
                           oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="togglePw('password', this)">👁️</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="strength-label" id="strengthLabel"></div>
            </div>

            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <div class="password-wrap">
                    <input type="password" id="confirm" name="confirm"
                           placeholder="Re-enter your password" autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm', this)">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn">🚀 Create Account</button>
        </form>

        <div class="divider">Already have an account?</div>
        <a href="login.php" class="link-btn">Sign In →</a>
    </div>

    <div class="back-home">
        <a href="index.php">← Back to PC Builder</a>
    </div>

<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;

    const levels = [
        { w: '0%',   bg: 'transparent', txt: '' },
        { w: '25%',  bg: '#ef4444',     txt: '🔴 Too weak' },
        { w: '50%',  bg: '#f59e0b',     txt: '🟡 Fair' },
        { w: '75%',  bg: '#3b82f6',     txt: '🔵 Good' },
        { w: '100%', bg: '#10b981',     txt: '🟢 Strong' },
    ];
    const lvl = val.length === 0 ? levels[0] : levels[Math.min(score, 4)];
    fill.style.width      = lvl.w;
    fill.style.background = lvl.bg;
    label.textContent     = lvl.txt;
}
</script>
</body>
</html>
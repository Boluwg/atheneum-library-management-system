<?php
require_once __DIR__ . '/config/security.php';
start_secure_session();

// If already logged in, redirect
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin/index.php");
    exit;
}

include("config/db.php");

$error = "";
$max_login_attempts = 20;
$lockout_seconds = 3600;
$attempts_key = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$lock_key = 'login_locked_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

// Simple brute-force throttle via session
if (!isset($_SESSION['login_attempts_v2'])) $_SESSION['login_attempts_v2'] = 0;
if (!isset($_SESSION['login_locked_until_v2'])) $_SESSION['login_locked_until_v2'] = 0;

$locked = time() < $_SESSION['login_locked_until_v2'];

if (isset($_POST['login']) && !$locked) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate input lengths
    if (strlen($username) > 64 || strlen($password) > 128) {
        $error = "Invalid credentials.";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            // Support both password_hash() and legacy sha256
            $valid = false;
            if (password_verify($password, $admin['password'])) {
                $valid = true;
            } elseif ($admin['password'] === hash('sha256', $password)) {
                $valid = true;
                // Upgrade to bcrypt on next login
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $upd = $conn->prepare("UPDATE admins SET password=? WHERE id=?");
                $upd->bind_param("si", $newHash, $admin['id']);
                $upd->execute();
            }

            if ($valid) {
                $_SESSION['login_attempts_v2'] = 0;
                session_regenerate_id(true); // prevent session fixation
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_id'] = $admin['id'];
                header("Location: admin/index.php");
                exit;
            }
        }

        $_SESSION['login_attempts_v2']++;
        if ($_SESSION['login_attempts_v2'] >= $max_login_attempts) {
            $_SESSION['login_locked_until_v2'] = time() + $lockout_seconds;
            $error = "Too many failed attempts. Please wait 1 hour.";
        } else {
            $remaining = $max_login_attempts - $_SESSION['login_attempts_v2'];
            $error = "Invalid username or password. $remaining attempt(s) remaining.";
        }
        $stmt->close();
    }
}

if ($locked) {
    $wait = ceil(($_SESSION['login_locked_until_v2'] - time()) / 60);
    $error = "Account temporarily locked. Try again in $wait minute(s).";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Atheneum</title>
  <link rel="stylesheet" href="admin/style.css">
  <script>
    if (localStorage.getItem('atheneum-theme') === 'light') document.documentElement.dataset.theme = 'light';
  </script>
  <style>
    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 12px;
      color: var(--muted);
    }
    .form-group { margin-bottom: 18px; }
    .attempts-bar {
      display: flex; gap: 4px; margin-top: 8px;
    }
    .attempt-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: var(--border);
    }
    .attempt-dot.used { background: var(--danger); }
  </style>
</head>
<body>

<div id="globalScannerStatus" class="global-scanner-status" hidden aria-live="polite">
  Select scanner port
</div>
<button type="button" class="theme-toggle login-theme-toggle" data-theme-toggle>☀ Light</button>
<div class="login-bg">
  <div class="login-box">

    <div class="login-logo">
      <span class="login-logo-icon">📚</span>
      <h1>Atheneum</h1>
      <p>Library Management System</p>
    </div>

    <form method="post" autocomplete="off">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               placeholder="Enter your username"
               maxlength="64"
               autocomplete="username"
               <?= $locked ? 'disabled' : '' ?>
               required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Enter your password"
               maxlength="128"
               autocomplete="current-password"
               <?= $locked ? 'disabled' : '' ?>
               required>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <span>⚠</span> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($_SESSION['login_attempts_v2'] > 0 && !$locked): ?>
        <div class="attempts-bar">
          <?php for ($i = 0; $i < $max_login_attempts; $i++): ?>
            <div class="attempt-dot <?= $i < $_SESSION['login_attempts_v2'] ? 'used' : '' ?>"></div>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

      <button type="submit" name="login"
              class="btn btn-primary btn-full"
              style="margin-top:22px;"
              <?= $locked ? 'disabled' : '' ?>>
        Sign In
      </button>
    </form>

    <div class="login-footer">Atheneum v2 &nbsp;·&nbsp; Librarian Access Only</div>
  </div>
</div>
<script src="assets/theme.js"></script>
<script src="assets/scanner_status.js?v=3"></script>
</body>
</html>

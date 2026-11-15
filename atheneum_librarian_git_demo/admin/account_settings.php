<?php
$page_title = "Account Settings";
include("auth.php");
include("../config/db.php");

$csrfToken = csrf_token();
$message = "";
$error = "";
$adminId = (int)($_SESSION['admin_id'] ?? 0);
$admin = null;

if ($adminId > 0) {
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
}

if (!$admin) {
    http_response_code(500);
    exit("Admin account unavailable.");
}

if (isset($_POST['save_account'])) {
    verify_csrf_token();
    $currentPassword = (string)($_POST['current_password'] ?? "");
    $newUsername = trim((string)($_POST['username'] ?? ""));
    $newPassword = (string)($_POST['new_password'] ?? "");

    $validCurrentPassword = password_verify($currentPassword, $admin['password']) ||
        hash_equals($admin['password'], hash('sha256', $currentPassword));
    if (!$validCurrentPassword) {
        $error = "The current password is incorrect.";
    } elseif ($newUsername === '' || strlen($newUsername) < 3 || strlen($newUsername) > 64) {
        $error = "Username must be between 3 and 64 characters.";
    } elseif (preg_match('/[\x00-\x1F\x7F]/', $newUsername)) {
        $error = "Username contains invalid characters.";
    } elseif ($newPassword !== '' && (strlen($newPassword) < 8 || strlen($newPassword) > 128)) {
        $error = "New password must be between 8 and 128 characters.";
    } else {
        $duplicate = $conn->prepare("SELECT id FROM admins WHERE username=? AND id<>? LIMIT 1");
        $duplicate->bind_param("si", $newUsername, $adminId);
        $duplicate->execute();

        if ($duplicate->get_result()->num_rows > 0) {
            $error = "That username is already in use.";
        } else {
            if ($newPassword !== '') {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE admins SET username=?, password=? WHERE id=?");
                $update->bind_param("ssi", $newUsername, $passwordHash, $adminId);
            } else {
                $update = $conn->prepare("UPDATE admins SET username=? WHERE id=?");
                $update->bind_param("si", $newUsername, $adminId);
            }

            if ($update->execute()) {
                $_SESSION['admin_username'] = $newUsername;
                $admin['username'] = $newUsername;
                if ($newPassword !== '') {
                    $admin['password'] = $passwordHash;
                }
                $message = "Account settings updated.";
            } else {
                $error = "Account settings could not be updated.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings — Atheneum</title>
  <link rel="stylesheet" href="style.css?v=4">
</head>
<body>
<nav class="topbar">
  <a class="topbar-brand" href="index.php">📚 Atheneum</a>
  <div class="topbar-right">
    <?php include("theme_toggle.php"); ?>
    <span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span>
    <a class="topbar-logout" href="../logout.php">Sign out</a>
  </div>
</nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap">
  <a class="back-link" href="users.php">Back to Users</a>
  <h2>Account Settings</h2>
  <div class="card" style="max-width:560px">
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group">
        <label for="current_password">Current password</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
      </div>
      <div class="form-group">
        <label for="username">New username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($admin['username']) ?>"
               minlength="3" maxlength="64" required autocomplete="username">
      </div>
      <div class="form-group">
        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password"
               minlength="8" maxlength="128" autocomplete="new-password">
        <small style="color:var(--muted)">Leave blank to keep the current password.</small>
      </div>
      <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <button type="submit" name="save_account" class="btn btn-primary">Save account settings</button>
    </form>
  </div>
</div>
</body>
</html>

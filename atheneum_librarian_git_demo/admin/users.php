<?php
$page_title = "Users";
include("auth.php");
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Users — Atheneum</title>
  <link rel="stylesheet" href="style.css?v=3">
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
  <a class="back-link" href="index.php">Back to Dashboard</a>
  <h2>Users</h2>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div>
      <h3>Students</h3>
      <div class="tile-grid" style="grid-template-columns:1fr 1fr 1fr">
        <a class="tile" href="students.php"><span class="tile-icon">👁</span>View</a>
        <a class="tile" href="add_student.php"><span class="tile-icon">➕</span>Add</a>
        <a class="tile" href="remove_student.php"><span class="tile-icon">🗑</span>Remove</a>
        <a class="tile" href="set_user_password.php"><span class="tile-icon">🔑</span>Set Password</a>
        <a class="tile" href="password_requests.php"><span class="tile-icon">📨</span>Password Requests</a>
        <a class="tile" href="account_settings.php"><span class="tile-icon">⚙</span>Account Settings</a>
      </div>
    </div>
    <div>
      <h3>Teachers</h3>
      <div class="tile-grid" style="grid-template-columns:1fr 1fr 1fr">
        <a class="tile" href="teachers.php"><span class="tile-icon">👁</span>View</a>
        <a class="tile" href="add_teacher.php"><span class="tile-icon">➕</span>Add</a>
        <a class="tile" href="remove_teacher.php"><span class="tile-icon">🗑</span>Remove</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>

<?php
$page_title = "Circulation";
include("auth.php");
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Circulation — Atheneum</title>
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
  <h2>Circulation</h2>
  <div class="tile-grid" style="max-width:500px">
    <a class="tile" href="issue.php"><span class="tile-icon">📤</span>Issue Book</a>
    <a class="tile" href="return.php"><span class="tile-icon">📥</span>Return Book</a>
  </div>
</div>
</body>
</html>

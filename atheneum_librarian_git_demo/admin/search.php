<?php
include("auth.php");
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Search — Atheneum</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="topbar"><a class="topbar-brand" href="index.php">📚 Atheneum</a><div class="topbar-right"><?php include("theme_toggle.php"); ?><span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span><a class="topbar-logout" href="../logout.php">Sign out</a></div></nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap">
  <a class="back-link" href="index.php">Back to Dashboard</a>
  <h2>Search</h2>
  <div class="card"><p style="color:var(--muted)">Use Inventory or the user catalogue to search books.</p><a class="btn btn-primary" href="inventory.php">Open Inventory</a></div>
</div>
</body>
</html>
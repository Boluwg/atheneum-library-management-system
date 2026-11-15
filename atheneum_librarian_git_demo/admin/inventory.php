<?php
$page_title = "Inventory";
include("auth.php");
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory — Atheneum</title>
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
  <h2>Inventory</h2>
  <div class="tile-grid">
    <a class="tile" href="add_book.php"><span class="tile-icon">➕</span>Add Book</a>
    <a class="tile" href="remove_book.php"><span class="tile-icon">🗑</span>Remove Book</a>
    <a class="tile" href="view_books.php"><span class="tile-icon">📚</span>View Books</a>
    <a class="tile" href="import_books.php"><span class="tile-icon">⬇</span>Import Books</a>
    <a class="tile" href="export_books.php"><span class="tile-icon">⬆</span>Export Books</a>
  </div>
</div>
<script src="../assets/csv_export.js?v=1"></script>
</body>
</html>

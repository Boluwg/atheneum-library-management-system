<?php
$page_title = "Add Book";
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

$message = "";
$error = "";
$categories = [
    'fiction' => 'Fiction',
    'non_fiction' => 'Non-fiction',
    'academic' => 'Academic',
    'other' => 'Other'
];

if (isset($_POST['add'])) {
    verify_csrf_token();
    $title     = trim($_POST['title']);
    $author    = trim($_POST['author']);
    $category  = $_POST['category'] ?? 'other';
    $accession = trim($_POST['accession_no']);
    $rfid      = trim($_POST['rfid']);
    if (!array_key_exists($category, $categories)) {
        $category = 'other';
    }

    $stmt = $conn->prepare("SELECT id FROM books WHERE rfid_tag=? OR accession_no=?");
    $stmt->bind_param("ss", $rfid, $accession);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error = "This RFID tag is already assigned to another book.";
    } else {
        $ins = $conn->prepare("INSERT INTO books (title, author, category, accession_no, rfid_tag, status) VALUES (?,?,?,?,?,'available')");
        $ins->bind_param("sssss", $title, $author, $category, $accession, $rfid);
        $ins->execute();
        $message = "Book added successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Book — Atheneum</title>
  <link rel="stylesheet" href="style.css">
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
  <a class="back-link" href="inventory.php">Back to Inventory</a>
  <h2>Add Book</h2>

  <div class="card add-book-card">
    <div class="add-book-intro">
      <span class="add-book-icon">＋</span>
      <div><h3>Add a book</h3><p>Enter the catalogue details and attach its RFID tag.</p></div>
    </div>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label>Book title</label><input type="text" name="title" placeholder="e.g. The Great Gatsby" required></div>
      <div class="form-row">
        <div class="form-group"><label>Author</label><input type="text" name="author" placeholder="e.g. F. Scott Fitzgerald" required></div>
        <div class="form-group"><label>Accession number</label><input type="text" name="accession_no" placeholder="e.g. LIB-00123" required></div>
      </div>
      <div class="form-group">
        <label for="category">Book type</label>
        <select name="category" id="category" required>
          <?php foreach ($categories as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= (($_POST['category'] ?? 'other') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <hr class="divider">

      <div class="form-group">
        <label>RFID Tag</label>
        <input type="text" name="rfid" id="rfidInput" placeholder="Tap book on scanner" autofocus required>
      </div>
      <div class="scanner-bar">
        <span class="scanner-status" id="scannerStatus">Scanner not connected</span>
      </div>

      <?php if ($message): ?><div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <button type="submit" name="add" class="btn btn-primary btn-full" style="margin-top:8px">Add Book</button>
    </form>
  </div>
</div>
<style>
.add-book-card{max-width:720px;margin:0 auto!important;padding:30px 34px!important}
.add-book-intro{display:flex;align-items:center;gap:14px;border-bottom:1px solid var(--border);padding-bottom:22px;margin-bottom:24px}
.add-book-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:rgba(201,168,76,.14);color:var(--accent);font-size:26px}
.add-book-intro h3{margin:0 0 3px;text-transform:none;color:var(--text);font-family:'Playfair Display',serif;font-size:20px;letter-spacing:0}
.add-book-intro p{color:var(--muted);font-size:13px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:650px){.add-book-card{padding:24px 18px!important}.form-row{grid-template-columns:1fr}}
</style>
<script src="../assets/scanner.js?v=9"></script>
</body>
</html>
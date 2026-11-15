<?php
$page_title = "Return Book";
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

$book = null; $issue = null; $user = null; $fine = 0; $message = "";

if (isset($_POST['confirm_return'])) {
    verify_csrf_token();
    $book_id = (int)($_POST['book_id'] ?? 0);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id FROM issued_books WHERE book_id = ? AND return_date IS NULL ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $issueResult = $stmt->get_result();
        if ($issueResult->num_rows !== 1) {
            throw new RuntimeException("Book is not currently issued.");
        }
        $issueId = (int)$issueResult->fetch_assoc()['id'];
        $today = date("Y-m-d");
        $updateIssue = $conn->prepare("UPDATE issued_books SET return_date = ? WHERE id = ?");
        $updateIssue->bind_param("si", $today, $issueId);
        if (!$updateIssue->execute()) {
            throw new RuntimeException("Could not record the return.");
        }
        $updateBook = $conn->prepare("UPDATE books SET status = 'available' WHERE id = ?");
        $updateBook->bind_param("i", $book_id);
        if (!$updateBook->execute()) {
            throw new RuntimeException("Could not update book status.");
        }
        $conn->commit();
        header("Location: circulation.php");
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $message = $e->getMessage();
    }
}

if (!empty($_POST['rfid']) || !empty($_POST['accession_no'])) {
    if (!empty($_POST['rfid'])) {
        $key = trim($_POST['rfid']);
        $b = $conn->prepare("SELECT * FROM books WHERE rfid_tag=?");
    } else {
        $key = trim($_POST['accession_no']);
        $b = $conn->prepare("SELECT * FROM books WHERE accession_no=?");
    }
    $b->bind_param("s", $key); $b->execute();
    $res = $b->get_result();
    if ($res->num_rows === 1) {
        $book = $res->fetch_assoc();
        $i = $conn->prepare("SELECT i.* FROM issued_books i JOIN books b ON i.book_id = b.id WHERE i.book_id=? AND b.status='issued' ORDER BY i.id DESC LIMIT 1");
        $i->bind_param("i", $book['id']); $i->execute();
        $ires = $i->get_result();
        if ($ires->num_rows === 1) {
            $issue = $ires->fetch_assoc();
            $u = $conn->prepare(
                "SELECT student_id AS user_id, name FROM students WHERE student_id=?
                 UNION ALL
                 SELECT teacher_id AS user_id, name FROM teachers WHERE teacher_id=?"
            );
            $u->bind_param("ss", $issue['student_id'], $issue['student_id']); $u->execute();
            $ures = $u->get_result();
            if ($ures->num_rows === 1) $user = $ures->fetch_assoc();
            $today = date("Y-m-d");
            if ($today > $issue['due_date']) {
                $days = (strtotime($today) - strtotime($issue['due_date'])) / 86400;
                $fine = (int)$days * 5;
            }
        } else { $message = "Book is not currently issued."; }
    } else { $message = "Book not found."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Return Book — Atheneum</title>
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
  <a class="back-link" href="circulation.php">Back to Circulation</a>
  <h2>Return Book</h2>

  <div class="card" style="max-width:560px">
    <div class="scanner-bar">
      <span class="scanner-status" id="scannerStatus">Scanner not connected</span>
    </div>
    <form method="post">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group">
          <label>RFID Tag</label>
          <input type="text" name="rfid" id="rfidInput" placeholder="Tap book on scanner" autofocus>
        </div>
        <div class="form-group">
          <label>Accession No (manual)</label>
          <input type="text" name="accession_no" placeholder="e.g. LIB-00123">
        </div>
      </div>
      <button type="submit" class="btn btn-secondary">Find Book →</button>
    </form>

    <?php if ($message): ?>
    <div class="alert alert-error" style="margin-top:14px">⚠ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
  </div>

  <?php if ($book && $issue && $user): ?>
  <div class="card" style="max-width:560px">
    <div class="book-info-box" style="margin-bottom:16px">
      <p><span class="label">Book</span><br><strong><?= htmlspecialchars($book['title']) ?></strong></p>
      <p style="margin-top:8px"><span class="label">Borrower</span><br><?= htmlspecialchars($user['name']) ?> <span style="color:var(--muted)">(<?= htmlspecialchars($issue['student_id']) ?>)</span></p>
      <p style="margin-top:8px"><span class="label">Due Date</span><br><?= $issue['due_date'] ?></p>
    </div>

    <?php if ($fine > 0): ?>
    <div class="alert alert-error" style="margin-bottom:16px">
      ⚠ Overdue fine: <strong>₹<?= $fine ?></strong>
    </div>
    <?php else: ?>
    <div class="alert alert-success" style="margin-bottom:16px">✓ No overdue fine</div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
      <button type="submit" name="confirm_return" class="btn btn-primary">Confirm Return</button>
    </form>
  </div>
  <?php endif; ?>
</div>
<script src="../assets/scanner.js?v=9"></script>
</body>
</html>
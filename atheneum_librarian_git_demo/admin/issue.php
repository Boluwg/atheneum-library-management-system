<?php
$page_title = "Issue Book";
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

$user = null; $user_type = null; $borrowed = 0;
$book = null; $message = "";

if (isset($_POST['user_id'])) {
    $raw_id = trim($_POST['user_id']);
    $stmt = $conn->prepare(
        "SELECT student_id AS user_id, name, class AS group_name, section AS group_section
         FROM students WHERE student_id=?
         UNION ALL
         SELECT teacher_id AS user_id, name, department AS group_name, designation AS group_section
         FROM teachers WHERE teacher_id=?"
    );
    $stmt->bind_param("ss", $raw_id, $raw_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc(); $user_type = "student";
        $c = $conn->prepare("SELECT COUNT(*) AS t FROM issued_books i JOIN books b ON i.book_id = b.id WHERE i.student_id=? AND b.status='issued'");
        $c->bind_param("s", $raw_id); $c->execute();
        $borrowed = $c->get_result()->fetch_assoc()['t'];
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
    if ($res->num_rows === 1) $book = $res->fetch_assoc();
    else $message = "Book not found.";
}

if (isset($_POST['confirm_issue'])) {
    verify_csrf_token();
    $user_id = trim($_POST['user_id'] ?? '');
    $book_id = (int)$_POST['book_id'];
    $issue_date = date("Y-m-d");
    $due_date   = date("Y-m-d", strtotime("+7 days"));
    $conn->begin_transaction();
    try {
        $check = $conn->prepare("SELECT id FROM books WHERE id = ? AND status = 'available' FOR UPDATE");
        $check->bind_param("i", $book_id);
        $check->execute();
        if ($check->get_result()->num_rows !== 1) {
            throw new RuntimeException("Book is no longer available.");
        }
        $ins = $conn->prepare("INSERT INTO issued_books (student_id,book_id,issue_date,due_date) VALUES (?,?,?,?)");
        $ins->bind_param("siss", $user_id, $book_id, $issue_date, $due_date);
        if (!$ins->execute()) {
            throw new RuntimeException("Could not issue the book.");
        }
        $upd = $conn->prepare("UPDATE books SET status='issued' WHERE id=?");
        $upd->bind_param("i", $book_id);
        if (!$upd->execute()) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Issue Book — Atheneum</title>
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
  <h2>Issue Book</h2>

  <!-- STEP 1 -->
  <div class="card" style="max-width:560px">
    <h3>Step 1 — Identify Student</h3>
    <form method="post">
      <div class="form-group">
        <label>Student ID</label>
        <input type="text" name="user_id" placeholder="Enter student ID" required
               value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-secondary">Look up →</button>
    </form>

    <?php if ($user): ?>
    <div class="book-info-box" style="margin-top:16px">
      <p><span class="label">Name</span><br><strong><?= htmlspecialchars($user['name']) ?></strong></p>
      <p style="margin-top:6px"><span class="label">Class / Department</span><br><?= htmlspecialchars($user['group_name']) ?> – <?= htmlspecialchars($user['group_section']) ?></p>
      <p style="margin-top:6px"><span class="label">Currently Borrowed</span><br><?= $borrowed ?> book<?= $borrowed != 1 ? 's' : '' ?></p>
    </div>
    <?php elseif (isset($_POST['user_id'])): ?>
    <div class="alert alert-error" style="margin-top:12px">⚠ User not found.</div>
    <?php endif; ?>
  </div>

  <!-- STEP 2 -->
  <?php if ($user): ?>
  <div class="card" style="max-width:560px">
    <h3>Step 2 — Identify Book</h3>
    <div class="scanner-bar">
      <span class="scanner-status" id="scannerStatus">Scanner not connected</span>
    </div>

    <form method="post">
      <input type="hidden" name="user_id" value="<?= htmlspecialchars($_POST['user_id']) ?>">
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

    <?php if ($book): ?>
    <div class="book-info-box" style="margin-top:16px">
      <p><span class="label">Title</span><br><strong><?= htmlspecialchars($book['title']) ?></strong></p>
      <p style="margin-top:6px"><span class="label">Status</span><br>
        <span class="badge badge-<?= $book['status'] ?>"><?= ucfirst($book['status']) ?></span>
      </p>
      <p style="margin-top:6px"><span class="label">Accession</span><br><?= htmlspecialchars($book['accession_no']) ?></p>
    </div>

    <?php if ($book['status'] === 'available'): ?>
    <form method="post" style="margin-top:16px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="user_id" value="<?= htmlspecialchars($_POST['user_id']) ?>">
      <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
      <div class="alert alert-success" style="margin-bottom:12px">
        ✓ Book available — due in 7 days (<?= date("d M Y", strtotime("+7 days")) ?>)
      </div>
      <button type="submit" name="confirm_issue" class="btn btn-primary">Confirm Issue</button>
    </form>
    <?php else: ?>
    <div class="alert alert-error" style="margin-top:12px">⚠ Book is not available for issue.</div>
    <?php endif; ?>

    <?php elseif ($message): ?>
    <div class="alert alert-error" style="margin-top:12px">⚠ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<script src="../assets/scanner.js?v=9"></script>
</body>
</html>
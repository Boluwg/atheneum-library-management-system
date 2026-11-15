<?php
$page_title = "Remove Student";
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

$msg = ""; $error = ""; $student = null;

// Preview step
if (isset($_POST['preview'])) {
    verify_csrf_token();
    $id = trim($_POST['student_id']);
    $chk = $conn->prepare("SELECT * FROM students WHERE student_id=?");
    $chk->bind_param("s", $id);
    $chk->execute();
    $res = $chk->get_result();
    if ($res->num_rows === 0) $error = "Student not found.";
    else $student = $res->fetch_assoc();
}

// Confirm delete
if (isset($_POST['confirm_remove'])) {
    verify_csrf_token();
    $id = trim($_POST['student_id']);
    $del = $conn->prepare("DELETE FROM students WHERE student_id=?");
    $del->bind_param("s", $id);
    $del->execute();
    $msg = "Student removed successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Remove Student — Atheneum</title>
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
  <a class="back-link" href="users.php">Back to Users</a>
  <h2>Remove Student</h2>
  <div class="card" style="max-width:480px">

    <?php if (!$student && !$msg): ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label>Student ID</label><input type="text" name="student_id" placeholder="Enter Student ID to look up" required></div>
      <?php if ($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <button type="submit" name="preview" class="btn btn-secondary btn-full" style="margin-top:8px">Find Student</button>
    </form>
    <?php endif; ?>

    <?php if ($student): ?>
    <div class="book-info-box" style="margin-bottom:18px">
      <p><span class="label">Name</span><br><strong><?= htmlspecialchars($student['name']) ?></strong></p>
      <p style="margin-top:8px"><span class="label">Class / Section</span><br><?= htmlspecialchars($student['class']) ?> – <?= htmlspecialchars($student['section']) ?></p>
    </div>
    <div class="alert alert-error" style="margin-bottom:16px">⚠ This action is permanent and cannot be undone.</div>
    <form method="post" style="display:flex;gap:10px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
      <button type="submit" name="confirm_remove" class="btn btn-danger">Yes, Remove</button>
      <a href="remove_student.php" class="btn btn-secondary">Cancel</a>
    </form>
    <?php endif; ?>

    <?php if ($msg): ?><div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  </div>
</div>
</body>
</html>

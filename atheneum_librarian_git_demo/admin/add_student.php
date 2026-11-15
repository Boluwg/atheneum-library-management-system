<?php
$page_title = "Add Student";
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

$msg = ""; $error = "";

if (isset($_POST['add'])) {
    verify_csrf_token();
    $id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);

    if (!$id || !$name || !$class || !$section) {
        $error = "All fields are required.";
    } else {
        $chk = $conn->prepare("SELECT student_id FROM students WHERE student_id=?");
        $chk->bind_param("s", $id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Student ID already exists.";
        } else {
            $ins = $conn->prepare("INSERT INTO students (student_id,name,class,section) VALUES (?,?,?,?)");
            $ins->bind_param("ssss", $id, $name, $class, $section);
            $ins->execute();
            $msg = "Student added successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Add Student — Atheneum</title>
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
  <h2>Add Student</h2>
  <div class="card" style="max-width:480px">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group"><label>Student ID</label><input type="text" name="student_id" placeholder="e.g. STU-2024-001" required></div>
      <div class="form-group"><label>Full Name</label><input type="text" name="name" placeholder="e.g. Aisha Patel" required></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group"><label>Class</label><input type="text" name="class" placeholder="e.g. 10" required></div>
        <div class="form-group"><label>Section</label><input type="text" name="section" placeholder="e.g. A" required></div>
      </div>
      <?php if ($msg):   ?><div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <button type="submit" name="add" class="btn btn-primary btn-full" style="margin-top:8px">Add Student</button>
    </form>
  </div>
</div>
</body>
</html>

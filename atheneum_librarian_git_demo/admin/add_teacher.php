<?php
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

$msg = "";
$error = "";

if (isset($_POST['add'])) {
    verify_csrf_token();
    $id = trim($_POST['teacher_id']);
    $name = trim($_POST['name']);
    $dept = trim($_POST['department']);
    $desig = trim($_POST['designation']);

    if ($id=="" || $name=="" || $dept=="" || $desig=="") {
        $error = "All fields required.";
    } else {
        $checkStmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE teacher_id=?");
        $checkStmt->bind_param("s", $id);
        $checkStmt->execute();
        $check = $checkStmt->get_result();
        if ($check && $check->num_rows > 0) {
            $error = "Teacher ID already exists.";
        } else {
            $ins = $conn->prepare("INSERT INTO teachers (teacher_id, name, department, designation) VALUES (?,?,?,?)");
            $ins->bind_param("ssss", $id, $name, $dept, $desig);
            $ins->execute();
            $msg = "Teacher added successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Add Teacher — Atheneum</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="topbar"><a class="topbar-brand" href="index.php">📚 Atheneum</a><div class="topbar-right"><?php include("theme_toggle.php"); ?><span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span><a class="topbar-logout" href="../logout.php">Sign out</a></div></nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap"><a class="back-link" href="users.php">Back to Users</a><h2>Add Teacher</h2>
<div class="card" style="max-width:560px"><form method="post">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
  <div class="form-group"><label>Teacher ID</label><input name="teacher_id" required></div>
  <div class="form-group"><label>Name</label><input name="name" required></div>
  <div class="form-group"><label>Department</label><input name="department" required></div>
  <div class="form-group"><label>Designation</label><input name="designation" required></div>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <button class="btn btn-primary" name="add">Add Teacher</button>
</form>
</div></div>
</body>
</html>

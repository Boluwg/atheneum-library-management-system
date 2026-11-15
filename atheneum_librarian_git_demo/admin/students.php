<?php
$page_title = "Students";
include("auth.php");
include("../config/db.php");

$search = trim($_GET['search'] ?? '');
$where = "";
if ($search !== "") {
    $like = "%$search%";
    $where = "WHERE s.student_id LIKE ? OR s.name LIKE ? OR s.class LIKE ? OR s.section LIKE ?";
}

$stmt = $conn->prepare("
    SELECT s.student_id, s.name, s.class, s.section, COUNT(i.id) AS borrowed
    FROM students s
    LEFT JOIN issued_books i ON s.student_id = i.student_id
    $where
    GROUP BY s.student_id, s.name, s.class, s.section
    ORDER BY s.name
");
if ($search) { $stmt->bind_param("ssss", ...array_fill(0, 4, "%$search%")); }
$stmt->execute();
$r = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Students — Atheneum</title>
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
  <h2>Students</h2>

  <form method="get" class="filter-bar" style="margin-bottom:20px">
    <input type="text" name="search" placeholder="Search by ID, name, class, section…" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-secondary btn-sm">Search</button>
    <?php if ($search): ?><a href="students.php" class="btn btn-secondary btn-sm">✕ Clear</a><?php endif; ?>
    <a href="add_student.php" class="btn btn-primary btn-sm" style="margin-left:auto">+ Add Student</a>
  </form>

  <div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Name</th><th>Class</th><th>Section</th><th>Borrowed</th></tr></thead>
        <tbody>
        <?php if ($r->num_rows > 0): while ($s = $r->fetch_assoc()): ?>
          <tr>
            <td style="font-family:monospace;font-size:13px"><?= htmlspecialchars($s['student_id']) ?></td>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['class']) ?></td>
            <td><?= htmlspecialchars($s['section']) ?></td>
            <td><?= (int)$s['borrowed'] ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--muted)">No students found</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>

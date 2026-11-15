<?php
include("auth.php");
include("../config/db.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);



$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$query = "
    SELECT t.teacher_id, t.name, t.department, t.designation,
           COUNT(i.id) AS borrowed
    FROM teachers t
    LEFT JOIN issued_books i 
      ON t.teacher_id = i.student_id
    " . ($search !== "" ? "WHERE (teacher_id LIKE ? OR name LIKE ? OR department LIKE ? OR designation LIKE ?)" : "") . "
    GROUP BY t.teacher_id, t.name, t.department, t.designation
    ORDER BY t.name
";

$stmt = $conn->prepare($query);
if ($search !== "") {
    $term = "%$search%";
    $stmt->bind_param("ssss", $term, $term, $term, $term);
}
$stmt->execute();
$r = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Teachers</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="topbar"><a class="topbar-brand" href="index.php">📚 Atheneum</a><div class="topbar-right"><?php include("theme_toggle.php"); ?><span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span><a class="topbar-logout" href="../logout.php">Sign out</a></div></nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap">
<a class="back-link" href="users.php">Back to Users</a>
<h2>Teachers</h2>

<div class="filter-bar">
<form method="get">
  <input type="text" name="search" placeholder="Search ID, name, department"
         value="<?= htmlspecialchars($search) ?>">
  <button class="btn btn-primary" type="submit">Search</button>
  <a class="btn btn-secondary" href="teachers.php">Reset</a>
</form>
</div>

<div class="card" style="padding:0;overflow:hidden">
<div class="table-wrap">
<table>
<tr>
  <th>ID</th>
  <th>Name</th>
  <th>Department</th>
  <th>Designation</th>
  <th>Books Borrowed</th>
</tr>

<?php if ($r && $r->num_rows > 0): ?>
<?php while ($t = $r->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($t['teacher_id']) ?></td>
  <td><?= htmlspecialchars($t['name']) ?></td>
  <td><?= htmlspecialchars($t['department']) ?></td>
  <td><?= htmlspecialchars($t['designation']) ?></td>
  <td><?= (int)$t['borrowed'] ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5">No teachers found</td></tr>
<?php endif; ?>

</table>
</div></div></div>
</body>
</html>

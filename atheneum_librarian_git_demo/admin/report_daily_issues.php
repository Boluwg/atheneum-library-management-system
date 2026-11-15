<?php
$page_title = "Daily Issues";
include("auth.php");
include("../config/db.php");

$date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
$stmt = $conn->prepare("
    SELECT i.issue_date, i.due_date,
           b.title, b.accession_no,
           s.name AS borrower, i.student_id
    FROM issued_books i
    JOIN books b ON i.book_id = b.id
    LEFT JOIN students s ON i.student_id = s.student_id
    WHERE i.issue_date = ?
    ORDER BY b.title
");
$stmt->bind_param("s", $date);
$stmt->execute();
$r = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Daily Issues — Atheneum</title>
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
  <a class="back-link" href="reports.php">Back to Reports</a>
  <h2>Daily Issue Report</h2>

  <form method="get" style="margin-bottom:20px;display:flex;gap:10px;align-items:center">
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" style="width:auto">
    <button type="submit" class="btn btn-secondary btn-sm">View</button>
  </form>

  <div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Book</th><th>Accession</th><th>Borrower ID</th><th>Name</th><th>Issue Date</th><th>Due Date</th></tr>
        </thead>
        <tbody>
        <?php if ($r && $r->num_rows > 0): while ($row = $r->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['accession_no']) ?></td>
            <td style="font-family:monospace;font-size:13px"><?= htmlspecialchars($row['student_id']) ?></td>
            <td><?= htmlspecialchars($row['borrower'] ?? '—') ?></td>
            <td><?= $row['issue_date'] ?></td>
            <td><?= $row['due_date'] ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">No issues on this date.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>

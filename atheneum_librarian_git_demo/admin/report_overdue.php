<?php
$page_title = "Overdue Report";
include("auth.php");
include("../config/db.php");

$today = date("Y-m-d");
$stmt = $conn->prepare("
    SELECT i.issue_date, i.due_date,
           b.title, b.accession_no,
           s.name AS borrower, i.student_id
    FROM issued_books i
    JOIN books b ON i.book_id = b.id
    LEFT JOIN students s ON i.student_id = s.student_id
    WHERE i.due_date < ? AND b.status = 'issued'
    ORDER BY i.due_date
");
$stmt->bind_param("s", $today);
$stmt->execute();
$r = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Overdue — Atheneum</title>
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
  <h2>Overdue Books</h2>

  <div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Book</th><th>Accession</th><th>Borrower</th><th>Due Date</th><th>Days Overdue</th><th>Fine (₹5/day)</th></tr>
        </thead>
        <tbody>
        <?php if ($r && $r->num_rows > 0): while ($row = $r->fetch_assoc()):
            $days = (int)((strtotime($today) - strtotime($row['due_date'])) / 86400);
            $fine = $days * 5;
        ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['accession_no']) ?></td>
            <td><?= htmlspecialchars($row['borrower'] ?? $row['student_id']) ?></td>
            <td><?= $row['due_date'] ?></td>
            <td><span class="badge badge-cancelled"><?= $days ?> days</span></td>
            <td style="color:var(--danger);font-weight:600">₹<?= $fine ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--success)">🎉 No overdue books!</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>

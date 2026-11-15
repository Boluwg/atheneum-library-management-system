<?php
$page_title = "View Reservations";
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

$r = $conn->query("
    SELECT r.id, r.user_id, r.reserve_date, r.status, r.queue_position,
           b.title, b.accession_no
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    ORDER BY b.title, r.queue_position
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Reservations — Atheneum</title>
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
  <a class="back-link" href="reservations.php">Back to Reservations</a>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <h2 style="margin:0">Reservations</h2>
    <a href="add_reservation.php" class="btn btn-primary btn-sm">+ Add Reservation</a>
  </div>

  <div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Book</th><th>Accession</th><th>User ID</th><th>Queue</th><th>Reserved</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ($r && $r->num_rows > 0): while ($row = $r->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['accession_no']) ?></td>
            <td style="font-family:monospace;font-size:13px"><?= htmlspecialchars($row['user_id']) ?></td>
            <td style="text-align:center"><?= (int)$row['queue_position'] ?></td>
            <td><?= $row['reserve_date'] ?></td>
            <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= ucfirst($row['status']) ?></span></td>
            <td>
              <?php if ($row['status'] === 'active'): ?>
                <form method="post" action="cancel_reservation.php" onsubmit="return confirm('Cancel this reservation?')">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted)">No reservations found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>

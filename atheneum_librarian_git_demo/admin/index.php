<?php
$page_title = "Dashboard";
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("auth.php");
include("../config/db.php");

$totalBooks     = $conn->query("SELECT COUNT(*) AS c FROM books")->fetch_assoc()['c'];
$availableBooks = $conn->query("SELECT COUNT(*) AS c FROM books WHERE status='available'")->fetch_assoc()['c'];
$issuedBooks    = $conn->query("SELECT COUNT(*) AS c FROM books WHERE status='issued'")->fetch_assoc()['c'];
$reservedBooks  = $conn->query("SELECT COUNT(*) AS c FROM books WHERE status='reserved'")->fetch_assoc()['c'];
$overdueCount   = $conn->query("SELECT COUNT(*) AS c FROM issued_books i JOIN books b ON i.book_id = b.id WHERE i.due_date < CURDATE() AND b.status = 'issued'")->fetch_assoc()['c'];
$studentCount   = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Atheneum</title>
  <link rel="stylesheet" href="style.css?v=3">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .dashboard-grid { display: grid; grid-template-columns: 1fr 300px; gap: 24px; }
    @media (max-width: 800px) { .dashboard-grid { grid-template-columns: 1fr; } }
    .overdue-warning {
      background: rgba(224,92,92,0.08); border: 1px solid rgba(224,92,92,0.25);
      border-radius: 8px; padding: 12px 16px;
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 24px; font-size: 14px; color: #e05c5c;
    }
    .chart-container { position: relative; height: 200px; }
  </style>
</head>
<body>

<nav class="topbar">
  <a class="topbar-brand" href="index.php">📚 Atheneum</a>
  <div class="topbar-right">
    <?php include("theme_toggle.php"); ?>
    <span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
    <a class="topbar-logout" href="../logout.php">Sign out</a>
  </div>
</nav>
<?php include("quick_menu.php"); ?>

<div class="page-wrap">

  <h2>Dashboard</h2>

  <?php if ($overdueCount > 0): ?>
  <div class="overdue-warning">
    ⚠ <strong><?= $overdueCount ?></strong> book<?= $overdueCount != 1 ? 's are' : ' is' ?> overdue —
    <a href="report_overdue.php" style="color:inherit;font-weight:600;margin-left:4px">View report →</a>
  </div>
  <?php endif; ?>

  <!-- STAT TILES -->
  <div class="stat-grid">
    <div class="stat-tile">
      <div class="stat-number"><?= $totalBooks ?></div>
      <div class="stat-label">Total Books</div>
    </div>
    <div class="stat-tile">
      <div class="stat-number" style="color:var(--success)"><?= $availableBooks ?></div>
      <div class="stat-label">Available</div>
    </div>
    <div class="stat-tile">
      <div class="stat-number" style="color:var(--accent)"><?= $issuedBooks ?></div>
      <div class="stat-label">Issued</div>
    </div>
    <div class="stat-tile">
      <div class="stat-number" style="color:var(--info)"><?= $reservedBooks ?></div>
      <div class="stat-label">Reserved</div>
    </div>
    <div class="stat-tile">
      <div class="stat-number" style="color:var(--danger)"><?= $overdueCount ?></div>
      <div class="stat-label">Overdue</div>
    </div>
    <div class="stat-tile">
      <div class="stat-number"><?= $studentCount ?></div>
      <div class="stat-label">Students</div>
    </div>
  </div>

  <div class="dashboard-grid">
    <!-- NAV TILES -->
    <div>
      <h3>Quick Access</h3>
      <div class="tile-grid">
        <a class="tile" href="circulation.php">
          <span class="tile-icon">🔄</span>Circulation
        </a>
        <a class="tile" href="inventory.php">
          <span class="tile-icon">📦</span>Inventory
        </a>
        <a class="tile" href="users.php">
          <span class="tile-icon">👥</span>Users
        </a>
        <a class="tile" href="reports.php">
          <span class="tile-icon">📊</span>Reports
        </a>
        <a class="tile" href="reservations.php">
          <span class="tile-icon">🔖</span>Reservations
        </a>
      </div>
    </div>

    <!-- CHART -->
    <div>
      <h3>Book Status</h3>
      <div class="card" style="padding:20px">
        <div class="chart-container">
          <canvas id="libraryChart"></canvas>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
new Chart(document.getElementById('libraryChart'), {
  type: 'doughnut',
  data: {
    labels: ['Available', 'Issued', 'Reserved'],
    datasets: [{
      data: [<?= $availableBooks ?>, <?= $issuedBooks ?>, <?= $reservedBooks ?>],
      backgroundColor: ['#4caf80', '#c9a84c', '#5c9be0'],
      borderColor: '#181c27',
      borderWidth: 3,
      hoverOffset: 6
    }]
  },
  options: {
    cutout: '68%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: { color: '#7a7f96', font: { size: 12, family: 'DM Sans' }, padding: 14 }
      }
    }
  }
});
</script>

</body>
</html>

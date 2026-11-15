<?php $quickPage = basename($_SERVER['PHP_SELF']); ?>
<div id="globalScannerStatus" class="global-scanner-status" hidden aria-live="polite">
  Scanner reconnecting...
</div>
<nav class="quick-menu" aria-label="Quick menu">
  <div class="quick-menu-inner">
  <span class="quick-menu-label">Navigate</span>
  <a class="<?= $quickPage === 'index.php' ? 'active' : '' ?>" href="index.php"><span>⌂</span>Dashboard</a>
  <a class="<?= $quickPage === 'issue.php' ? 'active' : '' ?>" href="issue.php"><span>↑</span>Issue</a>
  <a class="<?= $quickPage === 'return.php' ? 'active' : '' ?>" href="return.php"><span>↓</span>Return</a>
  <a class="<?= $quickPage === 'inventory.php' ? 'active' : '' ?>" href="inventory.php"><span>▣</span>Inventory</a>
  <a class="<?= in_array($quickPage, ['users.php', 'students.php', 'teachers.php', 'set_user_password.php', 'password_requests.php', 'account_settings.php'], true) ? 'active' : '' ?>" href="users.php"><span>♙</span>Users</a>
  <a class="<?= in_array($quickPage, ['reservations.php', 'view_reservations.php', 'add_reservation.php'], true) ? 'active' : '' ?>" href="reservations.php"><span>◇</span>Reservations</a>
  <a class="<?= in_array($quickPage, ['reports.php', 'report_overdue.php', 'report_daily_issues.php'], true) ? 'active' : '' ?>" href="reports.php"><span>▤</span>Reports</a>
  </div>
</nav>
<script src="../assets/scanner_status.js?v=3"></script>

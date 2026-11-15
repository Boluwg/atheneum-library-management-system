<?php
$page_title = "Password Requests";
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $requestId = (int)($_POST['request_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($requestId > 0 && in_array($status, ['approved', 'rejected'], true)) {
        $stmt = $conn->prepare("UPDATE password_reset_requests SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param("sii", $status, $_SESSION['admin_id'], $requestId);
        $stmt->execute();
    }
}

$requests = $conn->query("SELECT id, user_type, user_id, reason, status, created_at FROM password_reset_requests ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Password Requests — Atheneum</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="topbar"><a class="topbar-brand" href="index.php">📚 Atheneum</a><div class="topbar-right"><?php include("theme_toggle.php"); ?><span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span><a class="topbar-logout" href="../logout.php">Sign out</a></div></nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap"><a class="back-link" href="users.php">Back to Users</a><h2>Password Requests</h2>
<div class="card" style="padding:0;overflow:hidden"><div class="table-wrap"><table><thead><tr><th>User</th><th>Reason</th><th>Status</th><th>Requested</th><th>Action</th></tr></thead><tbody>
<?php while ($row = $requests->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars(ucfirst($row['user_type']) . ' — ' . $row['user_id']) ?></td>
  <td><?= htmlspecialchars($row['reason']) ?></td>
  <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
  <td><?= htmlspecialchars($row['created_at']) ?></td>
  <td><?php if ($row['status'] === 'pending'): ?><form method="post" style="display:flex;gap:6px"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>"><button class="btn btn-primary btn-sm" name="status" value="approved">Approve</button><button class="btn btn-danger btn-sm" name="status" value="rejected">Reject</button></form><?php endif; ?></td>
</tr>
<?php endwhile; ?>
</tbody></table></div></div></div>
</body>
</html>

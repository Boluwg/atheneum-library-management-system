<?php
$page_title = "View Books";
include("auth.php");
include("../config/db.php");

$limit  = 15;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$category = trim($_GET['category'] ?? '');
$categories = ['fiction' => 'Fiction', 'non_fiction' => 'Non-fiction', 'academic' => 'Academic', 'other' => 'Other'];

$where = "WHERE 1=1";
$params = []; $types = "";

if ($search !== "") {
    $like = "%$search%";
    $where .= " AND (title LIKE ? OR author LIKE ? OR accession_no LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}
if ($status !== "") {
    $where .= " AND status=?";
    $params[] = $status; $types .= "s";
}
if (array_key_exists($category, $categories)) {
    $where .= " AND category=?";
    $params[] = $category; $types .= "s";
} else {
    $category = "";
}

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM books $where");
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows  = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = (int)ceil($totalRows / $limit);

$dataStmt = $conn->prepare("SELECT * FROM books $where ORDER BY title LIMIT ? OFFSET ?");
$allParams = array_merge($params, [$limit, $offset]);
$allTypes  = $types . "ii";
$dataStmt->bind_param($allTypes, ...$allParams);
$dataStmt->execute();
$r = $dataStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Books — Atheneum</title>
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
  <a class="back-link" href="inventory.php">Back to Inventory</a>
  <h2>Books <span style="color:var(--muted);font-size:16px;font-family:'DM Sans'">(<?= $totalRows ?>)</span></h2>

  <form method="get" class="filter-bar">
    <input type="text" name="search" placeholder="Search title, author, accession…" value="<?= htmlspecialchars($search) ?>">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <select name="category" aria-label="Filter by book type">
      <option value="">All types</option>
      <?php foreach ($categories as $value => $label): ?><option value="<?= $value ?>" <?= $category === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Search</button>
    <a href="view_books.php" class="filter-pill <?= $status===''?'active':'' ?>">All</a>
    <a href="view_books.php?status=available&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="filter-pill <?= $status==='available'?'active':'' ?>">Available</a>
    <a href="view_books.php?status=issued&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="filter-pill <?= $status==='issued'?'active':'' ?>">Issued</a>
    <a href="view_books.php?status=reserved&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="filter-pill <?= $status==='reserved'?'active':'' ?>">Reserved</a>
    <a href="export_books.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&category=<?= urlencode($category) ?>" class="btn btn-secondary btn-sm">⬆ Export CSV</a>
    <a href="import_books.php" class="btn btn-secondary btn-sm">⬇ Import CSV</a>
  </form>

  <div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Title</th><th>Author</th><th>Type</th><th>Accession</th><th>RFID</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if ($r->num_rows > 0): while ($b = $r->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($b['title']) ?></td>
            <td><?= htmlspecialchars($b['author']) ?></td>
            <td><span class="filter-pill"><?= htmlspecialchars($categories[$b['category']] ?? 'Other') ?></span></td>
            <td><?= htmlspecialchars($b['accession_no']) ?></td>
            <td style="font-family:monospace;font-size:12px;color:var(--muted)"><?= htmlspecialchars($b['rfid_tag']) ?></td>
            <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">No books found</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?=$i?>&search=<?=urlencode($search)?>&status=<?=urlencode($status)?>&category=<?=urlencode($category)?>"
         class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</div>
<script src="../assets/csv_export.js?v=1"></script>
</body>
</html>

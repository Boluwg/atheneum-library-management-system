<?php
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();


$msg = "";
$error = "";

if (isset($_POST['reserve'])) {
    verify_csrf_token();
    $user_id = trim($_POST['user_id']);
    $accession = trim($_POST['accession_no']);

    if ($user_id == "" || $accession == "") {
        $error = "All fields required.";
    } else {
        $bookStmt = $conn->prepare("SELECT * FROM books WHERE accession_no=?");
        $bookStmt->bind_param("s", $accession);
        $bookStmt->execute();
        $b = $bookStmt->get_result();
        if (!$b || $b->num_rows == 0) {
            $error = "Book not found.";
        } else {
            $book = $b->fetch_assoc();

            if ($book['status'] == 'available') {
                $error = "Book is available. Issue it directly.";
            } else {
                $queueStmt = $conn->prepare("SELECT MAX(queue_position) AS max_pos FROM reservations WHERE book_id=? AND status='active'");
                $queueStmt->bind_param("i", $book['id']);
                $queueStmt->execute();
                $q = $queueStmt->get_result();
                $row = $q->fetch_assoc();
                $nextPos = ($row['max_pos'] ?? 0) + 1;

                $insertStmt = $conn->prepare("INSERT INTO reservations (book_id, user_id, reserve_date, queue_position) VALUES (?, ?, CURDATE(), ?)");
                $insertStmt->bind_param("isi", $book['id'], $user_id, $nextPos);
                $insertStmt->execute();

                $msg = "Reservation added. Queue position: $nextPos";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Add Reservation — Atheneum</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="topbar"><a class="topbar-brand" href="index.php">📚 Atheneum</a><div class="topbar-right"><?php include("theme_toggle.php"); ?><span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span><a class="topbar-logout" href="../logout.php">Sign out</a></div></nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap"><a class="back-link" href="reservations.php">Back to Reservations</a><h2>Add Reservation</h2>
<div class="card" style="max-width:560px">
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
  <div class="form-group"><label>Student / Teacher ID</label><input name="user_id" required></div>
  <div class="form-group"><label>Book Accession Number</label><input name="accession_no" required></div>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <button class="btn btn-primary" name="reserve">Reserve Book</button>
</form></div></div>
</body>
</html>

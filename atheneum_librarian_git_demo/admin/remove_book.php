<?php
include("auth.php");
include("../config/db.php");
$csrfToken = csrf_token();


$msg = "";
$error = "";

if (isset($_POST['remove'])) {
    verify_csrf_token();

    $rfid = trim($_POST['rfid']);
    $accession = trim($_POST['accession_no']);

    // ❌ If both fields are empty
    if ($rfid === "" && $accession === "") {
        $error = "Please enter RFID or Accession Number.";
    } 
    else {

        // Remove by RFID
        if ($rfid !== "") {
            $checkStmt = $conn->prepare("SELECT id FROM books WHERE rfid_tag=?");
            $checkStmt->bind_param("s", $rfid);
            $checkStmt->execute();
            $check = $checkStmt->get_result();
            if ($check && $check->num_rows == 1) {
                $deleteStmt = $conn->prepare("DELETE FROM books WHERE rfid_tag=? AND status='available'");
                $deleteStmt->bind_param("s", $rfid);
                $deleteStmt->execute();
                $msg = "Book removed successfully.";
            } else {
                $error = "No book found with this RFID.";
            }
        }

        // Remove by Accession Number
        else {
            $checkStmt = $conn->prepare("SELECT id FROM books WHERE accession_no=?");
            $checkStmt->bind_param("s", $accession);
            $checkStmt->execute();
            $check = $checkStmt->get_result();
            if ($check && $check->num_rows == 1) {
                $deleteStmt = $conn->prepare("DELETE FROM books WHERE accession_no=? AND status='available'");
                $deleteStmt->bind_param("s", $accession);
                $deleteStmt->execute();
                $msg = "Book removed successfully.";
            } else {
                $error = "No book found with this accession number.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Remove Book — Atheneum</title><link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="topbar"><a class="topbar-brand" href="index.php">📚 Atheneum</a><div class="topbar-right"><?php include("theme_toggle.php"); ?><span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span><a class="topbar-logout" href="../logout.php">Sign out</a></div></nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap"><a class="back-link" href="inventory.php">Back to Inventory</a><h2>Remove Book</h2>
<div class="card" style="max-width:520px"><form method="post">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
  <div class="form-group"><label>RFID (optional)</label><input type="text" name="rfid"></div>
  <div class="form-group"><label>Accession Number (optional)</label><input type="text" name="accession_no"></div>
  <button class="btn btn-danger" type="submit" name="remove">Remove Book</button>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
</form></div></div>
</body>
</html>

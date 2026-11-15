<?php
include("auth.php");
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf_token();

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    $select = $conn->prepare("SELECT * FROM reservations WHERE id=?");
    $select->bind_param("i", $id);
    $select->execute();
    $r = $select->get_result();
    if ($r && $r->num_rows == 1) {
        $res = $r->fetch_assoc();
        $book_id = $res['book_id'];
        $pos = $res['queue_position'];

        $update = $conn->prepare("UPDATE reservations SET status='cancelled' WHERE id=?");
        $update->bind_param("i", $id);
        $update->execute();

        // Shift queue
        $shift = $conn->prepare("UPDATE reservations SET queue_position = queue_position - 1 WHERE book_id=? AND queue_position > ? AND status='active'");
        $shift->bind_param("ii", $book_id, $pos);
        $shift->execute();
    }
}

header("Location: view_reservations.php");
exit;

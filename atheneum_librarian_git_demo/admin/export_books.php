<?php
include("auth.php");
include("../config/db.php");


$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$status = isset($_GET['status']) ? trim($_GET['status']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";
$categories = ["fiction", "non_fiction", "academic", "other"];

$conditions = ["1=1"];
$params = [];
$types = "";
if ($search !== "") {
    $conditions[] = "(title LIKE ? OR author LIKE ? OR accession_no LIKE ?)";
    $term = "%$search%";
    array_push($params, $term, $term, $term);
    $types .= "sss";
}
if ($status !== "") {
    $conditions[] = "status=?";
    $params[] = $status;
    $types .= "s";
}
if (in_array($category, $categories, true)) {
    $conditions[] = "category=?";
    $params[] = $category;
    $types .= "s";
} else {
    $category = "";
}
$where = "WHERE " . implode(" AND ", $conditions);

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=library_books.csv");

$output = fopen("php://output", "w");
fputcsv($output, ["Title", "Author", "Category", "Accession No", "RFID", "Status"]);

$stmt = $conn->prepare("SELECT * FROM books $where ORDER BY title");
if ($search !== "" && $status !== "") {
    if ($category !== "") $stmt->bind_param("sssss", $term, $term, $term, $status, $category);
    else $stmt->bind_param("ssss", $term, $term, $term, $status);
} elseif ($search !== "") {
    if ($category !== "") $stmt->bind_param("ssss", $term, $term, $term, $category);
    else $stmt->bind_param("sss", $term, $term, $term);
} elseif ($status !== "") {
    if ($category !== "") $stmt->bind_param("ss", $status, $category);
    else $stmt->bind_param("s", $status);
} elseif ($category !== "") {
    $stmt->bind_param("s", $category);
}
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    fputcsv($output, [
        $row['title'],
        $row['author'],
        $row['category'],
        $row['accession_no'],
        $row['rfid_tag'],
        $row['status']
    ]);
}

fclose($output);
exit;

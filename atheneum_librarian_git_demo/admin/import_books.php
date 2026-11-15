<?php
$page_title = "Import Books";
include("auth.php");
include("../config/db.php");

$csrfToken = csrf_token();
$categories = [
    'fiction' => 'Fiction',
    'non_fiction' => 'Non-fiction',
    'academic' => 'Academic',
    'other' => 'Other'
];
$message = "";
$errors = [];

if (isset($_GET['template'])) {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=atheneum_books_template.csv");
    $output = fopen("php://output", "w");
    fputcsv($output, ["Title", "Author", "Category", "Accession No", "RFID"]);
    fputcsv($output, ["Example title", "Example author", "fiction", "LIB-00001", "115B3C5D"]);
    fclose($output);
    exit;
}

if (isset($_POST['import_books'])) {
    verify_csrf_token();
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Choose a CSV file to import.";
    } elseif ($_FILES['csv']['size'] > 5 * 1024 * 1024) {
        $errors[] = "The CSV file must be smaller than 5 MB.";
    } else {
        $handle = fopen($_FILES['csv']['tmp_name'], "r");
        if ($handle === false) {
            $errors[] = "The uploaded CSV file could not be opened.";
        } else {
            $header = fgetcsv($handle);
            $headerMap = [];
            if (is_array($header)) {
                foreach ($header as $index => $column) {
                    $normalized = strtolower((string)preg_replace('/[^a-z0-9]+/i', '_', trim($column)));
                    $headerMap[$normalized] = $index;
                }
            }
            $requiredColumns = [
                'title' => 'Title',
                'author' => 'Author',
                'category' => 'Category',
                'accession_no' => 'Accession No',
                'rfid' => 'RFID'
            ];
            foreach ($requiredColumns as $key => $label) {
                if (!array_key_exists($key, $headerMap) &&
                    !($key === 'rfid' && array_key_exists('rfid_tag', $headerMap))) {
                    $errors[] = "Missing required column: $label.";
                }
            }

            if (!$errors) {
                $rfidColumn = $headerMap['rfid'] ?? $headerMap['rfid_tag'];
                $duplicate = $conn->prepare("SELECT id FROM books WHERE rfid_tag=? OR accession_no=? LIMIT 1");
                $insert = $conn->prepare(
                    "INSERT INTO books (title, author, category, accession_no, rfid_tag, status)
                     VALUES (?, ?, ?, ?, ?, 'available')"
                );
                $imported = 0;
                $rowNumber = 1;
                $conn->begin_transaction();
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    if (count(array_filter($row, static fn($value) => trim((string)$value) !== "")) === 0) {
                        continue;
                    }
                    $title = trim((string)($row[$headerMap['title']] ?? ''));
                    $author = trim((string)($row[$headerMap['author']] ?? ''));
                    $category = trim((string)($row[$headerMap['category']] ?? ''));
                    $accession = trim((string)($row[$headerMap['accession_no']] ?? ''));
                    $rfid = trim((string)($row[$rfidColumn] ?? ''));

                    if ($title === '' || $author === '' || $accession === '' || $rfid === '') {
                        $errors[] = "Row $rowNumber is missing title, author, accession number, or RFID.";
                        continue;
                    }
                    if (!array_key_exists($category, $categories)) {
                        $errors[] = "Row $rowNumber has an invalid category.";
                        continue;
                    }

                    $duplicate->bind_param("ss", $rfid, $accession);
                    $duplicate->execute();
                    if ($duplicate->get_result()->num_rows > 0) {
                        $errors[] = "Row $rowNumber was skipped because its RFID or accession number already exists.";
                        continue;
                    }

                    $insert->bind_param("sssss", $title, $author, $category, $accession, $rfid);
                    if (!$insert->execute()) {
                        $errors[] = "Row $rowNumber could not be imported.";
                        continue;
                    }
                    $imported++;
                }
                $conn->commit();
                $message = "$imported book(s) imported successfully.";
            }
            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Import Books — Atheneum</title>
  <link rel="stylesheet" href="style.css?v=4">
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
  <a class="back-link" href="view_books.php">Back to Books</a>
  <h2>Import Books</h2>
  <div class="card" style="max-width:680px">
    <p style="color:var(--muted);margin-bottom:16px">
      Upload a CSV containing Title, Author, Category, Accession No, and RFID.
      Imported books start as available.
    </p>
    <a class="btn btn-secondary btn-sm" href="import_books.php?template=1">Download CSV template</a>
    <form method="post" enctype="multipart/form-data" style="margin-top:20px">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group">
        <label for="csv">Book CSV file</label>
        <input type="file" id="csv" name="csv" accept=".csv,text/csv" required>
      </div>
      <button type="submit" name="import_books" class="btn btn-primary">Import books</button>
    </form>
    <?php if ($message): ?>
      <div class="alert alert-success" style="margin-top:16px">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error" style="margin-top:10px">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();

header('Content-Type: application/json; charset=utf-8');

function api_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    api_response(401, ['error' => 'Admin authentication required.']);
}

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    api_response(200, [
        'authenticated' => true,
        'csrf_token' => csrf_token(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response(405, ['error' => 'Method not allowed.']);
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrf) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrf)) {
    api_response(403, ['error' => 'Invalid security token.']);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    api_response(400, ['error' => 'Invalid JSON request.']);
}

$action = $payload['action'] ?? '';
$rfid = trim((string)($payload['rfid'] ?? ''));
$userId = trim((string)($payload['user_id'] ?? ''));

if ($rfid === '' || strlen($rfid) > 100) {
    api_response(422, ['error' => 'A valid RFID tag is required.']);
}

try {
    if ($action === 'issue') {
        if ($userId === '' || strlen($userId) > 50) {
            api_response(422, ['error' => 'A valid borrower ID is required.']);
        }

        $userStmt = $conn->prepare(
            "SELECT student_id AS user_id, name FROM students WHERE student_id=?
             UNION ALL
             SELECT teacher_id AS user_id, name FROM teachers WHERE teacher_id=?"
        );
        $userStmt->bind_param('ss', $userId, $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        if ($userResult->num_rows !== 1) {
            api_response(404, ['error' => 'Borrower not found.']);
        }
        $user = $userResult->fetch_assoc();

        $conn->begin_transaction();
        $transactionOpen = true;
        $bookStmt = $conn->prepare(
            "SELECT id, title, accession_no, status FROM books WHERE rfid_tag=? FOR UPDATE"
        );
        $bookStmt->bind_param('s', $rfid);
        $bookStmt->execute();
        $bookResult = $bookStmt->get_result();
        if ($bookResult->num_rows !== 1) {
            throw new RuntimeException('Book not found.');
        }
        $book = $bookResult->fetch_assoc();
        if ($book['status'] !== 'available') {
            throw new RuntimeException('Book is not available for issue.');
        }

        $issueDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+7 days'));
        $issueStmt = $conn->prepare(
            'INSERT INTO issued_books (student_id, book_id, issue_date, due_date) VALUES (?, ?, ?, ?)'
        );
        $issueStmt->bind_param('siss', $userId, $book['id'], $issueDate, $dueDate);
        $issueStmt->execute();

        $updateStmt = $conn->prepare("UPDATE books SET status='issued' WHERE id=?");
        $updateStmt->bind_param('i', $book['id']);
        $updateStmt->execute();
        $conn->commit();
        $transactionOpen = false;

        api_response(200, [
            'success' => true,
            'message' => 'Book issued successfully.',
            'borrower' => $user['name'],
            'title' => $book['title'],
            'accession_no' => $book['accession_no'],
            'due_date' => $dueDate,
        ]);
    }

    if ($action === 'return') {
        $conn->begin_transaction();
        $transactionOpen = true;
        $bookStmt = $conn->prepare(
            "SELECT id, title, accession_no FROM books WHERE rfid_tag=? FOR UPDATE"
        );
        $bookStmt->bind_param('s', $rfid);
        $bookStmt->execute();
        $bookResult = $bookStmt->get_result();
        if ($bookResult->num_rows !== 1) {
            throw new RuntimeException('Book not found.');
        }
        $book = $bookResult->fetch_assoc();

        $issueStmt = $conn->prepare(
            'SELECT id, student_id, due_date FROM issued_books
             WHERE book_id=? AND return_date IS NULL ORDER BY id DESC LIMIT 1 FOR UPDATE'
        );
        $issueStmt->bind_param('i', $book['id']);
        $issueStmt->execute();
        $issueResult = $issueStmt->get_result();
        if ($issueResult->num_rows !== 1) {
            throw new RuntimeException('Book is not currently issued.');
        }
        $issue = $issueResult->fetch_assoc();

        $returnDate = date('Y-m-d');
        $returnStmt = $conn->prepare('UPDATE issued_books SET return_date=? WHERE id=?');
        $returnStmt->bind_param('si', $returnDate, $issue['id']);
        $returnStmt->execute();

        $updateStmt = $conn->prepare("UPDATE books SET status='available' WHERE id=?");
        $updateStmt->bind_param('i', $book['id']);
        $updateStmt->execute();
        $conn->commit();
        $transactionOpen = false;

        $overdueDays = max(
            0,
            (int)((strtotime($returnDate) - strtotime($issue['due_date'])) / 86400)
        );
        api_response(200, [
            'success' => true,
            'message' => 'Book returned successfully.',
            'title' => $book['title'],
            'accession_no' => $book['accession_no'],
            'borrower_id' => $issue['student_id'],
            'fine' => $overdueDays * 5,
        ]);
    }

    api_response(422, ['error' => 'Unknown circulation action.']);
} catch (Throwable $error) {
    if (!empty($transactionOpen)) {
        $conn->rollback();
    }
    error_log('Circulation API error: ' . $error->getMessage());
    api_response(500, ['error' => $error->getMessage()]);
}

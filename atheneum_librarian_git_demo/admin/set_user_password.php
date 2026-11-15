<?php
$page_title = "Set User Password";
include("auth.php");
include("../config/db.php");

$csrfToken = csrf_token();
$message = "";
$error = "";
$users = [];
$userResult = $conn->query("SELECT student_id AS user_id, name, 'student' AS user_type FROM students UNION ALL SELECT teacher_id AS user_id, name, 'teacher' AS user_type FROM teachers ORDER BY name");
while ($userResult && ($user = $userResult->fetch_assoc())) {
    $users[] = $user;
}

if (isset($_POST['save'])) {
    verify_csrf_token();
    $type = $_POST['user_type'] ?? "";
    $userId = trim($_POST['user_id'] ?? "");
    $password = $_POST['password'] ?? "";

    if (!in_array($type, ["student", "teacher"], true) ||
        $userId === "" || strlen($userId) > 64 || strlen($password) < 8 || strlen($password) > 128) {
        $error = "Choose a valid user and a password of 8 to 128 characters.";
    } elseif ($type === "student") {
        $stmt = $conn->prepare("UPDATE students SET password_hash=?, password_version=password_version+1 WHERE student_id=?");
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("ss", $hash, $userId);
        $stmt->execute();
        $message = $stmt->affected_rows === 1 ? "Student password updated." : "Student not found.";
    } else {
        $stmt = $conn->prepare("UPDATE teachers SET password_hash=?, password_version=password_version+1 WHERE teacher_id=?");
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("ss", $hash, $userId);
        $stmt->execute();
        $message = $stmt->affected_rows === 1 ? "Teacher password updated." : "Teacher not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Set User Password</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="topbar"><a class="topbar-brand" href="users.php">Atheneum</a><div class="topbar-right"><?php include("theme_toggle.php"); ?><a class="topbar-logout" href="../logout.php">Sign out</a></div></nav>
<?php include("quick_menu.php"); ?>
<div class="page-wrap">
  <a class="back-link" href="users.php">Back to Users</a>
  <h2>Set User Password</h2>
  <div class="card" style="max-width:520px">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div class="form-group">
        <label for="user_type">1. User type</label>
        <select name="user_type" id="user_type" required>
          <option value="">Choose type first</option>
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
        </select>
      </div>
      <div class="form-group">
        <label for="user_id">2. Search and select user</label>
        <input type="text" name="user_id" id="user_id" list="user_options"
               placeholder="Choose a type first" autocomplete="off" required disabled>
        <datalist id="user_options"></datalist>
      </div>
      <div class="form-group"><label>New password</label><input type="password" name="password" minlength="8" maxlength="128" required></div>
      <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <button type="submit" name="save" class="btn btn-primary">Save Password</button>
    </form>
  </div>
</div>
<script>
const users = <?= json_encode($users, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const typeSelect = document.getElementById("user_type");
const userInput = document.getElementById("user_id");
const options = document.getElementById("user_options");

function refreshUserOptions() {
  const type = typeSelect.value;
  options.replaceChildren();
  userInput.value = "";
  userInput.disabled = !type;
  userInput.placeholder = type ? "Search by name or ID" : "Choose a type first";
  users.filter(user => user.user_type === type).forEach(user => {
    const option = document.createElement("option");
    option.value = user.user_id;
    option.label = `${user.name} — ${user.user_id}`;
    options.appendChild(option);
  });
}

typeSelect.addEventListener("change", refreshUserOptions);
</script>
</body>
</html>

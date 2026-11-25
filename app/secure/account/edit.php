<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// [보완 3] IDOR 차단: URL 의 id 를 받지 않고, 항상 세션의 본인 id 만 사용
$id = $_SESSION['user_id'];

$message = "";
$error   = "";

// [보완 1] Prepared Statement
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { die("회원 정보를 찾을 수 없습니다."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // [보완 4]

    $name     = $_POST['name'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($password !== "") {
        // [보완 5] 비밀번호 변경 시에도 해시로 저장
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $hashed, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $email, $id);
    }

    if ($stmt->execute()) {
        $message = "회원 정보가 수정되었습니다.";
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $_SESSION['name'] = $user['name'];
    } else {
        $error = "수정에 실패했습니다.";
    }
}

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . "/../includes/header.php";
?>
<div class="narrow" style="margin:0 auto">
  <div class="page-head">
    <span class="eyebrow">Account</span>
    <h2>회원 정보 수정</h2>
    <p>이름·이메일·비밀번호를 바꿀 수 있어요.</p>
  </div>
  <?php if ($message): ?><div class="alert alert-ok"><?php echo $h($message); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?php echo $h($error); ?></div><?php endif; ?>
  <div class="card">
    <form method="post" action="edit.php">
      <?php echo csrf_field(); ?>
      <div class="field"><label>아이디</label><input value="<?php echo $h($user['username']); ?>" disabled></div>
      <div class="field"><label>이름</label><input name="name" value="<?php echo $h($user['name']); ?>" required></div>
      <div class="field"><label>이메일</label><input name="email" value="<?php echo $h($user['email']); ?>" required></div>
      <div class="field"><label>새 비밀번호 <span class="muted">(안 바꾸려면 비워 두세요)</span></label><input type="password" name="password"></div>
      <button class="btn btn-primary full" type="submit">저장</button>
    </form>
    <p class="center" style="margin-top:14px"><a class="muted" href="delete_account.php">회원 탈퇴</a></p>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

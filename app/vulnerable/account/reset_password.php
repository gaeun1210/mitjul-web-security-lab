<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$message = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = $_POST['username'] ?? '';
    $email        = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // [취약 1] 입력값을 그대로 쿼리에 삽입
    $row = $conn->query("SELECT id FROM users WHERE username='$username' AND email='$email'")->fetch_assoc();
    if ($row) {
        // [취약 5] 평문 비밀번호로 즉시 변경 (이메일 인증 등 본인확인 절차 없음)
        $conn->query("UPDATE users SET password='$new_password' WHERE id=" . $row['id']);
        $message = "비밀번호가 변경되었습니다. 새 비밀번호로 로그인해 주세요.";
    } else {
        $error = "회원 정보를 확인해주세요.";
    }
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="narrow" style="margin:0 auto">
  <div class="page-head"><span class="eyebrow">Account</span><h2>비밀번호 재설정</h2>
  <p>아이디와 이메일을 입력하면 새 비밀번호로 바꿔 드려요.</p></div>
  <?php if ($message): ?><div class="alert alert-ok"><?php echo $message; ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
  <div class="card">
    <form method="post">
      <div class="field"><label>아이디</label><input name="username" required></div>
      <div class="field"><label>이메일</label><input type="email" name="email" required></div>
      <div class="field"><label>새 비밀번호</label><input type="password" name="new_password" required></div>
      <button class="btn btn-primary full" type="submit">비밀번호 변경</button>
    </form>
    <p class="center muted" style="margin-top:14px"><a href="login.php">로그인으로</a></p>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // [보완 4]

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name     = $_POST['name'] ?? '';
    $email    = $_POST['email'] ?? '';

    // [보완 1] Prepared Statement 로 중복 확인
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $error = "이미 사용 중인 아이디입니다.";
    } else {
        // [보완 5] 비밀번호를 bcrypt 해시로 저장
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, name, email, role) VALUES (?, ?, ?, ?, 'member')"
        );
        $stmt->bind_param("ssss", $username, $hashed, $name, $email);
        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit;
        }
        $error = "가입에 실패했습니다.";
    }
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="narrow" style="margin:0 auto">
  <div class="page-head">
    <span class="eyebrow">Account</span>
    <h2>가입</h2>
    <p>문장을 남기려면 먼저 가입이 필요해요.</p>
  </div>
  <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <div class="card">
    <form method="post">
      <?php echo csrf_field(); ?>
      <div class="field"><label>아이디</label><input name="username" required></div>
      <div class="field"><label>비밀번호</label><input type="password" name="password" required></div>
      <div class="field"><label>이름</label><input name="name" required></div>
      <div class="field"><label>이메일</label><input type="email" name="email" required></div>
      <button class="btn btn-primary full" type="submit">가입하기</button>
    </form>
    <p class="center muted" style="margin-top:14px">이미 계정이 있으세요? <a href="login.php">로그인</a></p>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

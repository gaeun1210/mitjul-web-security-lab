<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name     = $_POST['name'] ?? '';
    $email    = $_POST['email'] ?? '';

    // [취약 1] 아이디 중복 확인 쿼리에 입력값을 그대로 이어붙임
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check && $check->num_rows > 0) {
        $error = "이미 사용 중인 아이디입니다.";
    } else {
        // [취약 5] 비밀번호를 평문 그대로 저장
        $sql = "INSERT INTO users (username, password, name, email, role)
                VALUES ('$username', '$password', '$name', '$email', 'member')";
        if ($conn->query($sql)) {
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
  <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
  <div class="card">
    <form method="post">
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

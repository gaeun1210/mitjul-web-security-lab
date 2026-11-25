<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// [취약 3] IDOR: URL 의 id 를 그대로 받고, 본인/관리자인지 확인하지 않음
$id = $_GET['id'] ?? $_SESSION['user_id'];

$message = "";
$error   = "";

// [취약 1] id 를 그대로 쿼리에 삽입
$user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
if (!$user) { die("회원 정보를 찾을 수 없습니다."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [취약 4] CSRF 토큰 검증 없음
    $name     = $_POST['name'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($password !== "") {
        // [취약 5] 평문 비밀번호 저장
        $sql = "UPDATE users SET name='$name', email='$email', password='$password' WHERE id=$id";
    } else {
        $sql = "UPDATE users SET name='$name', email='$email' WHERE id=$id";
    }

    if ($conn->query($sql)) {
        $message = "회원 정보가 수정되었습니다.";
        $user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
        if ($id == $_SESSION['user_id']) { $_SESSION['name'] = $user['name']; }
    } else {
        $error = "수정에 실패했습니다.";
    }
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="narrow" style="margin:0 auto">
  <div class="page-head">
    <span class="eyebrow">Account</span>
    <h2>회원 정보 수정</h2>
    <p>이름·이메일·비밀번호를 바꿀 수 있어요.</p>
  </div>
  <?php if ($message): ?><div class="alert alert-ok"><?php echo $message; ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
  <div class="card">
    <form method="post" action="edit.php?id=<?php echo $id; ?>">
      <div class="field"><label>아이디</label><input value="<?php echo $user['username']; ?>" disabled></div>
      <div class="field"><label>이름</label><input name="name" value="<?php echo $user['name']; ?>" required></div>
      <div class="field"><label>이메일</label><input name="email" value="<?php echo $user['email']; ?>" required></div>
      <div class="field"><label>새 비밀번호 <span class="muted">(안 바꾸려면 비워 두세요)</span></label><input type="password" name="password"></div>
      <button class="btn btn-primary full" type="submit">저장</button>
    </form>
    <p class="center" style="margin-top:14px">
      <a class="muted" href="delete_account.php">회원 탈퇴</a>
    </p>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

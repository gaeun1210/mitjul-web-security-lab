<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// [취약 3] IDOR: 삭제 대상 id 를 URL 로 받되 본인/관리자 확인 없음
$id = $_GET['id'] ?? $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [취약 4] CSRF 토큰 검증 없음
    // [취약 1] id 를 그대로 쿼리에 삽입 (?id=1 OR 1=1 이면 전체 삭제)
    $sql = "DELETE FROM users WHERE id = $id";
    if ($conn->query($sql)) {
        if ($id == $_SESSION['user_id']) {
            $_SESSION = [];
            session_destroy();
        }
        header("Location: /index.php");
        exit;
    }
    die("탈퇴 처리에 실패했습니다.");
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="narrow" style="margin:0 auto">
  <div class="page-head"><span class="eyebrow">Account</span><h2>회원 탈퇴</h2></div>
  <div class="card">
    <p>정말 탈퇴하시겠어요? 작성한 문장은 남습니다.</p>
    <form method="post" action="delete_account.php?id=<?php echo $id; ?>">
      <button class="btn btn-danger full" type="submit">탈퇴하기</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

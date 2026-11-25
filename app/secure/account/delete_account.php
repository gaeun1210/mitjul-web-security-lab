<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// [보완 3] IDOR 차단: 세션의 본인 id 만 삭제 대상으로
$id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // [보완 4]

    // [보완 1] Prepared Statement (i = 정수 바인딩)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION = [];
        session_destroy();
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
    <form method="post" action="delete_account.php">
      <?php echo csrf_field(); ?>
      <button class="btn btn-danger full" type="submit">탈퇴하기</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

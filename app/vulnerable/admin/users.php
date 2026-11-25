<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../includes/header.php";

// 관리 화면에는 비밀번호를 보여주지 않습니다.
// (그러나 취약점 1의 SQLi/덤프로는 password 컬럼까지 유출됩니다 — README 참고)
$users = $conn->query("SELECT id, username, name, email, role, created_at FROM users ORDER BY id");
?>
<div class="page-head"><span class="eyebrow">Admin</span><h2>회원 목록</h2></div>
<div class="card">
  <table class="data">
    <tr><th>ID</th><th>아이디</th><th>이름</th><th>이메일</th><th>권한</th><th>가입일</th></tr>
    <?php while ($u = $users->fetch_assoc()): ?>
      <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo $u['username']; ?></td>
        <td><?php echo $u['name']; ?></td>
        <td><?php echo $u['email']; ?></td>
        <td><?php echo $u['role']; ?></td>
        <td><?php echo $u['created_at']; ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../includes/header.php";

$users = $conn->query("SELECT id, username, name, email, role, created_at FROM users ORDER BY id");
$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<div class="page-head"><span class="eyebrow">Admin</span><h2>회원 목록</h2></div>
<div class="card">
  <table class="data">
    <tr><th>ID</th><th>아이디</th><th>이름</th><th>이메일</th><th>권한</th><th>가입일</th></tr>
    <?php while ($u = $users->fetch_assoc()): ?>
      <tr>
        <td><?php echo (int)$u['id']; ?></td>
        <td><?php echo $h($u['username']); ?></td>
        <td><?php echo $h($u['name']); ?></td>
        <td><?php echo $h($u['email']); ?></td>
        <td><?php echo $h($u['role']); ?></td>
        <td><?php echo $h($u['created_at']); ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

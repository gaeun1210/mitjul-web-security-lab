<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../includes/header.php";

$user_count = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$post_count = $conn->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'];
$file_count = $conn->query("SELECT COUNT(*) c FROM posts WHERE image_stored IS NOT NULL")->fetch_assoc()['c'];
?>
<div class="page-head"><span class="eyebrow">Admin</span><h2>관리 홈</h2>
<p>밑줄 서비스 운영 현황을 관리합니다.</p></div>

<div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat"><div class="l">전체 회원</div><div class="n"><?php echo (int)$user_count; ?></div></div>
  <div class="stat"><div class="l">등록된 문장</div><div class="n"><?php echo (int)$post_count; ?></div></div>
  <div class="stat"><div class="l">첨부파일</div><div class="n"><?php echo (int)$file_count; ?></div></div>
</div>

<div class="toolbar"><a class="btn btn-outline" href="users.php">회원 목록 →</a></div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

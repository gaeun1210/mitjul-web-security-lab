<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$uid = $_SESSION['user_id'];

// 세션 user_id 를 쿼리에 사용 (취약 버전: 문자열 결합 스타일 유지)
$me = $conn->query("SELECT username, name FROM users WHERE id = $uid")->fetch_assoc();

$agg = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(views),0) v, COUNT(DISTINCT category) g FROM posts WHERE user_id = $uid")->fetch_assoc();
$cnt = $agg['c']; $vsum = $agg['v']; $cats = $agg['g'];

$posts = $conn->query("
    SELECT id, title, category, book_title, views
    FROM posts WHERE user_id = $uid ORDER BY id DESC
");

$nm = $me['name'] ?? '밑';
$initial = preg_match('/./u', $nm, $m) ? $m[0] : '밑';
require_once __DIR__ . "/../includes/header.php";
?>
<div class="page-head"><span class="eyebrow">My Archive</span><h2>나의 기록</h2></div>

<div class="profile">
  <div class="ava"><?php echo $initial; ?></div>
  <div class="who">
    <!-- [취약 2] 이름을 이스케이프 없이 출력 -->
    <div class="name"><?php echo $me['name']; ?></div>
    <div class="bio">좋은 문장을 모으는 중 · @<?php echo $me['username']; ?></div>
  </div>
  <div class="stats">
    <div><div class="n"><?php echo (int)$cnt; ?></div><div class="l">내 문장</div></div>
    <div><div class="n"><?php echo (int)$vsum; ?></div><div class="l">총 조회</div></div>
    <div><div class="n"><?php echo (int)$cats; ?></div><div class="l">카테고리</div></div>
  </div>
</div>

<div class="tabs"><a href="#" class="on">내 문장</a></div>

<div class="scraps">
<?php if ($posts && $posts->num_rows > 0): ?>
  <?php while ($p = $posts->fetch_assoc()): ?>
    <a class="scrap" href="/board/view.php?id=<?php echo $p['id']; ?>" style="text-decoration:none">
      <div class="thumb"></div>
      <div class="body">
        <span class="cat"><?php echo $p['category']; ?></span>
        <p class="q"><?php echo $p['title']; ?></p>
        <div class="meta"><span>『<?php echo $p['book_title']; ?>』</span><span>조회 <?php echo (int)$p['views']; ?></span></div>
      </div>
    </a>
  <?php endwhile; ?>
<?php else: ?>
  <p class="muted">아직 남긴 문장이 없어요. <a href="/board/write.php">첫 문장 남기기 →</a></p>
<?php endif; ?>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

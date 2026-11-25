<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$uid = (int)$_SESSION['user_id'];

// [보완 1] Prepared Statement
$stmt = $conn->prepare("SELECT username, name FROM users WHERE id = ?");
$stmt->bind_param("i", $uid); $stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(*) c, COALESCE(SUM(views),0) v, COUNT(DISTINCT category) g FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $uid); $stmt->execute();
$agg = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT id, title, category, book_title, views FROM posts WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $uid); $stmt->execute();
$posts = $stmt->get_result();

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$nm = $me['name'] ?? '밑';
$initial = preg_match('/./u', $nm, $m) ? $m[0] : '밑';
require_once __DIR__ . "/../includes/header.php";
?>
<div class="page-head"><span class="eyebrow">My Archive</span><h2>나의 기록</h2></div>

<div class="profile">
  <div class="ava"><?php echo $h($initial); ?></div>
  <div class="who">
    <!-- [보완 2] 출력 이스케이프 -->
    <div class="name"><?php echo $h($me['name']); ?></div>
    <div class="bio">좋은 문장을 모으는 중 · @<?php echo $h($me['username']); ?></div>
  </div>
  <div class="stats">
    <div><div class="n"><?php echo (int)$agg['c']; ?></div><div class="l">내 문장</div></div>
    <div><div class="n"><?php echo (int)$agg['v']; ?></div><div class="l">총 조회</div></div>
    <div><div class="n"><?php echo (int)$agg['g']; ?></div><div class="l">카테고리</div></div>
  </div>
</div>

<div class="tabs"><a href="#" class="on">내 문장</a></div>

<div class="scraps">
<?php if ($posts && $posts->num_rows > 0): ?>
  <?php while ($p = $posts->fetch_assoc()): ?>
    <a class="scrap" href="/board/view.php?id=<?php echo (int)$p['id']; ?>" style="text-decoration:none">
      <div class="thumb"></div>
      <div class="body">
        <span class="cat"><?php echo $h($p['category']); ?></span>
        <p class="q"><?php echo $h($p['title']); ?></p>
        <div class="meta"><span>『<?php echo $h($p['book_title']); ?>』</span><span>조회 <?php echo (int)$p['views']; ?></span></div>
      </div>
    </a>
  <?php endwhile; ?>
<?php else: ?>
  <p class="muted">아직 남긴 문장이 없어요. <a href="/board/write.php">첫 문장 남기기 →</a></p>
<?php endif; ?>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

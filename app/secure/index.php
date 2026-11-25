<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/auth.php";

$recent = $conn->query("
    SELECT posts.id, posts.title, posts.category, posts.book_title, posts.book_author, users.name
    FROM posts JOIN users ON posts.user_id = users.id
    ORDER BY posts.id DESC LIMIT 3
");

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . "/includes/header.php";
?>
<div class="mast">
  <span>Vol. 01 — Collecting Words</span>
  <span><?php echo strtoupper(date('M. Y')); ?></span>
</div>

<section class="home-hero" aria-label="밑줄 소개">
  <div class="hero-intro">
    <h1 class="hero-title">읽다가,<br>마음이 <u>멈춘 곳.</u></h1>
    <p class="hero-sub">책 속에서 발견한, 오래 간직하고 싶은 문장을 수집하고 기록하는 공간.</p>
    <a class="btn btn-primary" href="<?php echo isLoggedIn() ? '/board/write.php' : '/account/login.php'; ?>">+ 문장 남기기</a>
  </div>
  <div class="hero-feature">
    <div class="hero-photo"><span class="ph-note">책 사진 자리 — 업로드 이미지로 교체 가능</span></div>
    <figure class="hero-quote">
      <blockquote class="q">“어떤 문장은 읽는 순간보다 읽고 난 뒤에 더 오래 남는다.”</blockquote>
      <figcaption class="meta">— 오늘의 문장</figcaption>
    </figure>
  </div>
</section>

<div class="section-label">
  <div><span class="lead">01 / The Scraps</span><h3>수집된 문장들</h3></div>
  <a class="view" href="/board/list.php">View all →</a>
</div>

<div class="tabs">
  <a href="/board/list.php" class="on">전체</a>
  <a href="/board/list.php?category=소설">소설</a>
  <a href="/board/list.php?category=에세이">에세이</a>
  <a href="/board/list.php?category=시">시</a>
  <a href="/board/list.php?category=인문">인문</a>
</div>

<div class="scraps">
<?php if ($recent && $recent->num_rows > 0): ?>
  <?php while ($r = $recent->fetch_assoc()): ?>
    <a class="scrap" href="/board/view.php?id=<?php echo (int)$r['id']; ?>" style="text-decoration:none">
      <div class="thumb"></div>
      <div class="body">
        <span class="cat"><?php echo $h($r['category']); ?></span>
        <!-- [보완 2] 출력 이스케이프 -->
        <p class="q"><?php echo $h($r['title']); ?></p>
        <div class="meta"><span>『<?php echo $h($r['book_title']); ?>』</span><span>by. <?php echo $h($r['name']); ?></span></div>
      </div>
    </a>
  <?php endwhile; ?>
<?php else: ?>
  <p class="muted">아직 문장이 없어요.</p>
<?php endif; ?>
</div>
<?php require_once __DIR__ . "/includes/footer.php"; ?>

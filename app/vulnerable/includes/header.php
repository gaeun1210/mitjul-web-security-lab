<?php
if (session_status() === PHP_SESSION_NONE) { session_name('MITJUL_VULN_SID'); session_start(); }
if (!isset($APP_MODE)) { $APP_MODE = getenv('APP_MODE') ?: 'vulnerable'; }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>밑줄 · 책 속 문장을 모으다</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
<link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="inner">
    <a href="/index.php" class="brand">
      <span class="bk brand-mark">밑줄</span>
      <span class="en">mitjul</span>
      <span class="tag">The Little Archive</span>
    </a>
    <nav class="nav">
      <a href="/board/list.php">문장 탐색</a>
      <a href="/board/list.php">컬렉션</a>
      <?php if (isLoggedIn()): ?>
        <a href="/board/write.php">문장 남기기</a>
        <a href="/mypage/index.php">나의 기록</a>
        <?php if (isAdmin()): ?><a href="/admin/index.php">관리</a><?php endif; ?>
      <?php else: ?>
        <a href="/account/login.php">로그인</a>
        <a href="/account/register.php">가입</a>
      <?php endif; ?>
      <span class="nav-icons">
        <a href="/board/list.php" aria-label="검색"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg></a>
        <a href="#" aria-label="알림"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></a>
        <a href="<?php echo isLoggedIn() ? '/mypage/index.php' : '/account/login.php'; ?>" aria-label="내 계정"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg></a>
      </span>
      <?php if (isLoggedIn()): ?>
        <a href="/account/logout.php" class="muted" style="font-size:13px"><?php echo $_SESSION['name']; ?>님 · 로그아웃</a>
      <?php endif; ?>
      <span class="mode-pill <?php echo $APP_MODE==='secure'?'mode-secure':'mode-vuln'; ?>"><?php echo $APP_MODE==='secure'?'SECURE':'VULNERABLE'; ?></span>
    </nav>
  </div>
</header>
<main class="container">

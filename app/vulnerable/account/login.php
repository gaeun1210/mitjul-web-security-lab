<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
$error      = isset($_GET['error']);
$registered = isset($_GET['registered']);
require_once __DIR__ . "/../includes/header.php";
?>
<div style="max-width:880px;margin:0 auto">
  <div class="card" style="display:grid;grid-template-columns:1fr 1fr;gap:0;padding:0;overflow:hidden">
    <div style="background:linear-gradient(160deg,#efe7d8,#e2d6c2);padding:44px 40px;display:flex;flex-direction:column">
      <span class="brand-mark login-brand" style="font-family:var(--serif);font-size:32px;font-weight:800">밑줄</span>
      <p style="font-family:var(--serif);color:var(--ink-soft);margin:10px 0 0">문장을 모으는 사람들.</p>
      <span style="margin-top:auto;color:var(--ink-faint);font-size:12px">책·꽃 사진 자리 — 이미지로 교체 가능</span>
    </div>
    <div style="padding:40px">
      <div class="eyebrow">Account</div>
      <h2 style="font-family:var(--serif);font-size:25px;margin:6px 0 20px">로그인</h2>
      <?php if ($registered): ?><div class="alert alert-ok">가입이 완료됐어요. 로그인해 주세요.</div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">아이디 또는 비밀번호가 올바르지 않습니다.</div><?php endif; ?>
      <form method="post" action="login_process.php">
        <div class="field"><label>아이디</label><input name="username" required></div>
        <div class="field"><label>비밀번호</label><input type="password" name="password" required></div>
        <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--ink-soft);margin-bottom:16px"><input type="checkbox" style="width:auto"> 로그인 상태 유지</label>
        <button class="btn btn-primary full" type="submit">로그인</button>
      </form>
      <p class="center muted" style="margin-top:16px;font-size:13px">
        <a href="register.php">회원가입</a> &nbsp;·&nbsp; <a href="reset_password.php">비밀번호 찾기</a>
      </p>
    </div>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

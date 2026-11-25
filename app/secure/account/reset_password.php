<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

/*
 * [보완 6] 안전한 비밀번호 재설정 — 2단계 본인 확인
 *  1단계: 아이디+이메일이 맞으면 1회용 인증코드를 발급(해시로 저장, 10분 만료)
 *  2단계: 그 코드를 입력해 검증된 경우에만 새 비밀번호로 변경
 *  - 코드는 평문이 아니라 password_hash() 해시로 DB에 저장
 *  - 만료시간은 MySQL NOW() 기준으로 계산/비교 (PHP·DB 시간대 불일치 방지)
 *  - 시도 횟수 제한 / 계정 존재 여부 비노출 / 변경 후 세션 무효화
 *
 *  [보완 10] 인증코드 화면 노출 제어
 *   이 데모는 메일(SMTP) 발송을 구현하지 않았습니다. 코드를 화면에 보여 주면
 *   '아이디+이메일만 알면 누구나 비밀번호를 바꿀 수 있는' 구조가 되므로,
 *   화면 표시는 환경변수 RESET_DEV_MODE=true 일 때(로컬 실습)만 켭니다.
 *   기본값은 false → 이 경우 재설정 기능 자체를 비활성화하고 안내만 보여 줍니다.
 */
define('RESET_DEV_MODE', filter_var(getenv('RESET_DEV_MODE') ?: 'false', FILTER_VALIDATE_BOOLEAN));
const RESET_MAX_ATTEMPTS = 5;

$message = "";
$error   = "";
$devCode = "";
$disabled = !RESET_DEV_MODE;   // 메일 발송 수단이 없으면 기능을 끈다

$stage = (isset($_SESSION['reset_stage']) && $_SESSION['reset_stage'] === 2) ? 2 : 1;

if (!$disabled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // "처음부터 다시"
    if (isset($_POST['restart'])) {
        unset($_SESSION['reset_stage'], $_SESSION['reset_user'], $_SESSION['reset_attempts']);
        $stage = 1;
    }

    // 1단계: 코드 발급
    elseif (isset($_POST['username'], $_POST['email'])) {
        $username = $_POST['username'];
        $email    = $_POST['email'];

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $code     = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
            $codeHash = password_hash($code, PASSWORD_DEFAULT);
            // 만료시간을 NOW() 로 계산해 저장(아래 검증도 NOW() 로 비교)
            $stmt = $conn->prepare(
                "UPDATE users SET reset_code = ?, reset_expires = NOW() + INTERVAL 10 MINUTE WHERE id = ?"
            );
            $stmt->bind_param("si", $codeHash, $row['id']);
            $stmt->execute();
            if (RESET_DEV_MODE) { $devCode = $code; } // 데모: 화면에 표시
        }

        // 계정 존재 여부와 무관하게 동일한 안내 + 항상 2단계로 (존재 비노출)
        $_SESSION['reset_stage']    = 2;
        $_SESSION['reset_user']     = $username;
        $_SESSION['reset_attempts'] = 0;
        $stage   = 2;
        $message = "입력하신 정보가 맞으면 인증코드를 발급했습니다. 아래에 코드를 입력하세요.";
    }

    // 2단계: 코드 검증 후 변경
    elseif (isset($_POST['code'], $_POST['new_password'])) {
        $username = $_SESSION['reset_user'] ?? '';
        $code     = $_POST['code'];
        $newpw    = $_POST['new_password'];

        $_SESSION['reset_attempts'] = ($_SESSION['reset_attempts'] ?? 0) + 1;

        if ($_SESSION['reset_attempts'] > RESET_MAX_ATTEMPTS) {
            unset($_SESSION['reset_stage'], $_SESSION['reset_user'], $_SESSION['reset_attempts']);
            $error = "시도 횟수를 초과했습니다. 처음부터 다시 시도해 주세요.";
            $stage = 1;
        } elseif (strlen($newpw) < 8) {
            $error = "비밀번호는 8자 이상이어야 합니다.";
        } else {
            $stmt = $conn->prepare(
                "SELECT id, reset_code, (reset_expires > NOW()) AS not_expired
                 FROM users WHERE username = ?"
            );
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();

            if ($u && $u['reset_code'] !== null && $u['not_expired']
                   && password_verify($code, $u['reset_code'])) {
                $hashed = password_hash($newpw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "UPDATE users SET password = ?, reset_code = NULL, reset_expires = NULL WHERE id = ?"
                );
                $stmt->bind_param("si", $hashed, $u['id']);
                $stmt->execute();

                // 변경 후 재설정 세션 정리 + 세션 ID 재발급(기존 세션 무효화)
                unset($_SESSION['reset_stage'], $_SESSION['reset_user'], $_SESSION['reset_attempts']);
                session_regenerate_id(true);
                header("Location: login.php?reset=1");
                exit;
            }
            $error = "인증코드가 올바르지 않거나 만료되었습니다.";
        }
    }
}

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . "/../includes/header.php";
?>
<div class="narrow" style="margin:0 auto">
  <div class="page-head"><span class="eyebrow">Account</span><h2>비밀번호 재설정</h2>
  <?php if ($disabled): ?>
  <p>이 데모에서는 비밀번호 재설정을 사용할 수 없습니다.</p></div>
  <div class="card">
    <p>비밀번호 재설정은 본인 메일로 인증코드를 보내야 안전하게 동작하는데, 이 실습 환경에는 메일 발송 기능이 없어 <strong>기능을 꺼 두었습니다.</strong></p>
    <p class="muted" style="font-size:13px">로컬 실습에서 동작을 확인하려면 <code>.env</code> 에 <code>RESET_DEV_MODE=true</code> 를 넣고 다시 실행하세요. (인증코드가 화면에 표시됩니다)</p>
    <p class="center muted" style="margin-top:14px"><a href="login.php">로그인으로</a></p>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; exit; ?>
  <?php endif; ?>
  <p><?php echo $stage === 1 ? "아이디와 이메일을 입력하면 인증코드를 보내드려요." : "받으신 인증코드와 새 비밀번호를 입력하세요."; ?></p></div>

  <?php if ($message): ?><div class="alert alert-ok"><?php echo $h($message); ?></div><?php endif; ?>
  <?php if ($devCode !== ""): ?>
    <div class="alert alert-ok">(테스트용) 인증코드: <strong><?php echo $h($devCode); ?></strong> — 10분 내 유효</div>
  <?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?php echo $h($error); ?></div><?php endif; ?>

  <div class="card">
    <?php if ($stage === 1): ?>
      <form method="post">
        <?php echo csrf_field(); ?>
        <div class="field"><label>아이디</label><input name="username" required></div>
        <div class="field"><label>이메일</label><input type="email" name="email" required></div>
        <button class="btn btn-primary full" type="submit">인증코드 받기</button>
      </form>
    <?php else: ?>
      <form method="post">
        <?php echo csrf_field(); ?>
        <div class="field"><label>인증코드</label><input name="code" placeholder="6자리 코드" required></div>
        <div class="field"><label>새 비밀번호 <span class="muted">(8자 이상)</span></label><input type="password" name="new_password" required></div>
        <button class="btn btn-primary full" type="submit">비밀번호 변경</button>
      </form>
      <form method="post" style="margin-top:10px">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="restart" value="1">
        <button class="btn btn-outline full" type="submit">처음부터 다시</button>
      </form>
    <?php endif; ?>
    <p class="center muted" style="margin-top:14px"><a href="login.php">로그인으로</a></p>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

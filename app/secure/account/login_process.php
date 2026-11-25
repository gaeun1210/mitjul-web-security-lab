<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

// [보완 4] 로그인 CSRF 방지 — POST + 우리 로그인 화면에서 발급한 토큰이 있을 때만 처리
//  (다른 사이트가 피해자 브라우저를 공격자 계정으로 몰래 로그인시키는 'Login CSRF' 차단)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}
csrf_check();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// [보완 1] Prepared Statement — 아이디로만 조회
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// [보완 5] 해시 검증 — 입력 원문이 저장된 해시에 맞는지만 확인
if ($user && password_verify($password, $user['password'])) {
    // [보완 7] 로그인 성공 직후 세션 ID 재발급 → 세션 고정 방지
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name']     = $user['name'];
    $_SESSION['role']     = $user['role'];
    header("Location: /index.php");
    exit;
}

header("Location: login.php?error=1");
exit;

<?php
/* 세션/권한 헬퍼 (보완 버전)
 * [보완 7] 세션 쿠키에 HttpOnly / SameSite 적용 → XSS 로도 쿠키를 못 읽게
 * [보완 4] CSRF 토큰을 세션에 1회 발급 → 상태변경 요청마다 검증
 */
require_once __DIR__ . '/errors.php';   // [보완 8] db.php 를 안 거치는 페이지(로그아웃 등)도 오류 숨김 적용

if (session_status() === PHP_SESSION_NONE) {
    // [보완] 세션 고정 방지: 서버가 발급하지 않은 세션 ID 는 거부
    ini_set('session.use_strict_mode', '1');
    // [보완] 쿠키는 포트를 구분하지 않아 localhost:8001(취약) 과 세션이 섞일 수 있음 → 이름 분리
    session_name('MITJUL_SECURE_SID');
    session_set_cookie_params([
        'httponly' => true,    // JS(document.cookie)로 세션 쿠키 읽기 불가
        'secure'   => false,   // HTTPS 서버라면 true 로 (이 데모는 HTTP)
        'samesite' => 'Lax',   // 다른 사이트에서 날아온 요청엔 쿠키 미전송
    ]);
    session_start();
}

// [보완] HTTP 보안 응답 헤더 — XSS·클릭재킹·MIME 스니핑을 브라우저 단에서 한 겹 더 차단
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: no-referrer");
    // 데모라 인라인 style/script 를 허용(unsafe-inline). 실서비스면 nonce 기반 CSP 권장.
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'none'");
}

// CSRF 토큰 1회 발급
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/* 상태를 바꾸는 POST 요청 처리 맨 앞에서 호출 */
function csrf_check() {
    $ok = hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
    if (!$ok) {
        http_response_code(403);
        die("잘못된 요청입니다. (CSRF 토큰 불일치)");
    }
}

/* [보완 4] 링크(GET)로 처리하는 로그아웃용 토큰.
 * 다른 사이트가 <img src=".../logout.php"> 같은 방식으로 몰래 로그아웃시키지 못하게,
 * 세션 토큰을 아는 우리 화면의 링크에서만 로그아웃이 되도록 한다. */
function logout_url() {
    return '/account/logout.php?token=' . urlencode($_SESSION['csrf_token']);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /account/login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        die("관리자 권한이 필요합니다.");
    }
}

/* 공통: 요청자가 이 글의 작성자이거나 관리자인지 (IDOR 방지용 서버측 재검증) */
function canManagePost($post) {
    if (isAdmin()) return true;
    return isLoggedIn() && isset($post['user_id']) && $_SESSION['user_id'] == $post['user_id'];
}

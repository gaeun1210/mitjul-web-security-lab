<?php
/* 세션/권한 헬퍼 (취약 버전)
 * - 세션 쿠키 보안 옵션 없음 (HttpOnly 미설정)
 * - CSRF 토큰 발급 없음
 * - 로그인 후 session_regenerate_id 없음
 */
if (session_status() === PHP_SESSION_NONE) {
    // 취약/보완 세션이 섞이지 않게 쿠키 이름만 분리 (쿠키는 포트를 구분하지 않음).
    // ※ 취약 버전의 세션 고정/HttpOnly 미설정 등 취약점은 그대로 둡니다.
    session_name('MITJUL_VULN_SID');
    session_start();
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

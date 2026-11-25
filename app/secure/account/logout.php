<?php
require_once __DIR__ . "/../includes/auth.php";

// [보완 4] 로그아웃 CSRF 방지 — 우리 화면의 로그아웃 링크(토큰 포함)로 온 요청만 처리
// 토큰이 없거나 틀리면 로그아웃하지 않고 홈으로 돌려보냄
if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_GET['token'] ?? ''))) {
    header("Location: /index.php");
    exit;
}

$_SESSION = [];
session_destroy();
header("Location: /index.php");
exit;

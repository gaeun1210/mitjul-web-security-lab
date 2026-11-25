<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// [취약 1] 로그인 우회(SQL Injection): 입력값을 그대로 쿼리에 이어붙임
// [취약 5] 평문 비밀번호: 해시 없이 문자열 그대로 비교
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    // [취약 7] 로그인 성공 후 session_regenerate_id() 없음 → 세션 고정 여지
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name']     = $user['name'];
    $_SESSION['role']     = $user['role'];
    header("Location: /index.php");
    exit;
}

header("Location: login.php?error=1");
exit;

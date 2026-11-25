<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// [취약 4] CSRF 토큰 검증 없음
$id = $_POST['id'] ?? ($_GET['id'] ?? 0);

// [취약 1] id 를 그대로 조회
$post = $conn->query("SELECT * FROM posts WHERE id = $id")->fetch_assoc();
if (!$post) { die("문장을 찾을 수 없습니다."); }

// [취약 3] IDOR: 작성자 본인/관리자인지 확인하지 않고 바로 삭제
$conn->query("DELETE FROM posts WHERE id = $id");

header("Location: list.php");
exit;

<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();
csrf_check(); // [보완 4]

$id = (int)($_POST['id'] ?? 0);

// [보완 1] Prepared Statement 로 조회
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { die("문장을 찾을 수 없습니다."); }

// [보완 3] IDOR 차단: 서버에서 작성자/관리자 여부 재검증
if (!canManagePost($post)) {
    http_response_code(403);
    die("삭제 권한이 없습니다.");
}

$stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list.php");
exit;

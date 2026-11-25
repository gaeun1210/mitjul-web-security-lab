<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

// [보완 1] Prepared Statement 로 조회 (user_id 포함 → 소유자 검사에 사용)
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { die("문장을 찾을 수 없습니다."); }

// [보완 3] IDOR 차단: 서버에서 작성자/관리자 여부 재검증
if (!canManagePost($post)) {
    http_response_code(403);
    die("수정 권한이 없습니다.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    csrf_check(); // [보완 4]

    $title       = $_POST['title'];
    $content     = $_POST['content'];
    $book_title  = $_POST['book_title'];
    $book_author = $_POST['book_author'];

    // [보완 1] Prepared Statement 로 저장
    $stmt = $conn->prepare("UPDATE posts SET title=?, content=?, book_title=?, book_author=? WHERE id=?");
    $stmt->bind_param("ssssi", $title, $content, $book_title, $book_author, $id);
    $stmt->execute();
    header("Location: view.php?id=$id");
    exit;
}

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . "/../includes/header.php";
?>
<div class="page-head"><span class="eyebrow">Edit</span><h2>문장 수정</h2></div>
<div class="card">
  <form method="post" action="edit.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
    <div class="field"><label>문장</label><input name="title" value="<?php echo $h($post['title']); ?>" required></div>
    <div class="field"><label>내 생각</label><textarea name="content" required><?php echo $h($post['content']); ?></textarea></div>
    <div class="field"><label>책 제목</label><input name="book_title" value="<?php echo $h($post['book_title']); ?>" required></div>
    <div class="field"><label>지은이</label><input name="book_author" value="<?php echo $h($post['book_author']); ?>" required></div>
    <button class="btn btn-primary" type="submit">저장</button>
  </form>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

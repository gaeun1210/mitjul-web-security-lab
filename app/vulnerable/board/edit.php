<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$id = $_GET['id'] ?? ($_POST['id'] ?? 0);

// [취약 1] id 를 그대로 조회
$post = $conn->query("SELECT * FROM posts WHERE id = $id")->fetch_assoc();
if (!$post) { die("문장을 찾을 수 없습니다."); }

// [취약 3] IDOR: 작성자 본인/관리자인지 확인하지 않음 (로그인만 돼 있으면 남의 글도 수정)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    // [취약 4] CSRF 토큰 검증 없음
    $title       = $_POST['title'];
    $content     = $_POST['content'];
    $book_title  = $_POST['book_title'];
    $book_author = $_POST['book_author'];

    // [취약 1] 입력값을 그대로 UPDATE
    $conn->query("UPDATE posts SET title='$title', content='$content',
                  book_title='$book_title', book_author='$book_author' WHERE id=$id");
    header("Location: view.php?id=$id");
    exit;
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="page-head"><span class="eyebrow">Edit</span><h2>문장 수정</h2></div>
<div class="card">
  <form method="post" action="edit.php">
    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
    <div class="field"><label>문장</label><input name="title" value="<?php echo $post['title']; ?>" required></div>
    <div class="field"><label>내 생각</label><textarea name="content" required><?php echo $post['content']; ?></textarea></div>
    <div class="field"><label>책 제목</label><input name="book_title" value="<?php echo $post['book_title']; ?>" required></div>
    <div class="field"><label>지은이</label><input name="book_author" value="<?php echo $post['book_author']; ?>" required></div>
    <button class="btn btn-primary" type="submit">저장</button>
  </form>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

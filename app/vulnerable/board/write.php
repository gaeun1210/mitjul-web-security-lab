<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [취약 4] CSRF 토큰 검증 없음
    $title       = $_POST['title'] ?? '';
    $content     = $_POST['content'] ?? '';
    $book_title  = $_POST['book_title'] ?? '';
    $book_author = $_POST['book_author'] ?? '';
    $category    = $_POST['category'] ?? '에세이';
    $user_id     = $_SESSION['user_id'];

    // [취약 1] 입력값을 그대로 INSERT
    $conn->query("INSERT INTO posts (user_id, title, content, book_title, book_author, category)
                  VALUES ($user_id, '$title', '$content', '$book_title', '$book_author', '$category')");
    $post_id = $conn->insert_id;

    // [취약 6] 파일 업로드: 확장자/내용 검사 없음, 웹에서 접근 가능한 폴더에
    //          '예측 가능한' 이름으로 그대로 저장 → 웹쉘(.php) 업로드 시 RCE
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/../uploads/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

        $original = basename($_FILES['image']['name']);
        $stored   = $post_id . "_" . $original;         // 예측 가능한 이름
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $stored);

        // [취약 1] 파일명(original)을 그대로 쿼리에 삽입 → 또 다른 SQLi 지점
        $conn->query("UPDATE posts SET image_original='$original', image_stored='$stored' WHERE id=$post_id");
    }

    header("Location: view.php?id=$post_id");
    exit;
}

require_once __DIR__ . "/../includes/header.php";
?>
<div class="page-head"><span class="eyebrow">Write</span><h2>문장 남기기</h2>
<p>읽다가 마음이 멈춘 문장을 기록해 보세요.</p></div>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <div class="field"><label>밑줄 친 문장</label><input name="title" required placeholder="어떤 책의 문장인지 적어주세요."></div>
    <div class="field"><label>책 제목</label><input name="book_title" required></div>
    <div class="field"><label>지은이</label><input name="book_author" required></div>
    <div class="field"><label>카테고리</label>
      <select name="category">
        <option value="소설">소설</option>
        <option value="에세이" selected>에세이</option>
        <option value="시">시</option>
        <option value="인문">인문</option>
      </select>
    </div>
    <div class="field"><label>이 문장을 기록한 이유 · 감상</label><textarea name="content" required placeholder="남겨두고 싶은 생각을 적어보세요."></textarea></div>
    <div class="field"><label>필사 이미지 <span class="muted">(선택)</span></label><input type="file" name="image"></div>
    <button class="btn btn-primary" type="submit">기록하기</button>
  </form>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // [보완 4]

    $title       = $_POST['title'] ?? '';
    $content     = $_POST['content'] ?? '';
    $book_title  = $_POST['book_title'] ?? '';
    $book_author = $_POST['book_author'] ?? '';
    $category    = $_POST['category'] ?? '에세이';
    $user_id     = $_SESSION['user_id'];

    // 카테고리는 허용 목록으로만
    if (!in_array($category, ['소설','에세이','시','인문'], true)) { $category = '에세이'; }

    // [보완 1] Prepared Statement 로 게시글 저장
    $stmt = $conn->prepare(
        "INSERT INTO posts (user_id, title, content, book_title, book_author, category) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssss", $user_id, $title, $content, $book_title, $book_author, $category);
    $stmt->execute();
    $post_id = $conn->insert_id;

    // [보완 6] 파일 업로드 안전 처리
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/../uploads/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

        $original = basename($_FILES['image']['name']);
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($ext, $allowed_ext, true) || !in_array($mime, $allowed_mime, true)) {
            $error = "이미지 파일(jpg, png, gif, webp)만 올릴 수 있어요.";
        } else {
            $stored = bin2hex(random_bytes(16)) . "." . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $stored)) {
                $stmt = $conn->prepare("UPDATE posts SET image_original=?, image_stored=? WHERE id=?");
                $stmt->bind_param("ssi", $original, $stored, $post_id);
                $stmt->execute();
            }
        }
    }

    if ($error === "") {
        header("Location: view.php?id=$post_id");
        exit;
    }
}

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . "/../includes/header.php";
?>
<div class="page-head"><span class="eyebrow">Write</span><h2>문장 남기기</h2>
<p>읽다가 마음이 멈춘 문장을 기록해 보세요.</p></div>
<?php if ($error): ?><div class="alert alert-error"><?php echo $h($error); ?> <a href="view.php?id=<?php echo (int)$post_id; ?>">글은 저장됐어요 →</a></div><?php endif; ?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
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
    <div class="field"><label>필사 이미지 <span class="muted">(선택, 이미지 파일만)</span></label><input type="file" name="image" accept="image/*"></div>
    <button class="btn btn-primary" type="submit">기록하기</button>
  </form>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

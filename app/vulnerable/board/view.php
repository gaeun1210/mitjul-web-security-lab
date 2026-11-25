<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$id = $_GET['id'] ?? 0;

// [취약 1] id 를 그대로 쿼리에 삽입 (조회수 증가 + 게시글 조회)
$conn->query("UPDATE posts SET views = views + 1 WHERE id = $id");

$post = $conn->query("
    SELECT posts.*, users.name, users.username, users.id AS writer_id
    FROM posts JOIN users ON posts.user_id = users.id
    WHERE posts.id = $id
")->fetch_assoc();

if (!$post) { die("문장을 찾을 수 없습니다."); }

// 버튼 노출 여부만 결정 (※ 실제 처리에는 검사 없음 → IDOR)
$can_manage = false;
if (isAdmin()) {
    $can_manage = true;
} elseif (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']) {
    $can_manage = true;
}

require_once __DIR__ . "/../includes/header.php";
?>
<p style="margin:0 0 18px"><a class="muted" href="list.php">← 문장 탐색</a></p>
<div class="card detail">
  <!-- [취약 2] 저장형 XSS: 제목/내용/글쓴이명을 이스케이프 없이 그대로 출력 -->
  <p class="big-quote"><?php echo $post['title']; ?></p>
  <div class="bookline">
    <span class="cat"><?php echo $post['category']; ?></span>
    <span>『<?php echo $post['book_title']; ?>』 · <?php echo $post['book_author']; ?></span>
    <span>· <?php echo $post['name']; ?></span>
    <span>· 조회 <?php echo $post['views']; ?></span>
  </div>
  <div class="body"><?php echo nl2br($post['content']); ?></div>

  <?php if (!empty($post['image_stored'])): ?>
    <img class="scan" src="/uploads/<?php echo $post['image_stored']; ?>" alt="필사 이미지">
  <?php endif; ?>

  <?php if ($can_manage): ?>
    <div class="actions">
      <a class="btn btn-outline btn-sm" href="edit.php?id=<?php echo $post['id']; ?>">수정</a>
      <form method="post" action="delete.php" onsubmit="return confirm('삭제할까요?');">
        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
        <button class="btn btn-danger btn-sm" type="submit">삭제</button>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

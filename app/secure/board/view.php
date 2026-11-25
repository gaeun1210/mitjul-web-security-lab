<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$id = (int)($_GET['id'] ?? 0); // 정수로 강제

// [보완 1] Prepared Statement
$stmt = $conn->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$stmt = $conn->prepare("
    SELECT posts.*, users.name, users.username
    FROM posts JOIN users ON posts.user_id = users.id
    WHERE posts.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { die("문장을 찾을 수 없습니다."); }

$can_manage = canManagePost($post);
$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . "/../includes/header.php";
?>
<p style="margin:0 0 18px"><a class="muted" href="list.php">← 문장 탐색</a></p>
<div class="card detail">
  <!-- [보완 2] 출력 이스케이프 -->
  <p class="big-quote"><?php echo $h($post['title']); ?></p>
  <div class="bookline">
    <span class="cat"><?php echo $h($post['category']); ?></span>
    <span>『<?php echo $h($post['book_title']); ?>』 · <?php echo $h($post['book_author']); ?></span>
    <span>· <?php echo $h($post['name']); ?></span>
    <span>· 조회 <?php echo (int)$post['views']; ?></span>
  </div>
  <div class="body"><?php echo nl2br($h($post['content'])); ?></div>

  <?php if (!empty($post['image_stored'])): ?>
    <img class="scan" src="/uploads/<?php echo rawurlencode($post['image_stored']); ?>" alt="필사 이미지">
  <?php endif; ?>

  <?php if ($can_manage): ?>
    <div class="actions">
      <a class="btn btn-outline btn-sm" href="edit.php?id=<?php echo (int)$post['id']; ?>">수정</a>
      <form method="post" action="delete.php" onsubmit="return confirm('삭제할까요?');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
        <button class="btn btn-danger btn-sm" type="submit">삭제</button>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

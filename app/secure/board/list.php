<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$keyword     = $_GET['keyword'] ?? '';
$search_type = $_GET['search_type'] ?? 'all';
$category    = $_GET['category'] ?? '';

// [보완 1] Prepared Statement — 검색어·카테고리를 값으로만 바인딩
$sql = "SELECT posts.id, posts.title, posts.category, posts.book_title, posts.book_author, users.name
        FROM posts JOIN users ON posts.user_id = users.id WHERE 1=1";
$params = [];
$types  = "";

if ($keyword !== '') {
    $like = "%{$keyword}%";
    if ($search_type === 'writer') {
        $sql .= " AND users.name LIKE ?";
        $params[] = $like; $types .= "s";
    } elseif ($search_type === 'title') {
        $sql .= " AND posts.title LIKE ?";
        $params[] = $like; $types .= "s";
    } else {
        $sql .= " AND (posts.title LIKE ? OR posts.content LIKE ?)";
        $params[] = $like; $params[] = $like; $types .= "ss";
    }
}
// 카테고리는 허용 목록(화이트리스트)으로만
$allowedCats = ['소설','에세이','시','인문'];
if (in_array($category, $allowedCats, true)) {
    $sql .= " AND posts.category = ?";
    $params[] = $category; $types .= "s";
}
$sql .= " ORDER BY posts.id DESC";

$stmt = $conn->prepare($sql);
if ($types !== "") { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$cats = ['' => '전체', '소설' => '소설', '에세이' => '에세이', '시' => '시', '인문' => '인문'];
$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . "/../includes/header.php";
?>
<div class="page-head">
  <span class="eyebrow">Explore</span>
  <h2>문장 탐색</h2>
  <p>다른 사람들은 어떤 문장에 밑줄을 그었을까요?</p>
</div>

<div class="tabs">
  <?php foreach ($cats as $key => $label): ?>
    <a href="list.php?category=<?php echo urlencode($key); ?>" class="<?php echo $category === $key ? 'on' : ''; ?>"><?php echo $label; ?></a>
  <?php endforeach; ?>
</div>

<form class="searchbar" method="get">
  <input type="hidden" name="category" value="<?php echo $h($category); ?>">
  <select name="search_type">
    <option value="all"    <?php if($search_type==='all')    echo 'selected'; ?>>제목+내용</option>
    <option value="title"  <?php if($search_type==='title')  echo 'selected'; ?>>제목</option>
    <option value="writer" <?php if($search_type==='writer') echo 'selected'; ?>>글쓴이</option>
  </select>
  <input name="keyword" value="<?php echo $h($keyword); ?>" placeholder="문장 속 단어로 검색">
  <button class="btn btn-outline" type="submit">검색</button>
</form>

<div class="scraps">
<?php if ($result && $result->num_rows > 0): ?>
  <?php while ($row = $result->fetch_assoc()): ?>
    <a class="scrap" href="view.php?id=<?php echo (int)$row['id']; ?>" style="text-decoration:none">
      <div class="thumb"></div>
      <div class="body">
        <span class="cat"><?php echo $h($row['category']); ?></span>
        <!-- [보완 2] 출력 이스케이프 -->
        <p class="q"><?php echo $h($row['title']); ?></p>
        <div class="meta"><span>『<?php echo $h($row['book_title']); ?>』</span><span>by. <?php echo $h($row['name']); ?></span></div>
      </div>
    </a>
  <?php endwhile; ?>
<?php else: ?>
  <p class="muted">아직 문장이 없어요.</p>
<?php endif; ?>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

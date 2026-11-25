<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$keyword     = $_GET['keyword'] ?? '';
$search_type = $_GET['search_type'] ?? 'all';
$category    = $_GET['category'] ?? '';

// [취약 1] 검색어를 그대로 WHERE 절에 이어붙임 → UNION 기반 정보 탈취 가능
$where = "1=1";
if ($keyword !== '') {
    if ($search_type === 'writer') {
        $where .= " AND users.name LIKE '%$keyword%'";
    } elseif ($search_type === 'title') {
        $where .= " AND posts.title LIKE '%$keyword%'";
    } else {
        $where .= " AND (posts.title LIKE '%$keyword%' OR posts.content LIKE '%$keyword%')";
    }
}
if ($category !== '') { $where .= " AND posts.category = '$category'"; }

// 목록은 6개 컬럼을 뿌립니다 (UNION 공격 시 열 개수 6개로 맞춰야 함)
$sql = "SELECT posts.id, posts.title, posts.category, posts.book_title, posts.book_author, users.name
        FROM posts JOIN users ON posts.user_id = users.id
        WHERE $where
        ORDER BY posts.id DESC";
$result = $conn->query($sql);

$cats = ['' => '전체', '소설' => '소설', '에세이' => '에세이', '시' => '시', '인문' => '인문'];
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
  <input type="hidden" name="category" value="<?php echo $category; ?>">
  <select name="search_type">
    <option value="all"    <?php if($search_type==='all')    echo 'selected'; ?>>제목+내용</option>
    <option value="title"  <?php if($search_type==='title')  echo 'selected'; ?>>제목</option>
    <option value="writer" <?php if($search_type==='writer') echo 'selected'; ?>>글쓴이</option>
  </select>
  <input name="keyword" value="<?php echo $keyword; ?>" placeholder="문장 속 단어로 검색">
  <button class="btn btn-outline" type="submit">검색</button>
</form>

<div class="scraps">
<?php if ($result && $result->num_rows > 0): ?>
  <?php while ($row = $result->fetch_assoc()): ?>
    <a class="scrap" href="view.php?id=<?php echo $row['id']; ?>" style="text-decoration:none">
      <div class="thumb"></div>
      <div class="body">
        <span class="cat"><?php echo $row['category']; ?></span>
        <!-- [취약 2] 저장형 XSS: 제목을 이스케이프 없이 출력 -->
        <p class="q"><?php echo $row['title']; ?></p>
        <div class="meta"><span>『<?php echo $row['book_title']; ?>』</span><span>by. <?php echo $row['name']; ?></span></div>
      </div>
    </a>
  <?php endwhile; ?>
<?php else: ?>
  <p class="muted">아직 문장이 없어요.</p>
<?php endif; ?>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

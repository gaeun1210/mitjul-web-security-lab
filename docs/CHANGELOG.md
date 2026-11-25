# CHANGELOG — v1 → v2

v2는 **취약점 6종(취약 버전의 의도적 취약점)은 그대로 두고**, 보완 버전과 공통 환경의
보안을 신입 수준에서 정직하게 강화한 버전입니다. 새로운 취약점 실습 항목은 추가하지 않았습니다.

> 원본(v1)은 `mitjul-vuln-lab.zip` 그대로 보존됩니다. v2는 별도 제공본입니다.

## 1. 환경 격리 (DB 계정 분리 · 비밀값 관리 · 외부 노출 차단)

| 파일 | 변경 내용 |
|---|---|
| `db/init.sql` | 공용 계정 `mituser`(ALL PRIVILEGES) 제거 → `mituser_vuln`·`mituser_secure` 로 분리, 각자 자기 DB에만 `SELECT/INSERT/UPDATE/DELETE`만 부여(최소 권한). `DROP/ALTER/GRANT` 없음 |
| `docker-compose.yml` | DB 접속 정보를 `.env` 환경변수로 분리. 앱 포트를 `127.0.0.1:8001/8080` 로만 바인딩(VMware 밖 노출 차단). DB 포트는 외부로 열지 않음 |
| `.env` / `.env.example` | (신규) 비밀값을 코드에서 분리. 실제 `.env`는 `.gitignore`로 커밋 제외 |
| `.gitignore` | `.env` 추가 |
| `app/*/includes/db.php` | getenv 기본값을 분리된 계정명으로 갱신 |

## 2. 보안 응답 헤더 (보완 버전)

| 파일 | 변경 내용 |
|---|---|
| `app/secure/includes/auth.php` | `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, 기본 `Content-Security-Policy` 추가. (데모라 인라인 style/script 허용 — 실서비스면 nonce 기반 CSP 권장) |

## 3. 세션 관리 강화 (보완 버전)

| 파일 | 변경 내용 |
|---|---|
| `app/secure/includes/auth.php` | 세션 쿠키 이름 `MITJUL_SECURE_SID` 로 분리(쿠키는 포트를 구분하지 않아 `:8001`과 섞일 수 있음), `session.use_strict_mode=1`(세션 고정 방지). 기존 HttpOnly·SameSite·로그인 시 `session_regenerate_id` 유지 |
| `app/vulnerable/includes/auth.php` | 쿠키 이름 `MITJUL_VULN_SID` 로만 분리(취약점 자체는 유지) |

## 4. 비밀번호 재설정 정직화 (보완 버전)

| 파일 | 변경 내용 |
|---|---|
| `app/secure/account/reset_password.php` | 아이디+이메일만으로 즉시 변경하던 로직 → **2단계**(코드 발급 → 코드 검증 후 변경). 코드는 `password_hash()` **해시로 저장**, `NOW()+10분` 만료, 시도 5회 제한, 계정 존재 비노출, 변경 후 `session_regenerate_id(true)`. 데모라 코드를 화면에 표시(dev 모드) |
| `db/init.sql` | 보완 스키마 `reset_code` 컬럼을 `VARCHAR(6)` → `VARCHAR(255)`(해시 저장용) |
| `app/secure/account/login.php` | `?reset=1` 변경 완료 안내 메시지 추가 |

## 5. 문서

| 파일 | 변경 내용 |
|---|---|
| `docs/SECURITY_REPORT.md` | (신규) 취약점 6종을 13항목 포맷으로 정리 |
| `docs/TEST_RESULTS.md` | (신규) 검증 결과 표 — 실제 수행분만 PASS, 미수행분은 NOT TESTED + VM 확인 절차 |
| `docs/CHANGELOG.md` | (신규) 본 문서 |
| `README.md` / `docs/REPRODUCE.md` | 용어·판정 기준 정확도 수정(저장형↔반사형 XSS, 해시≠암호화, 업로드 거부↔실행 차단, IDOR 판정 기준) |

## 적용 시 주의 — 스키마 변경 반영

`reset_code` 컬럼 크기가 바뀌었으므로, 기존 볼륨이 있으면 재시드가 필요합니다:

```bash
docker compose down -v      # DB 볼륨 삭제
docker compose up --build   # init.sql 재적용
```

운영 중 데이터를 지키며 바꾸려면(마이그레이션):

```sql
ALTER TABLE mitjul_secure.users MODIFY reset_code VARCHAR(255) NULL;
```

## 남아 있는(의도적으로 보류한) 항목

신입 포트폴리오 범위를 지키기 위해 아래는 **적용하지 않았고**, 추가 개선 항목으로만 기록합니다:
업로드 파일 웹루트 외부 저장·이미지 재인코딩, 전면 보안 로깅/대시보드, Mailpit 실제 메일,
자동화 테스트, Docker 네트워크 완전 분리, 로그아웃 GET→POST·로그인 CSRF 토큰. (→ 로그인·로그아웃 CSRF는 v4에서 반영)

---

# CHANGELOG — v2 → v3 (디자인 + 가벼운 기능)

> 취약점 6종의 취약/방어 로직은 그대로. 디자인은 CSS·레이아웃·마크업만, 기능은 카테고리·마이페이지 추가.

## 1. 디자인 리뉴얼 (시안 톤)
- `app/*/css/style.css` : 종이·매거진 무드로 전면 재작성(페이퍼/오트밀/그레이지/세이지/버건디, Pretendard+나눔명조).
- `app/*/includes/header.php`·`footer.php` : 브랜드(밑줄. mitjul. The Little Archive), 탐색/컬렉션/나의 기록 nav + 아이콘, 매거진 푸터.
- `index.php`(홈 매거진 레이아웃), `board/list.php`(카드 그리드+탭), `board/view.php`(상세), `board/write.php`(폼), `account/login.php`(2열) 마크업 재구성.
- 사진은 CSS 톤 플레이스홀더(외부 이미지·저작권 의존 없음). 자유 이미지로 교체 가능.
- 폰트는 Google Fonts(나눔명조)·jsdelivr(Pretendard) CDN 사용 → 보완 버전 CSP에 두 출처를 `style-src`/`font-src`로 허용 추가.

## 2. 가벼운 기능
- **카테고리**(소설/에세이/시/인문) : `db/init.sql` posts 테이블에 `category` 컬럼 추가+시드, 목록 탭 필터, 쓰기 셀렉터.
  - 취약 버전: 입력을 그대로(기존 취약 성격 유지) / 보완 버전: 화이트리스트 검증.
  - 목록 SELECT 컬럼이 6개가 되어, UNION 공격 재현 payload도 6개 컬럼으로 갱신(README·REPRODUCE·SECURITY_REPORT 반영).
- **나의 기록(마이페이지)** `mypage/index.php`(신규) : 내 문장 수·총 조회·카테고리 수(모두 실제 집계) + 내 문장 목록.
  - 취약 버전: 기존 스타일 / 보완 버전: Prepared Statement + 출력 이스케이프.

## 주의 — 스키마 변경(category) 반영
기존 볼륨이 있으면 재시드가 필요합니다: `docker compose down -v && docker compose up --build`.
운영 중 마이그레이션: `ALTER TABLE <db>.posts ADD COLUMN category VARCHAR(20) NOT NULL DEFAULT '에세이';`

---

# CHANGELOG — v3 → v4 (공통 보안 보완)

> 기준본: 디자인 수정본 `mitjul-v3-quiet-credits`(사진·형광펜 밑줄·푸터·Credits 페이지).
> **취약 버전의 취약점 6종 실습 코드는 변경하지 않았습니다.** 보완은 secure 버전과 환경 설정에만 들어갔습니다.

## 1. 오류 정보 노출 방지 — secure [보완 8]
- `app/secure/includes/errors.php`(신규): `display_errors` 끔, 상세 오류는 서버 로그로만(`docker compose logs secure`).
  잡히지 않은 예외·치명적 오류는 일반 안내 화면(500)으로 대체.
- `db.php`·`auth.php`가 가장 먼저 불러오므로 모든 secure 페이지에 적용.
- 취약 버전은 오류 기반 SQLi 실습을 위해 오류를 그대로 보여 주는 상태를 유지.

## 2. DB 비밀번호 하드코딩 제거 — 양쪽 공통 [보완 9]
- `app/*/includes/db.php`: 코드 안의 기본 비밀번호 제거. 환경변수가 비면 접속을 시도하지 않고 중단.
- `db/init.sql`: `CREATE USER ... IDENTIFIED BY '비밀번호'`, `GRANT` 제거 → 비밀번호가 SQL 파일에 남지 않음.
- `db/users.sh`(신규): `.env` 값으로 앱 전용 계정 생성 + 최소 권한(DML만) 부여. 값 누락·위험 문자 입력 시 초기화 중단.
- `docker-compose.yml`: `init.sql`→`10-schema.sql`, `users.sh`→`20-users.sh` 순서로 실행되게 마운트, DB 컨테이너에 계정 변수 전달.
- 결과: DB 비밀번호는 `.env` **한 곳에서만** 관리.

## 3. 비밀번호 재설정 인증코드 노출 제어 — secure [보완 10]
- 코드 안 상수 `RESET_DEV_MODE = true` → 환경변수 `RESET_DEV_MODE`(기본 `false`).
- `false`: 메일 발송 기능이 없으므로 재설정 기능 자체를 비활성화하고 안내만 표시.
- `true`: 로컬 실습에서만 인증코드를 화면에 표시. zip의 로컬용 `.env`는 캡처를 위해 `true`, `.env.example`은 `false`.

## 4. 로그인·로그아웃 CSRF 보완 — secure [보완 4 확장]
- 로그인: 폼에 CSRF 토큰 추가, `login_process.php`는 POST + 토큰 일치일 때만 처리(Login CSRF 차단).
- 로그아웃: 헤더 링크에 세션 토큰 포함, `logout.php`는 토큰이 맞을 때만 로그아웃(외부 사이트의 강제 로그아웃 차단).

## 적용 시 주의
- DB 계정은 **볼륨이 비어 있을 때 최초 1회**만 만들어집니다. v3에서 쓰던 볼륨은 비밀번호가 같아 그대로 동작하지만,
  `.env`의 DB 비밀번호를 바꿨다면 `docker compose down -v && docker compose up --build`로 다시 초기화하세요.
- `.env` 값에는 영문/숫자/`_ . -`만 쓸 수 있습니다.

## v4 배포 수정 (VM 실행 중 발견)
- 증상: DB 초기화 중 `10-schema.sql: Permission denied` → `mitjul-db is unhealthy`.
- 원인: 기준본 zip의 파일 권한이 모두 600(소유자만 읽기)이라 컨테이너 안 `mysql`·`www-data` 사용자가 파일을 읽지 못함.
- 조치: 파일 644 / 폴더 755 / `users.sh` 755 / `.env` 600으로 정리. 앱 컨테이너 시작 시 `uploads/` 소유자를 `www-data`로 맞추도록 `command` 추가.

## v4.1 홈 레이아웃 반영 (디자인 수정본 quiet-spaced)
- `app/*/index.php`: 홈 상단(제목·소개·사진·오늘의 문장)을 `section.home-hero` > `.hero-intro` / `.hero-feature` 구조로 재배치. 인용은 `figure/blockquote` 의미 태그 사용.
- `app/*/css/style.css`: 홈 전용 간격·비율 규칙 추가.
- 카드(DB 출력부)는 변경 없음 — 보안 버전 이스케이프 / 취약 버전 원문 출력 그대로.
- (v4.2) 홈에서 본문 최소 높이를 없애 푸터가 카드 바로 아래에 오도록 조정 (CSS만, 홈 화면 한정).
- (v4.3) 같은 방식을 모든 페이지로 확대 — `body`·`main.container`의 화면 높이 최소값 제거, 아래 여백 36px(모바일 28px)로 통일.

## 여전히 보류한 항목
업로드 파일 웹루트 외부 저장·이미지 재인코딩, Mailpit 실제 메일, Docker 네트워크 완전 분리(현재는 계정·DB·포트 바인딩 분리),
자동화 테스트, `credits.html`(정적 페이지)의 보안 헤더(PHP를 거치지 않아 CSP 미적용 — 입력·스크립트가 없는 페이지라 보류).

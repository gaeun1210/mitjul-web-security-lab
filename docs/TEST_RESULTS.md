# 검증 결과 (TEST_RESULTS)

> **작성 원칙:** 실제로 VM에서 수행해 확인한 항목만 `PASS`로 바꾸세요.
> 수행 전/미수행 항목은 `NOT TESTED`로 둡니다. 결과가 기대와 다르면 `FAIL`, 일부만 충족하면 `PARTIAL`.
> 지어낸 결과를 적지 마세요 — 이 문서의 신뢰도가 포트폴리오의 신뢰도입니다.

실행 환경: VMware Ubuntu(Kali) · Docker Compose · 취약 `http://localhost:8001` / 보완 `http://localhost:8080`
수행자: (이름) · 수행일: (YYYY-MM-DD)

## A. 소스코드 수정 (저자 수행, 환경 무관)

| 항목 | 상태 | 비고 |
|---|---|---|
| 전체 PHP `php -l` 문법 통과 | PASS | v2 전 파일 |
| 목(mock) DB 전 페이지 렌더링 무오류 | PASS | 치명적 오류 없음 |
| XSS 이스케이프·IDOR 403·CSRF 토큰 불일치 차단(로직 단위) | PASS | 테스트 하네스 |

### A-2. v4 공통 보안 보완 (저자 수행 · 2026-11-25 · PHP 내장 서버 + 가짜 DB, Docker 미사용)

| 항목 | 상태 | 비고 |
|---|---|---|
| 전체 PHP 41개 `php -l` | PASS | 오류 0 |
| `docker compose config` 구성 해석 | PASS | 마운트 순서·환경변수 확인 |
| secure: DB 접속 예외 시 일반 안내(500), 화면에 계정명·경로 노출 0건, 상세는 로그 | PASS | 가짜 예외 주입 |
| secure: DB_PASS 누락 시 접속 중단 + 로그 기록 | PASS | |
| secure 로그인: GET→302, 토큰 없는 POST→403, 올바른 토큰→로그인 | PASS | |
| secure 로그아웃: 토큰 없음/틀림→로그인 유지, 화면 링크→로그아웃 | PASS | |
| secure 재설정: RESET_DEV_MODE 미설정→비활성 안내·코드 노출 0 / true→코드 표시 | PASS | |
| `users.sh`: 정상 SQL 생성 / 빈 값·따옴표 입력 시 중단 | PASS | `docker_process_sql` 대체 함수로 검사 |
| 양쪽 10개 페이지 200·PHP 경고 0, secure CSP 헤더 유지 | PASS | 회귀 |
| 실제 MariaDB 컨테이너에서 `users.sh` 계정 생성 | NOT TESTED | VM에서 `down -v` 후 확인 필요 |
| VM 실환경에서 위 항목 재확인 | NOT TESTED | |

## B. 취약점 6종 — 실제 공격·방어 검증 (VM에서 수행)

실행 환경: VMware Ubuntu · Docker Compose · 취약 `http://localhost:8001` / 보완 `http://localhost:8080`
수행일: 2026-11-25 · 증거 캡처는 `docs/images/` 참고.

| # | 항목 | 기대 결과 | 판정 | 증거 |
|---|---|---|---|---|
| 01 | SQLi 취약: `admin' -- ` 로그인 | 비번 없이 admin 로그인 | PASS | 01-sqli-login-before.png |
| 01 | SQLi 보완: 동일 입력 | 로그인 실패 | PASS | 01-sqli-login-after.png |
| 02 | SQLi 취약: sqlmap `users` 덤프 | 평문 비밀번호 노출 | PASS | 02-sqli-dump-before.png |
| 02 | SQLi 취약: 브라우저 오류 기반 | 쿼리·경로 노출(error-based) | PASS | 02-sqli-error-before.png |
| 02 | SQLi 보완: 동일 입력 | 주입 불가/결과 없음 | PASS | 02-sqli-dump-after.png |
| 03 | XSS 취약: `<script>alert()` 글 | alert(쿠키) 실행 | PASS | 03-xss-before.png |
| 03 | XSS 보완: 동일 글 | 글자 그대로 표시(실행 X) | PASS | 03-xss-after.png |
| 04 | IDOR 취약: yuna가 admin 수정폼 | 타인(admin) 폼 열림 | PASS | 04-idor-before.png |
| 04 | IDOR 보완: 동일 요청 | 본인 정보만(URL id 무시) | PASS | 04-idor-after.png |
| 05 | CSRF 취약: 토큰 없이 delete | 글 삭제됨 | PASS | 05-csrf-before.png |
| 05 | CSRF 보완: 토큰 없는 요청 | 403 토큰 불일치 | PASS | 05-csrf-after-token.png |
| 05 | CSRF 보완: 교차 사이트 요청 | 쿠키 미전송→로그인 이동 | PASS | 05-csrf-after-samesite.png |
| 06 | 업로드 취약: `shell.php?cmd=id` | 명령 실행(uid=www-data) | PASS | 06-upload-before.png |
| 06 | 업로드 보완: 비이미지 업로드 | "이미지 파일만" 거부 | PASS | 06-upload-after.png |

## C. 정상 기능 회귀 테스트 (VM)

| 항목 | 기대 | 판정 |
|---|---|---|
| 정상 로그인 `admin/Admin!234`(보완) | 성공 | NOT TESTED |
| 정상 문장 작성·조회·검색 | 정상 | NOT TESTED |
| 정상 이미지(jpg/png) 업로드·조회(보완) | 정상 | NOT TESTED |
| 한글·특수문자·줄바꿈 표시 | 정상 | NOT TESTED |

## D. v2 공통 보안 수정 (VM에서 확인)

| 항목 | 확인 방법 | 판정 |
|---|---|---|
| 보안 헤더(보완) | `curl -I http://localhost:8080/` 응답에 `X-Frame-Options`·`X-Content-Type-Options`·CSP 존재 | NOT TESTED |
| 세션 쿠키 분리 | 8001은 `MITJUL_VULN_SID`, 8080은 `MITJUL_SECURE_SID` 쿠키 | NOT TESTED |
| DB 계정 최소권한 | `mituser_secure`로 `mitjul_vuln` 접근 시 거부 | NOT TESTED |
| 외부 노출 차단 | 호스트 외부에서 `:8001` 접근 불가(127.0.0.1 바인딩) | NOT TESTED |
| 비번 재설정 | 코드 없이 변경 불가, 코드+만료 검증 후에만 변경 | NOT TESTED |

### VM 확인 명령 예시

```bash
# 보안 헤더
curl -I http://localhost:8080/

# 세션 쿠키 이름
curl -i http://localhost:8001/ | grep -i set-cookie
curl -i http://localhost:8080/ | grep -i set-cookie

# DB 계정 최소권한(거부돼야 정상)
docker exec -it mitjul-db mariadb -u mituser_secure -psecure_db_pw_2026 -e "USE mitjul_vuln; SELECT 1;"

# 보완 DB 비밀번호가 해시인지
docker exec -it mitjul-db mariadb -u root -prootpass -e "SELECT username,LEFT(password,7) FROM mitjul_secure.users;"
```

## E. 미검증 사유 및 수행 방법

전체 스택(Docker+MariaDB+브라우저+공격 도구) 구동이 필요한 공격 재현은 본 문서 작성 환경에서
직접 수행하지 못해 `NOT TESTED`로 둔 것입니다. 위 `REPRODUCE.md`·본 문서의 절차대로 VMware Ubuntu에서
수행하면 각 항목을 PASS로 확정할 수 있습니다.

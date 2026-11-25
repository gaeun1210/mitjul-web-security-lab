#!/bin/bash
# =====================================================================
#  [보완 9] 앱 전용 DB 계정 생성 — 비밀번호는 .env 에서만 받음
#
#  MariaDB 공식 이미지는 최초 초기화(볼륨이 비어 있을 때) 때
#  /docker-entrypoint-initdb.d 안의 파일을 이름 순서대로 실행합니다.
#    10-schema.sql (= db/init.sql)  →  20-users.sh (= 이 파일)
#  그래서 DB/테이블이 만들어진 뒤에 계정과 권한이 붙습니다.
#
#  최소 권한 원칙:
#    - 취약 앱 계정  → mitjul_vuln   DB 에만 SELECT/INSERT/UPDATE/DELETE
#    - 보완 앱 계정  → mitjul_secure DB 에만 SELECT/INSERT/UPDATE/DELETE
#    (DROP/ALTER/GRANT 없음 → 한쪽 앱이 뚫려도 다른 쪽 DB 는 못 건드림)
# =====================================================================

for v in VULN_DB_USER VULN_DB_PASS SECURE_DB_USER SECURE_DB_PASS; do
    val="${!v:-}"
    if [ -z "$val" ]; then
        echo "[users.sh] $v 값이 비어 있습니다. .env 파일을 확인하세요." >&2
        exit 1
    fi
    # 값이 SQL 문 안에 그대로 들어가므로 안전한 문자만 허용 (따옴표 등으로 SQL 이 깨지는 것 방지)
    if ! [[ "$val" =~ ^[A-Za-z0-9_.-]+$ ]]; then
        echo "[users.sh] $v 에는 영문/숫자/_ . - 만 쓸 수 있습니다." >&2
        exit 1
    fi
done

SQL="
CREATE USER IF NOT EXISTS '${VULN_DB_USER}'@'%'   IDENTIFIED BY '${VULN_DB_PASS}';
CREATE USER IF NOT EXISTS '${SECURE_DB_USER}'@'%' IDENTIFIED BY '${SECURE_DB_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE ON mitjul_vuln.*   TO '${VULN_DB_USER}'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON mitjul_secure.* TO '${SECURE_DB_USER}'@'%';
FLUSH PRIVILEGES;
"

# 공식 이미지의 초기화 단계에서는 docker_process_sql 함수(root 로 접속)를 쓸 수 있음
if declare -F docker_process_sql >/dev/null; then
    echo "$SQL" | docker_process_sql
else
    echo "$SQL" | mariadb --protocol=socket -uroot -p"${MARIADB_ROOT_PASSWORD}"
fi

echo "[users.sh] 앱 DB 계정 생성 완료: ${VULN_DB_USER}, ${SECURE_DB_USER}"

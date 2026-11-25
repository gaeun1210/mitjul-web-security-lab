<?php
/* DB 접속. 접속 정보는 .env → docker-compose 환경변수로 주입됩니다.
 * [공통 정리] 코드 안의 기본 비밀번호는 제거 (실습용 취약점 6종과 무관한 환경 설정이므로 양쪽 모두 적용)
 * ※ 오류 메시지를 그대로 화면에 보여 주는 것은 '취약 버전'의 의도된 상태입니다.
 */

$host   = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'mitjul_vuln';
$dbuser = getenv('DB_USER');
$dbpass = getenv('DB_PASS');

if ($dbuser === false || $dbuser === '' || $dbpass === false || $dbpass === '') {
    die("DB_USER / DB_PASS 환경변수가 비어 있습니다. .env 파일을 확인하세요.");
}

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 화면 상단 배지에 쓰는 표시용 모드
$APP_MODE = getenv('APP_MODE') ?: 'vulnerable';

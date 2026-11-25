<?php
/* DB 접속 (보완 버전)
 * [보완 8] 오류 노출 방지: errors.php 를 가장 먼저 불러 상세 오류는 로그로만 보냄
 * [보완 9] 비밀번호 하드코딩 제거: 접속 정보는 .env → docker-compose 환경변수로만 받음
 *          (코드에 기본 비밀번호를 두지 않음. 값이 없으면 접속을 시도하지 않고 중단)
 */
require_once __DIR__ . '/errors.php';

$host   = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'mitjul_secure';
$dbuser = getenv('DB_USER');
$dbpass = getenv('DB_PASS');

if ($dbuser === false || $dbuser === '' || $dbpass === false || $dbpass === '') {
    error_log('[mitjul-secure] DB_USER / DB_PASS 환경변수가 비어 있습니다. .env 파일을 확인하세요.');
    app_fail(500);
}

// 접속·쿼리 오류를 예외로 받아 errors.php 의 핸들러가 처리 (화면에 SQL 오류가 찍히지 않음)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
$conn->set_charset("utf8mb4");

$APP_MODE = getenv('APP_MODE') ?: 'secure';

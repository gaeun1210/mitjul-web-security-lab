<?php
/* [보완 8] 오류 정보 노출 방지 (보완 버전 전용)
 *
 * 공식 php 이미지에는 php.ini 가 없어서 display_errors 가 기본으로 켜져 있습니다.
 * 그대로 두면 SQL 오류 메시지, 서버 파일 경로(/var/www/html/...)가 화면에 찍혀
 * 공격자에게 내부 구조를 알려 주게 됩니다.
 *  - 화면: 일반 안내 문구만
 *  - 상세 내용: 서버 로그로만 (docker compose logs secure 로 확인)
 *
 * ※ 취약 버전은 오류 기반 SQL Injection 실습을 위해 일부러 오류를 그대로 보여 줍니다.
 */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// 중간에 오류가 나면 그때까지 출력된 반쪽 화면을 버리고 안내 화면만 보이도록 버퍼링
ob_start();

function app_fail(int $status = 500): void {
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="ko"><head><meta charset="UTF-8"><title>밑줄 · 일시적인 오류</title></head>'
       . '<body style="font-family:sans-serif;padding:48px;color:#2c2824;background:#f7f4ec">'
       . '<h2>요청을 처리하지 못했습니다.</h2><p>잠시 후 다시 시도해 주세요.</p>'
       . '<p><a href="/index.php">홈으로</a></p></body></html>';
    exit;
}

// 잡히지 않은 예외(DB 오류 포함) → 로그에 상세 기록, 화면엔 일반 문구
set_exception_handler(function (Throwable $e) {
    error_log('[mitjul-secure] ' . get_class($e) . ': ' . $e->getMessage()
              . ' @ ' . $e->getFile() . ':' . $e->getLine());
    app_fail(500);
});

// 치명적 오류(Fatal)도 같은 방식으로 처리
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_fail(500);
    }
});

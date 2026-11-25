-- =====================================================================
--  밑줄(Mitjul) 보안 실습 랩 - 초기 DB
--  같은 MariaDB 서버 안에 두 개의 DB 를 만듭니다.
--    1) mitjul_vuln   : 취약 버전용 (비밀번호가 '평문'으로 저장됨)
--    2) mitjul_secure : 보완 버전용 (비밀번호가 bcrypt '해시'로 저장됨)
--  두 DB 는 스키마는 같고 users.password 저장 형태만 다릅니다.
-- =====================================================================

-- 앱 접속 계정을 '버전별로 분리'합니다. (최소 권한 원칙)
--   - 취약 앱  -> mituser_vuln   : mitjul_vuln   DB 에만 접근
--   - 보완 앱  -> mituser_secure : mitjul_secure DB 에만 접근
-- 각 계정은 DML(SELECT/INSERT/UPDATE/DELETE)만 가지며, DROP/ALTER/GRANT 는 없습니다.
-- 이렇게 하면 한쪽 앱이 뚫려도 다른 쪽 DB 는 건드릴 수 없습니다.
-- [보완 9] 계정 생성(CREATE USER)과 권한 부여(GRANT)는 이 파일에 두지 않습니다.
--   비밀번호를 SQL 파일에 적으면 저장소에 그대로 남기 때문에,
--   db/users.sh 가 .env 의 값을 읽어 계정을 만듭니다. (이 파일 실행 직후 자동 실행)

-- =====================================================================
--  공통 스키마 정의를 위한 프로시저 느낌의 반복 — 그냥 두 번 적어 둡니다.
-- =====================================================================

-- ---------- 1) 취약 버전 DB ----------
CREATE DATABASE IF NOT EXISTS mitjul_vuln
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mitjul_vuln;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,          -- 취약: 평문 그대로 저장
    name       VARCHAR(50)  NOT NULL,
    email      VARCHAR(120) NOT NULL,
    role       VARCHAR(20)  NOT NULL DEFAULT 'member',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    title          VARCHAR(200) NOT NULL,       -- 밑줄 친 문장 (한 줄)
    content        TEXT NOT NULL,               -- 그 문장에 대한 내 생각
    book_title     VARCHAR(200) NOT NULL,
    book_author    VARCHAR(120) NOT NULL,
    category       VARCHAR(20)  NOT NULL DEFAULT '에세이',
    image_original VARCHAR(255) NULL,           -- 업로드 원본 파일명
    image_stored   VARCHAR(255) NULL,           -- 실제 저장 파일명
    views          INT NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 평문 비밀번호 (취약 버전: 로그인 시 문자열 그대로 비교)
INSERT INTO users (username, password, name, email, role) VALUES
 ('admin', 'Admin!234', '관리자',  'admin@mitjul.local', 'admin'),
 ('yuna',  'yuna1234',  '서유나',  'yuna@mitjul.local',  'member'),
 ('minho', 'minho1234', '김민호',  'minho@mitjul.local', 'member'),
 ('gaeun', 'gaeun1234', '김가은',  'gaeun@mitjul.local', 'member');

INSERT INTO posts (user_id, title, content, book_title, book_author, category, views) VALUES
 (2, '밑줄을 긋는다는 건 그 문장 앞에서 잠깐 멈췄다는 뜻이다.',
     '첫 글이라 제가 좋아하는 생각을 적어봤어요. 밑줄은 결국 멈춤의 기록 같아요.',
     '문장수집', '이가람', '에세이', 7),
 (3, '빌린 책에는 밑줄을 긋지 못해서, 대신 문장을 베껴 적었다.',
     '도서관에서 빌린 책은 깨끗이 봐야 하니까요. 필사의 시작은 아쉬움이었던 듯.',
     '도서관의 시간', '정해든', '인문', 3),
 (4, '다 읽은 책보다, 한 문장에서 오래 머문 책이 기억에 남는다.',
     '완독보다 중요한 건 머문 시간이라고 믿어요.',
     '천천히 읽기', '한서우', '시', 2);

-- ---------- 2) 보완 버전 DB ----------
CREATE DATABASE IF NOT EXISTS mitjul_secure
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mitjul_secure;

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,        -- 보완: bcrypt 해시 저장
    name          VARCHAR(50)  NOT NULL,
    email         VARCHAR(120) NOT NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'member',
    reset_code    VARCHAR(255) NULL,            -- 비밀번호 재설정 인증코드의 '해시'(평문 저장 안 함)
    reset_expires DATETIME     NULL,            -- 인증코드 만료 시각
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    title          VARCHAR(200) NOT NULL,
    content        TEXT NOT NULL,
    book_title     VARCHAR(200) NOT NULL,
    book_author    VARCHAR(120) NOT NULL,
    category       VARCHAR(20)  NOT NULL DEFAULT '에세이',
    image_original VARCHAR(255) NULL,
    image_stored   VARCHAR(255) NULL,
    views          INT NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 보완 버전: 같은 비밀번호를 bcrypt 해시로 저장 (password_verify 로 검증)
INSERT INTO users (username, password, name, email, role) VALUES
 ('admin', '$2y$10$xvByxYfURxoFgkzmXVx9De5rdoWMnA.E68f/kI88KQcbY04uZZ4DO', '관리자', 'admin@mitjul.local', 'admin'),
 ('yuna',  '$2y$10$MS4YSoeZfkUYwN6WrfHTFetlzqdJM98B6Stq6wXj4pmiJuPdKpeLS', '서유나', 'yuna@mitjul.local',  'member'),
 ('minho', '$2y$10$fOARb0CNkFUVRebNUDwElOIYmC/qid5ZqbysnmAh1eJak9Oe4HT0e', '김민호', 'minho@mitjul.local', 'member'),
 ('gaeun', '$2y$10$hKtKH93o2Gm.i05AWx/CPuZhCG2nN3U80lxPk7Pofk617CaS1H.aC', '김가은', 'gaeun@mitjul.local', 'member');

INSERT INTO posts (user_id, title, content, book_title, book_author, category, views) VALUES
 (2, '밑줄을 긋는다는 건 그 문장 앞에서 잠깐 멈췄다는 뜻이다.',
     '첫 글이라 제가 좋아하는 생각을 적어봤어요. 밑줄은 결국 멈춤의 기록 같아요.',
     '문장수집', '이가람', '에세이', 7),
 (3, '빌린 책에는 밑줄을 긋지 못해서, 대신 문장을 베껴 적었다.',
     '도서관에서 빌린 책은 깨끗이 봐야 하니까요. 필사의 시작은 아쉬움이었던 듯.',
     '도서관의 시간', '정해든', '인문', 3),
 (4, '다 읽은 책보다, 한 문장에서 오래 머문 책이 기억에 남는다.',
     '완독보다 중요한 건 머문 시간이라고 믿어요.',
     '천천히 읽기', '한서우', '시', 2);

FLUSH PRIVILEGES;

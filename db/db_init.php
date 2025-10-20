<?php
$init_file_path = __DIR__ . '../db_initialized.txt';
try {
    // 데이터베이스 연결 확인: phpdb가 존재하고, 사용자 php와 비밀번호 tiger가 맞는지 확인
    $db = new PDO('mysql:host=localhost;dbname=phpdb;charset=utf8mb4', 'php', 'tiger');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 초기화 플래그 확인: db_initialized.txt가 쓰기 가능한 디렉토리에 있는지 확인
    if (!file_exists($init_file_path)) {
        
        // 존재 하는 테이블 한번 삭제
        $db ->exec("
            DROP TABLE user_info,posts,flags,top_secret
            ");

        // user_info 테이블 생성
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_info (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                passwd VARCHAR(255) NOT NULL,
                hint TEXT,
                is_admin BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // posts 테이블 생성
        $db->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                is_hidden BOOLEAN DEFAULT FALSE
            )
        ");

        // flags 테이블 생성
        $db->exec("
            CREATE TABLE IF NOT EXISTS flags (
                id INT PRIMARY KEY AUTO_INCREMENT,
                flag VARCHAR(255) NOT NULL,
                is_secret BOOLEAN DEFAULT TRUE
            )
        ");

        // top_secret 테이블 생성
        $db->exec("
            CREATE TABLE IF NOT EXISTS top_secret (
                id INT UNIQUE NOT NULL AUTO_INCREMENT,
                title VARCHAR(55) NOT NULL PRIMARY KEY,
                post_content TEXT,
                create_at timestamp NOT NULL
            );
        ");

        // 초기 데이터 삽입 (비밀번호 평문 유지)
        $db->exec("
            INSERT IGNORE INTO user_info (id, name, passwd, hint, is_admin) VALUES
            ('admin', 'admin', 'aQd123%!231ddq1)!@{L}!@L!<!@#$*&CW!_1', '관리자 계정', TRUE),
            ('user1', '유저', 'TFY#1@!31@#!$#q12dq1*$%&>!2', '사용자', FALSE),
            ('user2', '유저', 'TF!#!1QQXQY#1@!31@#!$#q12dq13QCq1*$%&>!2', '사용자', FALSE),
            ('user3', '유저', 'TFY#1@!31@#!$#q122vV@dq1*$%&>!2', '사용자', FALSE),
            ('user4', '유저', 'TFY#1@!31@#!$#q12dq1adcwcqc*$%&>!2', '사용자', FALSE),
            ('user5', '유저', 'wqcqwxxqdq1*$%&>!2', '사용자', FALSE)
        ");

        // 포스트 데이터 삽입
        $db->exec("
            INSERT IGNORE INTO posts (id, title, content, is_hidden) VALUES
            (1, '공지사항', '부스에 오신 것을 환영합니다!', 0),
            (2, '[관리자 전용] 점심 메뉴 투표', '이 글을 보고 계신다면... 축하합니다! SQL 인젝션 공격에 성공하셨군요. 저희의 허술한 방어벽을 뚫으셨네요.다음 단계로 넘어가기 위한 정답은 **(검열 됨)** 데이터 말소. (P.S. 운영팀에게 이 사실을 알리지 말아 주세요.)', 1),
            (3, '공지사항', '생각보다 제작에 힘들었습니다', 0),
            (4, '[1급 기밀] 프로젝트 오메가 관련 로그', '경고: 허가되지 않은 접근이 감지되었습니다. 이 로그는 시스템 관리자 외에는 열람이 불가능한 기밀 데이터입니다. 당신의 공격 기법(SQL Injection)이 서버에 기록되었습니다. 이 사실을 증명하려면 정답으로 OMEGA_PROTOCOL 을 입력하세요.', 1),
            (5, '공지사항', '재밌게 즐겨주시면 감사하겠습니다', 0),
            (6, '[관리자 전용] 야식 메뉴 투표', '아 배고픈데 진짜 저녁 뭐 먹을래 만들다 밤을 몇번을 샌거야....', 1),
            (7, '공지사항', '하나 하나 구현 한다고 연휴도 반납한 개발자에게 X를 눌러 조의를 표해주세요', 0),
            (8, '[관리자 전용] 개발자의 한탄', '연휴인데 놀지도 못하고...코딩이나 한다니 핳하...', 1)
        ");

        // Flag 데이터 삽입
        $db->exec("
            INSERT IGNORE INTO flags (flag, is_secret) VALUES
            ('FLAG{You_Cracked_The_Code}', TRUE)
        ");

;

        // 초기화 완료 플래그: 디렉토리에 쓰기 권한이 있는지 확인
        file_put_contents('db_initialized.txt', 'initialized');
        error_log("Database initialized successfully");
    }
} catch (PDOException $e) {
    error_log("DB initialization error: " . $e->getMessage());
    die("Database connection failed: " . h($e->getMessage()));
}
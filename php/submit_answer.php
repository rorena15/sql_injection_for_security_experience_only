<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// error_reporting(0); // 개발 중에는 주석 처리하여 오류를 확인하는 것이 좋습니다.
// ini_set('display_errors', 0);

include_once __DIR__ . '/../db/db_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "잘못된 요청입니다.";
    header("Location: ../index.php");
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
$answer = trim($_POST['answer'] ?? '');
$level = $_POST['level'] ?? '1';
$secure_mode = $_SESSION['secure_mode'] ?? 'off';

if (!check_csrf($csrf_token)) {
    if ($level !== '1') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '잘못된 CSRF 토큰입니다.']);
        exit;
    }
    $_SESSION['error'] = "잘못된 CSRF 토큰입니다.";
    error_log("CSRF validation failed for answer submission");
    header("Location: ../index.php");
    exit;
}


if ($level === '1') {
    if (!isset($_SESSION['username'])) {
        $_SESSION['error'] = "먼저 로그인해야 합니다.";
        error_log("Level 1 answer submission failed: not logged in");
        header("Location: ../index.php");
        exit;
    }

    // Level 1: 관리자로 로그인 성공 후, 정답을 맞추는 로직
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && ($answer === 'admin' || $answer === '1' || strtoupper($answer) === 'TRUE')) {
        $_SESSION['cleared_levels'][1] = true;
        $_SESSION['success'] = "Level 1 클리어! Level 2로 이동합니다.";
        error_log("Level 1 cleared: answer=$answer, username={$_SESSION['username']}, is_admin={$_SESSION['is_admin']}");
        header("Location: ../html_sent/main_page.php?level=2&secure=$secure_mode");
        exit;
    } else {
        $_SESSION['error'] = "잘못된 정답입니다. 관리자 계정 정보를 입력하세요.";
        error_log("Level 1 answer incorrect: answer=$answer, username={$_SESSION['username']}, is_admin=" . (isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 'none'));
        header("Location: ../index.php");
        exit;
    }

// ===== Level 2~5 로직 (DB 연동 방식) =====
} else {
    $is_correct = false;
    $message = '잘못된 정답입니다.';
    $level_int = (int)$level;

    try {
        if ($level === '2') {
            // DB에서 숨겨진 게시물의 제목이나 내용과 일치하는지 확인
            $stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE is_hidden = 1 AND (title = :answer OR content = :answer)");
            $stmt->execute([':answer' => $answer]);
            if ($stmt->fetchColumn() > 0) {
                $is_correct = true;
                $message = "실제로는 이렇게 작동합니다:\nSELECT id, title, content FROM posts WHERE title LIKE '%<span style=\"color:#0000FF;\">' OR 1=1 UNION SELECT</span><span style=\"color:red;\"> id, title, content FROM posts WHERE is_hidden = 1 ;</span><span style=\"color:#808080;\"> -- %' AND is_hidden = FALSE</span>";
            }
        } elseif ($level === '3') {
            // 입력된 정답이 'top_secret'과 일치하는지 직접 확인
            if ($answer === 'top_secret') {
            $is_correct = true;
            $message = "실제로는 이렇게 작동합니다:\nSELECT id, title, content FROM posts WHERE title LIKE '%<span style=\"color:#0000FF;\">' OR 1=1 UNION SELECT</span><span style=\"color:red;\"> null, table_name, null FROM information_schema.tables WHERE table_schema = DATABASE()</span><span style=\"color:#808080;\"> -- - %' AND is_hidden = FALSE</span>";
    }
        } elseif ($level === '4') {
            // is_admin 플래그가 참인 사용자의 비밀번호와 일치하는지 확인
            $stmt = $db->prepare("SELECT passwd FROM user_info WHERE is_admin = 1 LIMIT 1");
            $stmt->execute();
            $admin_pw = $stmt->fetchColumn();
            if ($admin_pw !== false && $answer === $admin_pw) {
                $is_correct = true;
                $message = "실제로는 이렇게 작동합니다:\nSELECT id, title, content FROM posts WHERE title LIKE '%<span style=\"color:#0000FF;\">' OR 1=1 UNION SELECT</span><span style=\"color:red;\"> name, passwd, null FROM user_info ;</span><span style=\"color:#808080;\"> -- %' AND is_hidden = FALSE</span>";
            }
        } elseif ($level === '5') {
            // is_secret 플래그가 참인 플래그와 일치하는지 확인
            $stmt = $db->prepare("SELECT flag FROM flags WHERE is_secret = 1 LIMIT 1");
            $stmt->execute();
            $secret_flag = $stmt->fetchColumn();
            if ($secret_flag !== false && $answer === $secret_flag) {
                $is_correct = true;
                $message = "실제로는 이렇게 작동합니다:\nSELECT id, title, content FROM posts WHERE title LIKE '%<span style=\"color:#0000FF;\">' OR 1=1 UNION SELECT</span><span style=\"color:red;\"> id, flag, null FROM flags WHERE is_secret = TRUE ;</span><span style=\"color:#808080;\"> -- %' AND is_hidden = FALSE</span>";
            }
        } elseif($level ==='6'){
            $stmt = $db ->prepare("SELECT title FROM posts WHERE id > 8 AND (title = :answer6)");
            $stmt ->execute([':answer6' => $answer]);
            $vulun_title = $stmt ->fetchColumn();
            if($vulun_title !== false && $answer === $vulun_title){
                $is_correct = true;
                $message = "예전 광고판 초등학생 해킹 사례처럼 흔적을 남길수도 흔적없이 사라질수도 있습니다";
            }
        }
        else {
            $message = "잘못된 레벨입니다.";
        }
    } catch (PDOException $e) {
        // DB 오류 발생 시 안전하게 처리
        error_log("Answer check DB error: " . $e->getMessage());
        $message = "정답 확인 중 오류가 발생했습니다.";
    }


    // 정답일 경우 세션에 클리어 상태 저장
    if ($is_correct) {
        $_SESSION['cleared_levels'][$level_int] = true;
        error_log("Level {$level} cleared via AJAX: answer=$answer");
    } else {
        error_log("Level {$level} answer incorrect via AJAX: answer=$answer");
    }

    $redirect_url = ($level === '5' && $is_correct) ? 'success.php' : null;

    header('Content-Type: application/json');
    echo json_encode(
        [
            'success' => $is_correct,
            'level' => $level,
            'message' => $message,
            'redirect' => $redirect_url 
        ], 
        // HTML 태그가 포함된 메시지를 정상적으로 보내기 위한 옵션
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}
?>
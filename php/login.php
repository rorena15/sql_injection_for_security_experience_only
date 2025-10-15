<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../db/db_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "잘못된 요청입니다.";
    header("Location: ../index.php");
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!check_csrf($csrf_token)) {
    $_SESSION['error'] = "잘못된 CSRF 토큰입니다.";
    error_log("CSRF validation failed for login attempt");
    header("Location: ../index.php");
    exit;
}

if ($username === '' || $password === '') {
    $_SESSION['error'] = "아이디와 비밀번호를 입력하세요.";
    error_log("Login failed: empty username or password");
    header("Location: ../index.php");
    exit;
}

try {
    // 취약한 쿼리 (SQL 인젝션 학습 목적)
    $sql = "SELECT id, name, is_admin FROM user_info WHERE id = '$username' AND passwd = '$password'";
    error_log("Executing SQL: $sql");
    $stmt = $db->query($sql);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['username'] = $user['name'];
        $_SESSION['is_admin'] = $user['is_admin'] == 1;
        error_log("Login successful: username={$user['name']}, is_admin={$user['is_admin']}");
        header("Location: ../index.php");
        exit;
    } else {
        $_SESSION['error'] = "아이디 또는 비밀번호가 잘못되었습니다.";
        error_log("Login failed: invalid credentials for username=$username");
        header("Location: ../index.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "로그인 처리 중 오류가 발생했습니다.";
    error_log("Login error: " . $e->getMessage());
    header("Location: ../index.php");
    exit;
}
?>
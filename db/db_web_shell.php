<?php
session_start();

// ===== 설정 =====
$db_host = 'localhost';
// ================

// Helper function
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// 로그아웃 처리
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 데이터베이스 변경 처리
if (isset($_GET['action']) && $_GET['action'] === 'change_db') {
    unset($_SESSION['db_name']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 페이지 상태 결정 변수
$page_state = 'login';
if (isset($_SESSION['db_user'])) {
    $page_state = 'db_select';
}
if (isset($_SESSION['db_name'])) {
    $page_state = 'query';
}

$error_message = null;
$success_message = null;
$results = [];
$headers = [];
$query_history = $_POST['query'] ?? 'SELECT * FROM ... LIMIT 100;';


// ===== 로직 처리 =====

// 1. 로그인 시도
if ($page_state === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    try {
        $db = new PDO("mysql:host=$db_host", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $_SESSION['db_user'] = $user;
        $_SESSION['db_pass'] = $pass;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error_message = "로그인 실패: " . $e->getMessage();
    }
}

// 2. DB 선택
if ($page_state === 'db_select' && isset($_GET['select_db'])) {
    $_SESSION['db_name'] = $_GET['select_db'];
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 3. 쿼리 실행
if ($page_state === 'query' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = $_POST['query'];
    if (!empty($query)) {
        try {
            $db = new PDO("mysql:host={$db_host};dbname={$_SESSION['db_name']};charset=utf8mb4", $_SESSION['db_user'], $_SESSION['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $stmt = $db->prepare($query);
            $stmt->execute();
            
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $query)) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($results) {
                    $headers = array_keys($results[0]);
                    $success_message = count($results) . "개의 행이 반환되었습니다.";
                } else {
                    $success_message = "쿼리는 성공했지만 반환된 결과가 없습니다.";
                }
            } else {
                $affected_rows = $stmt->rowCount();
                $success_message = "쿼리가 성공적으로 실행되었습니다. (" . $affected_rows . "개 행에 영향을 미침)";
            }

        } catch (PDOException $e) {
            $error_message = "오류 발생: " . $e->getMessage();
        }
    } else {
        $error_message = "실행할 쿼리를 입력하세요.";
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>Root DB Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f7fa; color: #222; }
        .wrap { max-width: 1100px; margin: 0 auto; }
        h1, h2 { margin: 0 0 12px; font-size: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 14px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); border: 1px solid #e8eef6; margin-bottom: 12px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; }
        input[type=text], input[type=password] { width: 100%; padding: 8px; border: 1px solid #ccd6e2; border-radius: 6px; box-sizing: border-box; margin-bottom: 8px; }
        textarea { width: 100%; height: 150px; padding: 8px; border: 1px solid #ccd6e2; border-radius: 6px; font-family: monospace; font-size: 14px; box-sizing: border-box; margin-bottom: 8px; }
        button { padding: 10px 15px; border-radius: 6px; border: none; background: #4caf50; color: #fff; cursor: pointer; font-size: 16px; }
        button:hover { background: #45a049; }
        .message { padding: 12px; border-radius: 6px; font-weight: bold; }
        .message.error { background-color: #f8e8e8; color: #b00020; }
        .message.success { background-color: #e8f4f8; color: #2e7d32; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; table-layout: auto; }
        th, td { padding: 10px; border-bottom: 1px solid #eef3f7; text-align: left; font-size: 14px; word-break: break-all; }
        thead th { background: #4caf50; color: #fff; font-weight: 600; position: sticky; top: 0; }
        tbody tr:nth-child(odd) { background-color: #fafafa; }
        .table-wrap { max-height: 60vh; overflow: auto; }
        .db-list ul { list-style: none; padding: 0; }
        .db-list li a { display: block; padding: 8px; text-decoration: none; color: #007bff; border-radius: 4px; }
        .db-list li a:hover { background-color: #f0f0f0; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .header-actions .info { font-size: 14px; color: #666; }
        .header-actions a { text-decoration: none; background: #6c757d; color: white; padding: 8px 12px; border-radius: 6px; font-size: 14px; }
        .header-actions a:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class="wrap">

    <?php if ($page_state === 'login'): ?>
        <h1>DB 서버 로그인</h1>
        <div class="card">
            <form method="post">
                <label for="username">사용자명:</label>
                <input type="text" id="username" name="username" value="root" required>
                <label for="password">비밀번호:</label>
                <input type="password" id="password" name="password">
                <button type="submit">로그인</button>
            </form>
        </div>
        <?php if ($error_message): ?><div class="card message error"><?= h($error_message); ?></div><?php endif; ?>
    
    <?php elseif ($page_state === 'db_select'): ?>
        <div class="header-actions">
            <h1>데이터베이스 선택</h1>
            <a href="?action=logout">로그아웃</a>
        </div>
        <div class="card db-list">
            <ul>
                <?php
                try {
                    $db = new PDO("mysql:host={$db_host}", $_SESSION['db_user'], $_SESSION['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $dbs = $db->query('SHOW DATABASES;')->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($dbs as $dbname) {
                        echo '<li><a href="?select_db=' . h($dbname) . '">' . h($dbname) . '</a></li>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="message error">DB 목록을 불러올 수 없습니다: ' . h($e->getMessage()) . '</div>';
                }
                ?>
            </ul>
        </div>

    <?php elseif ($page_state === 'query'): ?>
        <div class="header-actions">
            <h1>쿼리 실행</h1>
            <div class="info">사용자: <strong><?= h($_SESSION['db_user']); ?></strong> / DB: <strong><?= h($_SESSION['db_name']); ?></strong></div>
            <div>
                <a href="?action=change_db" style="margin-right: 8px;">DB 변경</a>
                <a href="?action=logout">로그아웃</a>
            </div>
        </div>
        <div class="card">
            <form method="post">
                <label for="query">SQL Query:</label>
                <textarea id="query" name="query" placeholder="여기에 SQL 쿼리를 입력하세요."><?= h($query_history); ?></textarea>
                <button type="submit">쿼리 실행</button>
            </form>
        </div>

        <?php if ($error_message): ?><div class="card message error"><?= h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message): ?><div class="card message success"><?= h($success_message); ?></div><?php endif; ?>
        <?php if (!empty($results)): ?>
            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><?php foreach ($headers as $header): ?><th><?= h($header); ?></th><?php endforeach; ?></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                            <tr><?php foreach ($row as $cell): ?><td><?= h($cell); ?></td><?php endforeach; ?></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>
</body>
</html>
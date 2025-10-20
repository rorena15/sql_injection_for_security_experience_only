<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/db_admin.php';

if (!isset($_SESSION['username'])) {
    $_SESSION['error'] = "로그인이 필요합니다.";
    header("Location: ../index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!check_csrf($token)) {
        $errors[] = "잘못된 요청입니다 (CSRF).";
    } else {
        try {
            if ($action === 'add') {
                $id = trim($_POST['id'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $passwd = $_POST['passwd'] ?? '';
                $hint = trim($_POST['hint'] ?? '');
                $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                if ($id === '' || $name === '' || $passwd === '') {
                    $errors[] = "ID, 이름, 비밀번호는 필수입니다.";
                } else {
                    $sql = "INSERT INTO user_info (id, name, passwd, hint, is_admin, created_at) VALUES (:id, :name, :passwd, :hint, :is_admin, NOW())";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(['id' => $id, 'name' => $name, 'passwd' => $passwd, 'hint' => $hint, 'is_admin' => $is_admin]);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } elseif ($action === 'edit') {
                $id = $_POST['id'] ?? '';
                $name = trim($_POST['name'] ?? '');
                $passwd = $_POST['passwd'] ?? '';
                $hint = trim($_POST['hint'] ?? '');
                $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                if ($id === '' || $name === '') {
                    $errors[] = "잘못된 입력입니다.";
                } else {
                    if ($passwd !== '') {
                        $sql = "UPDATE user_info SET name = :name, passwd = :passwd, hint = :hint, is_admin = :is_admin WHERE id = :id";
                        $stmt = $db->prepare($sql);
                        $stmt->execute(['name' => $name, 'passwd' => $passwd, 'hint' => $hint, 'is_admin' => $is_admin, 'id' => $id]);
                    } else {
                        $sql = "UPDATE user_info SET name = :name, hint = :hint, is_admin = :is_admin WHERE id = :id";
                        $stmt = $db->prepare($sql);
                        $stmt->execute(['name' => $name, 'hint' => $hint, 'is_admin' => $is_admin, 'id' => $id]);
                    }
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } elseif ($action === 'delete') {
                $id = $_POST['id'] ?? '';
                if ($id === '') {
                    $errors[] = "잘못된 ID입니다.";
                } else {
                    $sql = "DELETE FROM user_info WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(['id' => $id]);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }
        } catch (PDOException $e) {
            $errors[] = "쿼리 오류: " . h($e->getMessage());
        }
    }
}

$search = trim($_GET['search'] ?? '');
$limit = intval($_GET['limit'] ?? 100);
if ($limit <= 0 || $limit > 1000) $limit = 100;

$rows = [];
try {
    if ($search !== '') {
        $sql = "SELECT id, name, passwd, hint, is_admin, created_at FROM user_info WHERE name LIKE :s OR hint LIKE :s ORDER BY created_at DESC LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':s', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = "SELECT id, name, passwd, hint, is_admin, created_at FROM user_info ORDER BY created_at DESC LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = $row;
    }
    $stmt->closeCursor();
} catch (PDOException $e) {
    $errors[] = "조회 실패: " . h($e->getMessage());
}

$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>Local DB Manager (user_info)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/db_access.css">
    <link rel="shortcut icon" href="sql.png" type="image/x-icon">
</head>
<body>
<div class="wrap">
    <h1>Local DB Manager — <span class="muted">user_info</span></h1>
    <p><a href="../index.php?logout=true" class="link">로그아웃</a> | <a href="../index.php" class="link">메인 페이지</a></p>

    <div class="top">
        <div class="card" style="flex:1">
            <form method="get" class="inline" style="gap:8px">
                <label for="search">검색(이름/힌트):</label>
                <input id="search" type="text" name="search" value="<?php echo h($search); ?>" placeholder="검색어 입력">
                <label for="limit">표시개수:</label>
                <input id="limit" type="number" name="limit" value="<?php echo h($limit); ?>" min="1" max="1000" style="width:80px">
                <button type="submit">검색</button>
                <a href="<?php echo h(basename($_SERVER['PHP_SELF'])); ?>" style="margin-left:8px;text-decoration:none;padding:8px 10px;border-radius:6px;background:#6c757d;color:#fff">초기화</a>
            </form>
        </div>

        <div class="card" style="min-width:320px">
            <strong>새 사용자 추가</strong>
            <form method="post" style="margin-top:8px">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" value="add">
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <input type="text" name="id" placeholder="ID" required>
                    <input type="text" name="name" placeholder="이름" required>
                    <input type="text" name="passwd" placeholder="비밀번호" required>
                    <input type="text" name="hint" placeholder="힌트">
                    <input type="checkbox" name="is_admin" id="is_admin">
                    <label for="is_admin">관리자</label>
                    <button type="submit">추가</button>
                </div>
            </form>
            <div class="muted" style="margin-top:8px;font-size:13px">비밀번호는 평문으로 저장됩니다.</div>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="errors card">
            <?php foreach ($errors as $e): ?>
                <div><?php echo h($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top:0">사용자 목록 (<?php echo count($rows); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ID</th>
                    <th>이름</th>
                    <th>비밀번호</th>
                    <th>힌트</th>
                    <th>관리자</th>
                    <th>생성일</th>
                    <th class="small">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="8">데이터가 없습니다.</td></tr>
                <?php else: $i=1; foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo h($r['id']); ?></td>
                        <td><?php echo h($r['name']); ?></td>
                        <td><?php echo h($r['passwd']); ?></td>
                        <td><?php echo h($r['hint']); ?></td>
                        <td><?php echo $r['is_admin'] ? '예' : '아니오'; ?></td>
                        <td><?php echo h($r['created_at']); ?></td>
                        <td class="actions">
                            <a href="?edit_id=<?php echo urlencode($r['id']); ?>" style="display:inline-block;padding:6px 8px;background:#007bff;color:#fff;border-radius:6px;text-decoration:none;margin-right:6px;">수정</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo h($r['id']); ?>">
                                <button type="submit" class="danger" style="padding:6px 8px;border-radius:6px;border:none;background:#e74c3c;color:#fff;cursor:pointer;">삭제</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($_GET['edit_id'])):
        $edit_id = $_GET['edit_id'] ?? '';
        $stmt = $db->prepare("SELECT id, name, passwd, hint, is_admin FROM user_info WHERE id = :id");
        $stmt->bindValue(':id', $edit_id, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row):
    ?>
        <div class="card" style="margin-top:12px">
            <h3>사용자 수정 — ID: <?php echo h($row['id']); ?></h3>
            <form method="post" class="inline" style="gap:8px;flex-wrap:wrap">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="text" name="id" value="<?php echo h($row['id']); ?>" placeholder="ID 변경">
                <input type="text" name="name" value="<?php echo h($row['name']); ?>" required placeholder="이름 변경" >
                <input type="text" name="passwd" placeholder="비밀번호 변경 (비워두면 미변경)">
                <input type="text" name="hint" value="<?php echo h($row['hint']); ?>">
                <input type="checkbox" name="is_admin" id="is_admin" <?php if ($row['is_admin']) echo 'checked'; ?>>
                <label for="is_admin">관리자</label>
                <button type="submit">저장</button>
                <a href="<?php echo h(basename($_SERVER['PHP_SELF'])); ?>" style="padding:8px 10px;border-radius:6px;background:#6c757d;color:#fff;text-decoration:none">취소</a>
            </form>
        </div>
    <?php endif; endif; ?>
</div>
</body>
</html>
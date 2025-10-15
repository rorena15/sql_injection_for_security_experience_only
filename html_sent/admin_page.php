<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$timeout = 1800;

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); window.location.href = 'main_page.php';</script>";
    exit;
}

// 세션 만료 체크
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    echo "<script>alert('세션이 만료되어 처음 페이지로 이동합니다.'); window.location.href = 'main_page.php';</script>";
    exit;
}
$_SESSION['last_activity'] = time();

$host = 'localhost';
$dbname = 'phpdb';
$username = 'php';
$password = 'tiger';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 최초 가입자 확인 (created_at 컬럼 기준)
    $sqlFirst = "SELECT id FROM user_info ORDER BY created_at ASC LIMIT 1";
    $stmtFirst = $db->query($sqlFirst);
    $firstUser = $stmtFirst->fetch(PDO::FETCH_ASSOC);

    if (!$firstUser) {
        echo "<script>alert('사용자 데이터가 없습니다.'); window.location.href = 'main_page.php';</script>";
        exit;
    }

    if ($_SESSION['user_id'] != $firstUser['id']) {
        echo "<script>alert('최초 가입자만 접근할 수 있습니다.'); window.location.href = 'main_page.php';</script>";
        exit;
    }

} catch (PDOException $e) {
    echo "<script>alert('DB 연결 실패: " . addslashes($e->getMessage()) . "'); window.location.href = 'main_page.php';</script>";
    exit;
}

// 사용자 목록 조회
try {
    $sqlUsers = "SELECT id, name FROM user_info ORDER BY id ASC";
    $stmtUsers = $db->query($sqlUsers);
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}

// 게시판 글 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitPost'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_name = $_SESSION['name'] ?? '익명';

    try {
        $stmtInsert = $db->prepare("INSERT INTO posts (user_name, title, content) VALUES (:user_name, :title, :content)");
        $stmtInsert->bindParam(':user_name', $user_name);
        $stmtInsert->bindParam(':title', $title);
        $stmtInsert->bindParam(':content', $content);
        $stmtInsert->execute();

        echo "<script>alert('글이 등록되었습니다.'); window.location.href='admin_page.php';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('글 등록 실패: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// 게시판 글 조회
try {
    $sqlPosts = "SELECT * FROM posts ORDER BY created_at DESC";
    $stmtPosts = $db->query($sqlPosts);
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $posts = [];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>관리자 페이지</title>
    <link rel="stylesheet" href="admin_css.css">
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/38.1.0/classic/ckeditor.js"></script>
</head>
<body>
<div class="admin-container">
    <header class="admin-header">
        <h1>관리자 페이지</h1>
        <div class="user-info">
            <span>환영합니다, <?php echo htmlspecialchars($_SESSION['name'] ?? '사용자', ENT_QUOTES, 'UTF-8'); ?>님</span>
            <button id="logoutBtn" class="btn">로그아웃</button>
        </div>
    </header>

    <!-- 사용자 목록 -->
    <section class="card">
        <h2 class="card-title">사용자 목록</h2>
        <div class="table-wrap">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>ID</th>
                        <th>이름</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php $no = 1; foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">사용자 데이터가 없습니다.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- 게시판 작성 -->
    <section class="card">
        <h2 class="card-title">게시판 작성</h2>
        <form action="admin_page.php" method="POST">
            <label>제목:</label><br>
            <input type="text" name="title" required style="width:100%;"><br><br>

            <label>내용:</label><br>
            <textarea name="content" id="editor" rows="10" required></textarea><br><br>

            <button type="submit" name="submitPost" class="btn">글 작성</button>
        </form>
    </section>

    <!-- 게시판 목록 -->
    <section class="card">
        <h2 class="card-title">게시판</h2>
        <div class="table-wrap">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>작성자</th>
                        <th>제목</th>
                        <th>작성일</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($posts): ?>
                        <?php $no = 1; foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($post['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $post['title']; ?></td>
                                <td><?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">게시물이 없습니다.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer class="admin-footer">
        <a class="btn secondary" href="main_page.php">메인 페이지로 돌아가기</a>
    </footer>
</div>

<script>
// CKEditor 초기화
ClassicEditor.create(document.querySelector('#editor')).catch(error => { console.error(error); });

// 로그아웃
document.getElementById('logoutBtn').addEventListener('click', function() {
    if (confirm('정말 로그아웃 하시겠습니까?')) {
        window.location.href = 'logout.php';
    }
});
</script>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include __DIR__ . '/../db/db_admin.php';

if (!isset($_SESSION['username'])) {
    $_SESSION['error'] = "로그인이 필요합니다.";
    header("Location: ../index.php");
    exit;
}

if (!isset($db)) {
    $_SESSION['error'] = "데이터베이스 연결에 실패했습니다.";
    error_log("DB connection failed: db_admin.php not properly included or PDO not initialized");
    header("Location: ../index.php");
    exit;
}

$search = $_GET['search'] ?? '';
$secure_mode = $_SESSION['secure_mode'] ?? 'off';
$level = $_GET['level'] ?? '2';
$results = [];
$errors = [];
$success = '';
$stmt = null;

// 미션 정보 설정
$mission_title = '숨겨진 게시글 열람';
$mission_goal = '검색창에 SQL 인젝션을 입력하여 숨겨진 게시글(<code>is_hidden = 1</code>)을 열람하세요.';
$mission_hint = "' __ ___ _____ ______ id, title, content ____ posts WHERE _________ = 1 ;-- -";
$answer_hint = "숨겨진 게시글의 제목 또는 내용을 입력하세요.";

if ($level === '3') {
    $mission_title = 'DB 구조 유출';
    $mission_goal = '검색창에 SQL 인젝션을 입력하여 데이터베이스 테이블의 종류를 알아 내세요.';
    $mission_hint = "' __ ___ _____ ______ null, table_name, null ____ information_schema.tables WHERE table_schema = DATABASE() -- -";
    $answer_hint = "유출된 테이블 이름 중 하나를 입력하세요.";
} elseif ($level === '4') {
    $mission_title = '비밀번호 탈취';
    $mission_goal = '검색창에 SQL 인젝션을 입력하여 사용자 아이디와 비밀번호를 탈취하세요.';
    $mission_hint = "' __ ___ _____ ______ null, name, passwd ____ _________ ;-- -";
    $answer_hint = "관리자 계정의 비밀번호를 입력하세요.";
} elseif ($level === '5') {
    $mission_title = '플래그 획득';
    $mission_goal = '검색창에 SQL 인젝션을 입력하여 비밀 플래그를 획득하세요.';
    $mission_hint = "' __ ___ _____ ______ null, id, flag ____ flags WHERE is_secret = TRUE ;-- -";
    $answer_hint = "획득한 플래그 값을 입력하세요.";
}  elseif ($level === '6') {
    $mission_title = '게시글 추가';
    $mission_goal = '검색창에 SQL 인젝션을 입력하여 원하는 게시글을 추가하세요.';
    $mission_hint = "'; INSERT INTO posts (title, content, is_hidden) VALUES ('_____________', '____________', FALSE); -- -";
    $answer_hint = "쿼리를 실행한 뒤 검색 창을 지운 뒤 다시 검색 한 후 직접 삽입한 게시물의 제목을 입력 하세요.";
}

// SQL 검색 로직
$pre_count = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
error_log("Before search - Posts count: $pre_count");
$stmt = null;
try {
    if ($secure_mode === 'on') {
        $sql = "SELECT id, title, content FROM posts WHERE title LIKE :search AND is_hidden = FALSE";
        $stmt = $db->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
    } else{
            if ($level === '6' && $search !== '') {
            $sql = "SELECT id, title, content FROM posts WHERE title LIKE '%$search%' AND is_hidden = FALSE";
            error_log("Executing SQL for Level 6 (query): $sql");
            $stmt = $db->query($sql); // exec() 대신 query() 사용
            if ($stmt) {
                $stmt->closeCursor();
            }

            $stmt = $db->query("SELECT id, title, content FROM posts WHERE is_hidden = FALSE");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = $row;
            }

        }else if ($search === '') {
            $sql = "SELECT id, title, content FROM posts WHERE is_hidden = FALSE";
            $stmt = $db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = $row;
            }
        }else {
            // SQL 인젝션 허용 (학습 목적)
            $sql = "SELECT id, title, content FROM posts WHERE title LIKE '%$search%' AND is_hidden = FALSE";
            error_log("Executing SQL: $sql");
            try {
                $stmt = $db->query($sql);
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $results[] = $row;
                        error_log("Result row: " . json_encode($row));
                    }
                }
            } catch (PDOException $e) {
                // 구문 오류 무시, 결과가 있으면 출력
                error_log("Non-critical SQL error: " . $e->getMessage());
                if ($stmt && $stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $results[] = $row;
                        error_log("Result row: " . json_encode($row));
                    }
                } else {
                    $errors[] = "검색 오류: " . h($e->getMessage());
                }
            }
        }
    error_log("Cleared levels: " . json_encode($_SESSION['cleared_levels']));
    error_log("Result count: " . count($results));
}
} catch (PDOException $e) {
    $errors[] = "검색 오류: " . h($e->getMessage());
    error_log("Search error: " . $e->getMessage());
}
    finally {
    if ($stmt) {
        $stmt->closeCursor();
    }
}

$post_count = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
if ($post_count > $pre_count) {
    error_log("WARNING: Posts table increased from $pre_count to $post_count after search");
}
$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>SQL Injection Training - Level <?php echo h($level); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/main_page.css">
    <style>
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.45);
            display: none; align-items: center; justify-content: center; z-index: 9999;
        }
        .modal-backdrop.show { display: flex; }
        .custom-alert {
            background: #fff; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.35);
            width: min(90%, 520px); max-height: 80vh; overflow: auto; padding: 18px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans KR", "Apple SD Gothic Neo", sans-serif;
        }
        .custom-alert h3 { margin: 0 0 10px 0; font-size: 18px; }
        .alert-content {
            white-space: pre-wrap; user-select: text; -webkit-user-select: text;
            margin-bottom: 12px; line-height: 1.4;
        }
        .alert-actions { display:flex; justify-content: flex-end; gap: 8px; }
        .btn {
            padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc;
            background: #f5f5f5; cursor: pointer; font-size: 14px;
        }
        .btn-primary { background: #0b79ff; color: white; border-color: #066fe0; }
        .btn-green { background: #28a745; color: white; border-color: #218838; }
        .errors { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>SQL Injection Training — <span class="muted">Level <?php echo h($level); ?>: <?php echo h($mission_title); ?></span></h1>
    <p>
        <a href="../index.php?logout=true" class="link">로그아웃</a> | 
        <a href="../index.php" class="link">메인 페이지</a>
    </p>

    <div class="card">
        <div class="mission-info">
            <strong>미션 목표:</strong> <?php echo $mission_goal; ?><br>
            힌트: <code><?php echo h($mission_hint); ?></code><br>
            현재 보안 모드: <span class="<?php echo $secure_mode === 'on' ? 'secure-on' : 'secure-off'; ?>">
                <?php echo $secure_mode === 'on' ? 'ON (보안)' : 'OFF (취약)'; ?>
            </span>
        </div>
    </div>

    <div class="card">
        <form method="get" class="search-form">
            <input type="hidden" name="level" value="<?php echo h($level); ?>">
            <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="게시글 제목 검색 (SQL 인젝션 시도)" aria-label="게시글 제목 검색">
            <button type="submit">검색</button>
        </form>
    </div>

    <div class="answer-form card" style="margin-top:12px">
        <h3>Level <?php echo h($level); ?> 정답 제출</h3>
        <p>힌트: <?php echo h($answer_hint); ?></p>
        
        <?php /* if ($errors): */ ?>
        <?php /* endif; */ ?>
        <?php /* if ($success): */ ?>
        <?php /* endif; */ ?>
        
        <form id="answerForm" method="post" class="inline" style="gap:8px">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
            <input type="hidden" name="level" value="<?php echo h($level); ?>">
            <input type="text" name="answer" placeholder="정답 입력" required aria-label="Level <?php echo h($level); ?> 정답 입력">
            <button type="submit">제출</button>
        </form>
    </div>

    <div class="card results">
        <h3>검색 결과 (<?php echo count($results); ?>)</h3>
        <?php if (count($results) === 0): ?>
            <p>게시글이 없습니다.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>제목</th>
                        <th>내용</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo h($row['title']); ?></td>
                            <td><?php echo h($row['content']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="secure-mode">
        현재 보안 모드: 
        <span class="<?php echo $secure_mode === 'on' ? 'secure-on' : 'secure-off'; ?>">
            <?php echo $secure_mode === 'on' ? 'ON (Prepared Statement)' : 'OFF (취약)'; ?>
        </span>
    </div>
</div>

<div id="modalBackdrop" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="custom-alert" role="document" aria-labelledby="alertTitle">
        <h3 id="alertTitle">알림</h3>
        <div id="alertContent" class="alert-content" tabindex="0"></div>
        <div id="alertActions" class="alert-actions">
            <button id="okBtn" class="btn btn-primary">확인</button>
        </div>
    </div>
</div>
<script>
    /* 모달 함수 정의 */
    const backdrop = document.getElementById('modalBackdrop');
    const contentEl = document.getElementById('alertContent');
    const titleEl = document.getElementById('alertTitle');
    const actionsEl = document.getElementById('alertActions');
    const currentLevel = parseInt("<?php echo h($level); ?>");
    const secureMode = "<?php echo h($secure_mode); ?>";

    let currentModalKeyHandler = null;

    function hideCustomAlert() {
        backdrop.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
        document.querySelector('h1').focus(); 
        
        if (currentModalKeyHandler) {
            document.removeEventListener('keydown', currentModalKeyHandler);
            currentModalKeyHandler = null;
        }
    }

    function showCustomAlert(text, title = '알림', buttons = []) {
        titleEl.textContent = title;
        contentEl.innerHTML = text; 
        actionsEl.innerHTML = '';	

        if (currentModalKeyHandler) {
            document.removeEventListener('keydown', currentModalKeyHandler);
            currentModalKeyHandler = null;
        }

        if (buttons.length === 0) {
            const defaultOkBtn = document.createElement('button');
            defaultOkBtn.className = 'btn btn-primary';
            defaultOkBtn.textContent = '확인';
            defaultOkBtn.addEventListener('click', hideCustomAlert);
            actionsEl.appendChild(defaultOkBtn);
        } else {
            buttons.forEach(btn => {
                const button = document.createElement('button');
                button.textContent = btn.text;
                button.className = `btn ${btn.className || 'btn-primary'}`;
                button.addEventListener('click', () => {
                    hideCustomAlert();
                    if (btn.onClick) btn.onClick();
                });
                actionsEl.appendChild(button);
            });
        }

        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
        const firstFocusable = actionsEl.querySelector('button') || contentEl;
        firstFocusable.focus();
        
        currentModalKeyHandler = function(e) { if (e.key === 'Enter') { e.preventDefault(); hideCustomAlert(); } };
        document.addEventListener('keydown', currentModalKeyHandler);
    }
    
    backdrop.addEventListener('mousedown', (e) => { if (e.target === backdrop) hideCustomAlert(); }); // 백드롭 클릭으로 닫기
	
	document.getElementById('answerForm').addEventListener('submit', function(e) {
		e.preventDefault(); 
		const form = e.target;
		const formData = new FormData(form);

		fetch('../php/submit_answer.php', {	
			method: 'POST',
			body: formData
		})
		.then(response => {
			if (!response.ok) { return response.json().then(error => Promise.reject(error)); }
			return response.json();
		})
		.then(data => {
			if (data.success) {
				const nextLevel = currentLevel + 1;
				const buttons = [];

				if (currentLevel === 6) { // 🚩 Level 6
					buttons.push({
						text: '🏆 미션 완료! 🏆',
						className: 'btn-green',
						onClick: () => {	
							const absolutePath = 'success.php';	
							window.location.href = data.redirect || absolutePath;
						}
					});
					showCustomAlert(data.message,`🎉 최종 미션 클리어!`,	buttons);
					
				} else { // 🚩 Level 2, 3, 4,5
					if (nextLevel <= 6) { // 다음 레벨로 이동하는 버튼
						buttons.push({
							text: `다음 단계로 >>`,
							className: 'btn-green',
							onClick: () => { window.location.href = `main_page.php?level=${nextLevel}&secure=${secureMode}`; }
						});
					}
					
					buttons.push({ // 일반 닫기 버튼
						text: '닫기',
						className: 'btn-primary',
						onClick: () => { window.location.reload(); }
					});
					showCustomAlert(data.message, `✅ Level ${currentLevel} 클리어! 정답이 맞습니다.`, buttons);
				}
			} else { showCustomAlert(data.message || '정답이 아닙니다. 다시 시도해 보세요.', '❌ 정답 오류'); }
		})
		.catch(error => {
			console.error('Fetch error:', error);
			showCustomAlert(error.message || '서버 통신 오류가 발생했습니다. (CSRF 문제 또는 서버 응답 문제)', '❌ 오류');
		});
	});
</script>
</body>
</html>

<?php
session_start();
include __DIR__ . '/db/db_admin.php';

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    error_log("Session destroyed and PHPSESSID cookie removed");
    header("Location: index.php");
    exit;
}

if (isset($_GET['secure'])) {
    $_SESSION['secure_mode'] = ($_GET['secure'] === 'on') ? 'on' : 'off';
}
$secure_mode = $_SESSION['secure_mode'] ?? 'off';
$csrf = function_exists('csrf_token') ? csrf_token() : 'dummy_csrf_token';
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// 레벨 클리어 상태 초기화 (없으면 빈 배열)
$_SESSION['cleared_levels'] = $_SESSION['cleared_levels'] ?? [];
error_log("Session state: username=" . ($_SESSION['username'] ?? 'none') . ", is_admin=" . (isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 'none') . ", cleared_levels=" . json_encode($_SESSION['cleared_levels']));
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Injection Training</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="shortcut icon" href="sql.png" type="image/x-icon">
    <style>
        /* 모달 스타일 시작 */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal-backdrop.show { display: flex; }

        .custom-alert {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.35);
            width: min(90%, 520px);
            max-height: 80vh;
            overflow: auto;
            padding: 18px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans KR", "Apple SD Gothic Neo", sans-serif;
        }

        .custom-alert h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .alert-content {
            white-space: pre-wrap;
            user-select: text;
            -webkit-user-select: text;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .alert-actions {
            display:flex;
            justify-content: flex-end;
            gap: 8px; 
        }

        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #f5f5f5;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background: #0b79ff;
            color: white;
            border-color: #066fe0;
        }
        /* 모달 스타일 끝 */
        .mission-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        .success {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login__form" role="region" aria-labelledby="login-title">
            <h2 id="login-title" class="login_text">로그인 (Level 1)</h2>
            
            <?php if ($error): ?>
                <p class="error" role="alert"><?php echo h($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success" role="alert"><?php echo h($success); ?></p>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['username'])): ?>
                <div class="success">
                    로그인 성공! <br>어서 오세요 <?php echo h($_SESSION['username']); ?>
                </div>
            <?php else: ?>
                <form action="php/login.php" method="post" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                    <div class="form-group">
                        <label for="username">아이디:</label>
                        <input type="text" id="username" name="username" required aria-required="true">
                    </div>
                    <div class="form-group">
                        <label for="password">비밀번호:</label>
                        <input type="password" id="password" name="password" required aria-required="true">
                    </div>
                    <div class="button-container">
                        <input type="submit" value="로그인" class="submit">
                        <input type="button" value="회원가입" onclick="window.location.href='sign_up.html'" aria-label="회원가입 페이지로 이동">
                    </div>
                </form>
            <?php endif; ?>
            <div class="answer-form card" style="margin-top:12px">
                <h3>Level 1 정답 제출</h3>
                <p>힌트: 관리자 계정으로 로그인 후 얻은 관리자 이름 정보를 입력하세요.</p>
                
                <form action="php/submit_answer.php" method="post" class="inline" style="gap:8px">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                    <input type="hidden" name="level" value="1">
                    <input type="text" name="answer" placeholder="정답 입력" required aria-label="Level 1 정답 입력">
                    <button type="submit">제출</button>
                </form>
            </div>
        </div>

        <div class="mission-section" role="region" aria-labelledby="mission-title">
            <h2 id="mission-title">🔑 SQL Injection 미션</h2>
            <div class="missions">
                <div class="mission-card" 
                    onclick="startMission(1)" role="button" tabindex="0" aria-label="Level 1: 로그인 우회 미션 시작">
                    Level 1<br>로그인 우회
                </div>
                <div class="mission-card <?php echo isset($_SESSION['cleared_levels'][1]) ? '' : 'disabled'; ?>" 
                    onclick="startMission(2)" role="button" tabindex="<?php echo isset($_SESSION['cleared_levels'][1]) ? '0' : '-1'; ?>" 
                    aria-label="Level 2: 숨겨진 게시글 열람 미션 시작">
                    Level 2<br>숨겨진 게시글 열람
                </div>
                <div class="mission-card <?php echo isset($_SESSION['cleared_levels'][2]) ? '' : 'disabled'; ?>" 
                    onclick="startMission(3)" role="button" tabindex="<?php echo isset($_SESSION['cleared_levels'][2]) ? '0' : '-1'; ?>" 
                    aria-label="Level 3: DB 구조 유출 미션 시작">
                    Level 3<br>DB 구조 유출
                </div>
                <div class="mission-card <?php echo isset($_SESSION['cleared_levels'][3]) ? '' : 'disabled'; ?>" 
                    onclick="startMission(4)" role="button" tabindex="<?php echo isset($_SESSION['cleared_levels'][3]) ? '0' : '-1'; ?>" 
                    aria-label="Level 4: 비밀번호 탈취 미션 시작">
                    Level 4<br>비밀번호 탈취
                </div>
                <div class="mission-card <?php echo isset($_SESSION['cleared_levels'][4]) ? '' : 'disabled'; ?>" 
                    onclick="startMission(5)" role="button" tabindex="<?php echo isset($_SESSION['cleared_levels'][4]) ? '0' : '-1'; ?>" 
                    aria-label="Level 5: 플래그 획득 미션 시작">
                    Level 5<br>플래그 획득
                </div>
            </div>

            <div class="secure-toggle">
                <p>현재 보안 모드:
                    <span class="<?php echo $secure_mode === 'on' ? 'secure-on' : 'secure-off'; ?>">
                        <?php echo $secure_mode === 'on' ? 'ON (Prepared Statement)' : 'OFF (취약)'; ?>
                    </span>
                </p>
                <button onclick="toggleSecureMode()" aria-label="보안 모드 전환">
                    <?php echo $secure_mode === 'on' ? '보안 모드 해제' : '보안 모드 활성화'; ?>
                </button>
            </div>
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
        let currentModalKeyHandler = null;
        function hideCustomAlert() {
            backdrop.classList.remove('show');
            backdrop.setAttribute('aria-hidden', 'true');
            if (currentModalKeyHandler) {
                document.removeEventListener('keydown', currentModalKeyHandler);
                currentModalKeyHandler = null;
            }
        }
		
        function showCustomAlert(text, title = '알림', buttons = []) {
            titleEl.textContent = title;
            contentEl.textContent = text;
            actionsEl.innerHTML = ''; 
            
            // 이전 리스너 제거 (안전성 확보)
            if (currentModalKeyHandler) {
                document.removeEventListener('keydown', currentModalKeyHandler);
                currentModalKeyHandler = null;
            }

            if (buttons.length === 0) {
                // 기본 '확인' 버튼 추가
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
            currentModalKeyHandler = function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); 
                    hideCustomAlert(); 
                }
            };
            document.addEventListener('keydown', currentModalKeyHandler);
        }
        /* 미션 관련 함수 */
        function startMission(level) {
            const clearedLevels = <?php echo json_encode($_SESSION['cleared_levels'] ?? []); ?>;
            const secureMode = "<?php echo $secure_mode; ?>";
            
            if (level === 1) {
                document.getElementById('username')?.focus();
                showCustomAlert(
                    "아이디와 비밀번호 입력란에 SQL 인젝션(예: ' OR '1'='1' ;--)을 시도하여 관리자 계정으로 로그인하세요.",
                    'Level 1: 로그인 우회'
                );
            } else if (!clearedLevels[level - 1]) {
                showCustomAlert(`Level ${level - 1}을 먼저 클리어해야 Level ${level}에 접근할 수 있습니다!`, '미션 잠금');
            } else {
                window.location.href = `./html_sent/main_page.php?level=${level}&secure=${secureMode}`;
            }
        }

        function toggleSecureMode() {
            const next = "<?php echo $secure_mode; ?>" === 'on' ? 'off' : 'on';
            window.location.href = `index.php?secure=${next}`;
        }
    </script>
</body>
</html>
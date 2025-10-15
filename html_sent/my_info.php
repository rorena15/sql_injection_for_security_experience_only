<?php
session_start();

// 세션에서 사용자 아이디 가져오기
if (!isset($_SESSION['user_id'])) {
    die("세션에 아이디가 저장되어 있지 않습니다."); // 세션이 없는 경우 처리
}
$userId = $_SESSION['user_id'];

$servername = "localhost";
$username = "php";
$password = "tiger";
$dbname = "phpdb";

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("연결 실패: " . $conn->connect_error);
}

// 사용자 정보 조회 쿼리
$sql = "SELECT id, name, hint FROM user_info WHERE id='$userId'";
$result = $conn->query($sql);

$row = null; // 변수 초기화

if ($result->num_rows > 0) {
    // 결과가 있는 경우 테이블에 출력
    $row = $result->fetch_assoc(); // 데이터 할당
}

// 데이터베이스 연결 종료
$conn->close();


// 비밀번호 변경 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmedPassword = $_POST['confirm_password'];

    // 데이터베이스 연결
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("연결 실패: " . $conn->connect_error);
    }

    // 현재 비밀번호 확인
    $checkPasswordSql = "SELECT passwd FROM user_info WHERE id='$userId'";
    $result = $conn->query($checkPasswordSql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $storedPassword = $user['passwd'];

        if ($currentPassword === $storedPassword) {
            // 현재 비밀번호가 일치하는 경우, 새로운 비밀번호 업데이트
            if ($newPassword === $confirmedPassword) {
                $updateSql = "UPDATE user_info SET passwd='$newPassword' WHERE id='$userId'";
                if ($conn->query($updateSql) === TRUE) {
                    echo "<script>alert('비밀번호가 성공적으로 변경되었습니다.');</script>";
                } else {
                    echo "<script>alert('비밀번호 변경에 실패했습니다.');</script>";
                }
            } else {
                echo "<script>alert('새로운 비밀번호와 확인 비밀번호가 일치하지 않습니다.');</script>";
            }
        } else {
            echo "<script>alert('현재 비밀번호가 일치하지 않습니다.');</script>";
        }
    } else {
        echo "<script>alert('사용자 정보를 찾을 수 없습니다.');</script>";
    }

    // 데이터베이스 연결 종료
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내 정보</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login__form {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
        }

        td {
            background-color: #fff;
            color: #666;
        }

        .login__form input[type="text"],
        .login__form input[type="password"] {
            width: calc(100% - 24px); /* Adjusted width to account for padding */
            padding: 12px;
            margin-bottom: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .login__form input[class="submit"] {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            background-color: #4caf50;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[class]{
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            background-color: #4caf50;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login__form input[class="submit"]:hover {
            background-color: #45a049;
        }
        
        .change-password-form {
            display: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login__form">
        <h2>내 정보</h2>
        <table>
            <?php if ($row) : ?>
                <tr>
                    <th>아이디</th>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                </tr>
                <tr>
                    <th>이름</th>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                </tr>
                <tr>
                    <th>힌트</th>
                    <td><?php echo htmlspecialchars($row['hint']); ?></td>
                </tr>
            <?php else : ?>
                <tr><td colspan="2">사용자 정보가 없습니다.</td></tr>
            <?php endif; ?>
        </table><br>
                
        <!-- 비밀번호 변경 버튼 -->
        <button id="changePasswordBtn" class="submit" value="비밀번호 변경" onclick="toggleChangePassword()">비밀번호 변경</button>

        <!-- 비밀번호 변경 폼 -->
        <div class="change-password-form" id="changePasswordForm">
            <h3>비밀번호 변경</h3>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <label for="current_password">현재 비밀번호:</label><br>
                <input type="password" id="current_password" name="current_password" required><br>
                
                <label for="new_password">새로운 비밀번호:</label><br>
                <input type="password" id="new_password" name="new_password" required><br>
                
                <label for="confirm_password">새로운 비밀번호 확인:</label><br>
                <input type="password" id="confirm_password" name="confirm_password" required><br>
                
                <input type="submit" class="submit" value="비밀번호 변경">
            </form>
        </div>
        

        
        <form action="main_page.php">
            <input type="submit" class="submit" value="이전으로 돌아가기">
        </form>
    </div>
                
    <script>
        function toggleChangePassword() {
            var form = document.getElementById('changePasswordForm');
            form.classList.toggle('change-password-form');
        }
    </script>
</body>
</html>

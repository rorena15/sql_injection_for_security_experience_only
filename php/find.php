            <script>

            <?php
            $servername = "localhost";
            $username = "php";
            $password = "tiger";
            $dbname = "phpdb";

            $name = $_POST['name'];
            $hint = $_POST['hint'];

            $conn = new mysqli($servername, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("연결 실패: " . $conn->connect_error);
            }

            // 이름과 힌트를 기준으로 사용자 정보 조회
            $sql = "SELECT id FROM user_info WHERE name='$name' AND hint='$hint'";
            $result = $conn->query($sql);

            // 결과 확인 및 처리
            if ($result->num_rows > 0) {
                // 사용자 정보가 조회되면 아이디 및 비밀번호를 JavaScript 변수에 저장
                $row = $result->fetch_assoc();
                $id = htmlspecialchars($row['id']);
                echo "var id = '$id';";
                echo "var found = true;";
            } else {
                // 조회된 결과가 없는 경우 JavaScript 변수에 결과를 저장
                echo "var found = false;";
            }

            // 데이터베이스 연결 종료
            $conn->close();
            ?>
            // JavaScript로 결과를 처리하고 알림 창을 띄웁니다.
            if (found) {
                alert("아이디: " + id + "\n비밀번호는 보안상 찾을 수 없습니다. 비밀번호 변경 페이지로 이동합니다.");
                window.location.href = '../change_pw.html'; // 성공 후 비밀번호 변경 페이지로 이동
            } else {
                alert("일치하는 정보가 없습니다.");
                window.location.href = '../Find_id_pw.php'; // 실패 후 새로고침
            }
            </script>

            <!DOCTYPE html>
<html lang="ko">
<head>
    <link rel="shortcut icon" href="sql.png" type="image/x-icon">
</head>
<body>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
</body>
<footer></footer>
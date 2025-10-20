<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>아이디 및 비밀번호 찾기</title>
    <link rel="stylesheet" href="css/Find_id_pw.css">
    <link rel="shortcut icon" href="sql.png" type="image/x-icon">
    </head>
<body>
    <div class="login__form">
        <h2>아이디 찾기</h2>
        <form action="./php/find.php" method="post">
            <label for="name">이름 :</label>
            <input type="text" name="name" id="name" required><br>
            <label for="hint">힌트 :</label>
            <input type="text" name="hint" id="hint" required><br>
            <input type="submit" class="submit" value="아이디 찾기">
            <input type="button" class="submit" value="처음으로 돌아가기" onclick="goToIndexPage()">
        </form>
        <script>
        function goToIndexPage() {
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>
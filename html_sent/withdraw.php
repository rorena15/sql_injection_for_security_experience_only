<?php
session_start();

$servername = "localhost";
$username = "php";
$password = "tiger";
$dbname = "phpdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("연결 실패: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM user_info WHERE id='$user_id'";

if ($conn->query($sql) === TRUE) {
    echo "<script>alert('회원 탈퇴가 완료되었습니다.');</script>";


    $_SESSION = array();


    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }


    session_destroy();

    header("Location: ../index.php");
    exit();
} else {
    echo "오류: " . $sql . "<br>" . $conn->error;
}

$conn->close();

<?php
session_start();

$servername = "localhost";
$username = "php";
$password = "tiger";
$dbname = "phpdb";

// 폼에서 전송된 데이터 가져오기
$id = $_POST['id'];
$newPassword = $_POST['new_password'];
$confirmedPassword = $_POST['confirm_password'];

// 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("연결 실패: " . $conn->connect_error);
}

// 기존 비밀번호 가져오기
$sql = $conn->prepare("SELECT passwd FROM user_info WHERE id=?");
$sql->bind_param("s", $id);
$sql->execute();
$result = $sql->get_result();

if ($result === FALSE) {
    die("쿼리 실패: " . $conn->error);
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentPassword = $row['passwd'];
} else {
    echo "<script>alert('사용자를 찾을 수 없습니다.'); window.location.href = '../change_pw.html';</script>";
    $conn->close();
    exit();
}


function isValidPassword($password) {
    return preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}


if ($newPassword === $confirmedPassword) {

    if (isValidPassword($newPassword)) {

        if ($newPassword !== $currentPassword) {

            $updateSql = $conn->prepare("UPDATE user_info SET passwd=? WHERE id=?");
            $updateSql->bind_param("ss", $newPassword, $id);
            if ($updateSql->execute()) {
                echo "<script>alert('비밀번호가 성공적으로 변경되었습니다.'); window.location.href = '../index.php';</script>";
            } else {
                echo "<script>alert('비밀번호 변경에 실패했습니다. 다시 시도해 주세요.'); window.location.href = '../change_pw.html';</script>";
            }
        } else {

            echo "<script>alert('새로운 비밀번호가 기존 비밀번호와 동일합니다. 다른 비밀번호를 사용해 주세요.'); window.location.href = '../change_pw.html';</script>";
        }
    } else {

        echo "<script>alert('비밀번호는 최소 한개의 숫자와 영문자를 포함해야 합니다.'); window.location.href = '../change_pw.html';</script>";
    }
} else {

    echo "<script>alert('새로운 비밀번호와 확인 비밀번호가 일치하지 않습니다.'); window.location.href = '../change_pw.html';</script>";
}


$conn->close();


<?php

$my_id = $_REQUEST["id"];
$my_pw = $_REQUEST["pw"];
$my_hint = $_REQUEST["hint"];
$my_name = $_REQUEST["name"];

try {

    $db = new PDO("mysql:host=localhost;dbname=phpdb", "php", "tiger");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $stmt = $db->prepare("INSERT INTO user_info (id, passwd, hint, name) VALUES (:id, :pw, :hint, :name)");
    $stmt->bindParam(':id', $my_id);
    $stmt->bindParam(':pw', $my_pw);
    $stmt->bindParam(':hint', $my_hint);
    $stmt->bindParam(':name', $my_name);


    $stmt->execute();

    echo "<script>alert('회원가입이 완료 되었습니다.'); window.location.href='../index.php';</script>";
} catch (PDOException $e) {

    if ($e->errorInfo[1] == 1062) {
        echo "<script>alert('이미 사용 중인 아이디입니다. 다른 아이디를 사용해주세요.'); window.location.href='../sign_up.html';</script>";
    } else {

        echo "<script>alert('오류가 발생했습니다.'); window.location.href='../sign_up.html';</script>";
        error_log("Database Error: " . $e->getMessage());
    }
}

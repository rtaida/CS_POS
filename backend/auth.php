<?php
require_once 'database.php';
session_start();

if(isset($_POST['loginAuth'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }

    $checkSql = "SELECT * FROM users WHERE email = ? AND dateDeleted IS NULL";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if($row){
        if(password_verify($password, $row['password'])){
            $_SESSION['userID'] = $row['userID'];
            $_SESSION['fullName'] = $row['fullName'];
            $_SESSION['role'] = $row['role'];
            header("Location: ../frontend/pos.php");
            exit();
        }else{
            header("Location: ../index.php?invalid");
            exit();
        }
    }else{
        header("Location: ../index.php?invalid");
        exit();
    }
}
?>
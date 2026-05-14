<?php
require_once 'database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['categoryAuth'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    $categoryName = trim($_POST['categoryName']);
    $categoryDesc = trim($_POST['categoryDesc']);
    
    if(empty($categoryName)){
        header("Location: ../frontend/categories.php?error=categoryName_required");
        exit();
    }
    
    $checkQuery = "SELECT COUNT(*) as count FROM categories WHERE categoryName = ? AND dateDeleted IS NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if($row['count'] > 0){
        header("Location: ../frontend/categories.php?already");
        exit();
    }
    
    $insertQuery = "INSERT INTO categories (categoryName, categoryDesc) VALUES (?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ss", $categoryName, $categoryDesc);
    
    if($stmt->execute()){
        header("Location: ../frontend/categories.php?savedData");
        exit();
    } else {
        header("Location: ../frontend/categories.php?error");
        exit();
    }
}

if(isset($_POST['updateCategory'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    $categoryID = $_POST['categoryID'];
    $categoryName = trim($_POST['categoryName']);
    $categoryDesc = trim($_POST['categoryDesc']);
    
    $checkQuery = "SELECT COUNT(*) as count FROM categories WHERE categoryName = ? AND categoryID != ? AND dateDeleted IS NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("si", $categoryName, $categoryID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if($row['count'] > 0){
        header("Location: ../frontend/categories.php?already");
        exit();
    }
    
    $updateQuery = "UPDATE categories SET categoryName = ?, categoryDesc = ? WHERE categoryID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssi", $categoryName, $categoryDesc, $categoryID);
    
    if($stmt->execute()){
        header("Location: ../frontend/categories.php?updateData");
        exit();
    } else {
        header("Location: ../frontend/categories.php?error");
        exit();
    }
}

if(isset($_POST['DeleteCategory'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    date_default_timezone_set('Asia/Manila');
    $categoryID = $_POST['categoryID'];
    $dateDeleted = date('Y-m-d H:i:s');
    
    $query = "UPDATE categories SET dateDeleted = ? WHERE categoryID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $dateDeleted, $categoryID);
    
    if($stmt->execute()){
        header("Location: ../frontend/categories.php?deleteData");
        exit();
    }
}
?>
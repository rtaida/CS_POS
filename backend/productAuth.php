<?php
require_once 'database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['productAuth'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    $productCode = trim($_POST['productCode']);
    $productName = trim($_POST['productName']);
    $productDesc = trim($_POST['productDesc']);
    $categoryID = $_POST['categoryID'] ?: NULL;
    $price = $_POST['price'];
    $cost = $_POST['cost'];
    $stock = $_POST['stock'];
    $reorderLevel = $_POST['reorderLevel'];
    
    $query = mysqli_query($conn, "CALL AddProduct('$productCode', '$productName', '$productDesc', " . ($categoryID ? "'$categoryID'" : "NULL") . ", '$price', '$cost', '$stock', '$reorderLevel')");
    
    $result = mysqli_fetch_assoc($query);
    
    if($result['status'] == 'EXIST'){
        header("Location: ../frontend/products.php?already");
        exit();
    } elseif($result['status'] == 'SUCCESS'){
        header("Location: ../frontend/products.php?savedData");
        exit();
    } else {
        header("Location: ../frontend/products.php?error");
        exit();
    }
}

if(isset($_POST['updateProduct'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    $productID = $_POST['productID'];
    $productCode = trim($_POST['productCode']);
    $productName = trim($_POST['productName']);
    $productDesc = trim($_POST['productDesc']);
    $categoryID = $_POST['categoryID'] ?: NULL;
    $price = $_POST['price'];
    $cost = $_POST['cost'];
    $stock = $_POST['stock'];
    $reorderLevel = $_POST['reorderLevel'];
    
    $query = mysqli_query($conn, "CALL UpdateProduct('$productID', '$productCode', '$productName', '$productDesc', " . ($categoryID ? "'$categoryID'" : "NULL") . ", '$price', '$cost', '$stock', '$reorderLevel')");
    
    $result = mysqli_fetch_assoc($query);
    
    if($result['status'] == 'EXIST'){
        header("Location: ../frontend/products.php?already");
        exit();
    } elseif($result['status'] == 'NO_CHANGE'){
        header("Location: ../frontend/products.php?nothingChanged");
        exit();
    } else {
        header("Location: ../frontend/products.php?updateData");
        exit();
    }
}

if(isset($_POST['DeleteProduct'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    date_default_timezone_set('Asia/Manila');
    $productID = $_POST['productID'];
    $dateDeleted = date('Y-m-d H:i:s');
    
    $query = "UPDATE products SET dateDeleted = ? WHERE productID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $dateDeleted, $productID);
    
    if($stmt->execute()){
        header("Location: ../frontend/products.php?deleteData");
        exit();
    }
}
?>
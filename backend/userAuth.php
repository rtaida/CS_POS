<?php
require_once 'database.php';
session_start();


include_once dirname(__DIR__) . '/vendor/autoload.php';

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}


function sendPusherNotification($eventType, $data) {
    try {
        $options = array(
            'cluster' => 'ap1',
            'useTLS' => true
        );
        
        $pusher = new Pusher\Pusher(
            '3d5e91994ffcfa8ec0b5',  
            '809a72ce4d916761101d',   
            '2152150',                 
            $options
        );
        
        $pusher->trigger('my-channel', 'my-event', $data);
        return true;
    } catch (Exception $e) {
        error_log("Pusher error: " . $e->getMessage());
        return false;
    }
}


if(isset($_POST['userAuth'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../frontend/user.php?error=invalid_token");
        exit();
    }
    
    $fullName = trim(mysqli_real_escape_string($conn, $_POST['fullName']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];
    
    
    if(empty($fullName) || empty($username) || empty($email) || empty($password)){
        header("Location: ../frontend/user.php?error=missing_fields");
        exit();
    }
    
    if(strlen($password) < 6){
        header("Location: ../frontend/user.php?error=password_short");
        exit();
    }
    
    
    $checkQuery = "SELECT userID FROM users WHERE (username = '$username' OR email = '$email') AND dateDeleted IS NULL";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if(mysqli_num_rows($checkResult) > 0){
        header("Location: ../frontend/user.php?already");
        exit();
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (fullName, username, email, password) VALUES ('$fullName', '$username', '$email', '$hashedPassword')";
    
    if(mysqli_query($conn, $query)){
        $newUserId = mysqli_insert_id($conn);
        
        
        $notificationData = [
            'action' => 'add',
            'message' => 'New user has been added to the system',
            'fullName' => $fullName,
            'username' => $username,
            'email' => $email,
            'userID' => $newUserId,
            'timestamp' => date('Y-m-d H:i:s'),
            'triggered_by' => $_SESSION['fullName'] ?? 'Admin'
        ];
        sendPusherNotification('add', $notificationData);
        
        header("Location: ../frontend/user.php?savedData");
        exit();
    } else {
        header("Location: ../frontend/user.php?error=save_failed");
        exit();
    }
}


if(isset($_POST['updateUser'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../frontend/user.php?error=invalid_token");
        exit();
    }
    
    $userID = mysqli_real_escape_string($conn, $_POST['userID']);
    $fullName = trim(mysqli_real_escape_string($conn, $_POST['fullName']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    
    
    $oldUserQuery = "SELECT * FROM users WHERE userID = '$userID'";
    $oldUserResult = mysqli_query($conn, $oldUserQuery);
    $oldUser = mysqli_fetch_assoc($oldUserResult);
    
    
    $checkQuery = "SELECT userID FROM users WHERE (username = '$username' OR email = '$email') AND userID != '$userID' AND dateDeleted IS NULL";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if(mysqli_num_rows($checkResult) > 0){
        header("Location: ../frontend/user.php?already");
        exit();
    }
    
    $query = "UPDATE users SET fullName='$fullName', username='$username', email='$email' WHERE userID='$userID'";
    
    if(mysqli_query($conn, $query)){
        
        $changes = [];
        if($oldUser['fullName'] != $fullName) $changes[] = "Name: {$oldUser['fullName']} → {$fullName}";
        if($oldUser['username'] != $username) $changes[] = "Username: {$oldUser['username']} → {$username}";
        if($oldUser['email'] != $email) $changes[] = "Email: {$oldUser['email']} → {$email}";
        
        
        $notificationData = [
            'action' => 'update',
            'message' => 'User information has been updated',
            'fullName' => $fullName,
            'username' => $username,
            'email' => $email,
            'userID' => $userID,
            'changes' => $changes,
            'changed_by' => $_SESSION['fullName'] ?? 'Admin',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        sendPusherNotification('update', $notificationData);
        
        header("Location: ../frontend/user.php?updateData");
        exit();
    } else {
        header("Location: ../frontend/user.php?error=update_failed");
        exit();
    }
}


if(isset($_POST['DeleteUser'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../frontend/user.php?error=invalid_token");
        exit();
    }
    
    $userID = mysqli_real_escape_string($conn, $_POST['userID']);
    
    
    if($userID == $_SESSION['userID']){
        header("Location: ../frontend/user.php?error=cannot_delete_self");
        exit();
    }
    
    
    $userQuery = "SELECT fullName, username, email FROM users WHERE userID = '$userID'";
    $userResult = mysqli_query($conn, $userQuery);
    $userData = mysqli_fetch_assoc($userResult);
    
    date_default_timezone_set('Asia/Manila');
    $dateDeleted = date('Y-m-d H:i:s');
    
    $query = "UPDATE users SET dateDeleted = '$dateDeleted' WHERE userID = '$userID'";
    
    if(mysqli_query($conn, $query)){
        
        $notificationData = [
            'action' => 'delete',
            'message' => 'A user has been removed from the system',
            'fullName' => $userData['fullName'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'userID' => $userID,
            'deleted_by' => $_SESSION['fullName'] ?? 'Admin',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        sendPusherNotification('delete', $notificationData);
        
        header("Location: ../frontend/user.php?deleteData");
        exit();
    } else {
        header("Location: ../frontend/user.php?error=delete_failed");
        exit();
    }
}
?>
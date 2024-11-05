<?php
session_start();

// Include the database connection file
require("./conx/conx_login.php");

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Insert a new record into the audit_trail table
$action = "Log-Out";
$insert_audit_query = "INSERT INTO audit_trail (user_id, action) VALUES (:user_id, :action)";
$insert_audit_stmt = $conn->prepare($insert_audit_query);
$insert_audit_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$insert_audit_stmt->bindParam(':action', $action, PDO::PARAM_STR);
$insert_audit_stmt->execute();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit();

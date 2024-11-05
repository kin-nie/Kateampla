<?php
$servername = "localhost";
$username = "ktv_admin";
$password = "administrator";
$dbname = "kateampla";

try {
    $conx_admin = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conx_admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

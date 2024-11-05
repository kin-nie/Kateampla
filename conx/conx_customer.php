<?php
$servername = "localhost";
$username = "ktv_customer";
$password = "customer";
$dbname = "kateampla";

try {
    $conx_customer = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conx_customer->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

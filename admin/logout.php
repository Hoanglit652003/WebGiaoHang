<?php
require '../config/conn.php';
session_destroy();
header('Location: login.php');
exit;
?>
<?php
// pages/logout.php - User Logout

session_start();
session_destroy();
header('Location: index.php');
exit;
?>
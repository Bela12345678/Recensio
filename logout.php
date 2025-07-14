<?php
session_start();

$_SESSION = array();

session_destroy();

header('Location: landing.php');
exit();
?>

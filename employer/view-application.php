<?php
// filepath: c:\laragon\www\jobmatch_new\employer\view-application.php
// Redirect to the correct application details page
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
header("Location: application-details.php?id=$id");
exit();
?>
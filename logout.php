<?php
session_start();
session_destroy();
header("Location: panitia/index.php");
exit();
?>

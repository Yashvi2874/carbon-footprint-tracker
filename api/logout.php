<?php
session_start();
session_destroy();
header("Location: /mini-project/index.php");
exit();
?>

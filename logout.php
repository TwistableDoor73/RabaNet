<?php
setcookie("auth_token", "", time() - 3600, "/"); // Clear cookie
header("Location: login.php");
exit();

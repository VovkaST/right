<?php
session_start();
require("../const.php");
require($kernel.'functions.php');
exit_session();
header('location: ../index.php');
?>
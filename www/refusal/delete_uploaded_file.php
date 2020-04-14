<?php
$need_auth = 0;
require('d:/www.sites/const.php');
require($kernel.'functions.php');
session_start();
?>
<?php
if (unlink($_SESSION['dir_session'].$_SESSION["refusal"]["uploaded_file"])) {  echo '<div id="file_deleted">Файл удален!</div>';
  unset($_SESSION["refusal"]["uploaded_file"]);}
else {
  echo '<div class="error" id="error_file"><p>Ошибка удаления!</p><div>';}
?>
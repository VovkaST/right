<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
if (!empty($_POST["id"]) &&
    !empty($_POST["deleter"]) &&
    !empty($_POST["reason"])) {
	$deleter = $_POST["deleter"];
	$reason = $_POST["reason"];
  $delete_info = $_SESSION['user'].", id сеанса ".$_SESSION['activity_id'].", ".date('d.m.Y, H:i');
  $deleter = htmlspecialchars($deleter);
  $reason = htmlspecialchars($reason);
  require($kernel.'connection.php');
    UPDATE
      otkaz
    SET
      deleted = "1",
      delete_info = "'.$delete_info.'",
      delete_emp = "'.$deleter.'",
      delete_reason = "'.$reason.'"
    WHERE
      id = "'.$id.'"
  ') or die("Ошибка SQL: ".mysql_error());
else {
?>
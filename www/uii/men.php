<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require (KERNEL."connection_uii.php");
$query = '
	SELECT
		MAX(b.id) AS rap_id
	FROM
		journal a LEFT JOIN
			raport as b on a.id=b.journal_id
	WHERE
		a.id = "'.$_GET['men_id'].'"
';
$result = mysql_query($query) or die("Query failed: " . mysql_error());
$row = mysql_fetch_array($result);
$rap_id = $row['rap_id'];
?>
<!DOCTYPE html>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <title>Проверки</title>
</head>
  <frameset rows="20%,*">
    <frame name="men_fio" src="men_fio.php?men_id=<?= $_GET['men_id'] ?>&ovd_id=<?= $_GET['ovd_id'] ?>">
    <frameset cols="12%,75%">
      <frame name="men_date" src="men_date.php?men_id=<?= $_GET['men_id'] ?>&ovd_id=<?= $_GET['ovd_id'] ?>">
      <frame name="men_txt" src="men_txt.php?men_id=<?= $_GET['men_id'] ?>&ovd_id=<?= $_GET['ovd_id'] ?>&rap_id=<?= $rap_id ?>">
    </frameset>
  </frameset>
</html>
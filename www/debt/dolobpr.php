<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!empty($_GET["raion"])) {
  $a=$_GET["raion"];
} else {
  header('obv.php');
}
require ($kernel."connection_debt.php");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset=utf-8>
  <title>Долги обвинит. и прекр. <?=$a?></title>
  <link rel="icon" href="<?= $img ?>favicon.ico" type="<?= $img ?>vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= $css ?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?= $css ?>head.css">
  <script type="text/javascript" src="js/hltable.js"></script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$debt?>">Сведения о количестве Уголовных Дел, по которым в текущем году вынесены обвинительные заключения/акты и (или) постановления о прекращении. Сведения о количестве отказных материалов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<?=$a?>
</div>
<table id="myTable" border="1" bgcolor="#F0FFB0" width="100%">
  <tr align="center">
    <td colspan="2">Обвинительные</td>
  </tr>
<?php
$r = mysql_query('
	SELECT
		*
	FROM
		dolob
	WHERE
		raion = "'.$a.'"
') or die("SQL error: ".mysql_error());
$color = "";
for ($i=0; $i<mysql_num_rows($r); $i++):
	$f=mysql_fetch_array($r);
	$color<>"F0E0FF" ? $color="F0E0FF" : $color="DFDFDF"; ?>
	<tr bgcolor="<?=$color?>">
    <td align="left" width="55"><?=$f["mesac"]?></td>
    <td><?=$f["nazv"]?></td>
	</tr>
<?php endfor; ?>
</table>
<p></p>
<table id="myTable" border="1" bgcolor="#F0FFB0" width="100%">
  <tr align="center">
    <td colspan="2">Прекращенные</td>
  </tr>
<?php
$r = mysql_query('
	SELECT
		*
	FROM
		dolpr
	WHERE
		raion = "'.$a.'"
') or die(mysql_error());
// Выводим таблицу
$color = "";
for ($i=0; $i<mysql_num_rows($r); $i++):
	$f=mysql_fetch_array($r);
	$color<>"F0E0FF" ? $color="F0E0FF" : $color="DFDFDF"; ?>
  <tr bgcolor="<?=$color?>">
    <td align="left" width="55"><?=$f["mesac"]?></td>
    <td><?=$f["nazv"]?></td>
	</tr>
<?php endfor; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
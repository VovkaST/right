<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET['district'])){
	header('Location: district.php');
	die();
} else {
	$district = $_GET['district'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Проверка граждан прибывших с Украины</title>
  <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?=$css?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?=$css?>head.css">
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
require ($kernel."connection_ukr.php"); ?>
<div class="breadcrumbs">
<a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="districtMessageFaceList.php">Всего проверено лиц, не состоящих на учете УФМС (в инициативном порядке)</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<?php
$stmt = mysql_query('
	SELECT
		e.адресПроверки,
		count(distinct d.faceId) as cnt
	FROM 
		message as e
	JOIN 
    report as d ON 
      d.messageId = e.id AND 
      e.department = "'.$district.'"
	JOIN 
    face as c ON 
      c.id = d.faceId
	GROUP BY 
		e.адресПроверки
'); ?>
<h2><?=$district?></h2>
<table cellpadding="3" width="70%" border="1" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th>Адрес</th>
    <th width="60px">Кол-во<br/>лиц</th>
  </tr>
<?php while($row = mysql_fetch_assoc($stmt)): ?>
	<tr>
    <td><?=$row['адресПроверки']?></td>
    <td align="center"><a href="<?=$addr?>ukr/peopleMessageFaceList.php?address=<?=$row['адресПроверки']?>&district=<?=$district?>"><?=$row['cnt']?></a></td>
  </tr>
<?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
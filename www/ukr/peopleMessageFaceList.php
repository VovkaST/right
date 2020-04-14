<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_REQUEST['address']) || !isset($_REQUEST['district'])){	
	header('Location: district.php');
	die();
} else {
	$address = $_REQUEST['address'];
	$district = $_REQUEST['district'];
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
?>
<div class="breadcrumbs">
<a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href=<?=$accounting?>>Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href=<?=$ukr?>>Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="districtMessageFaceList.php">Всего проверено лиц, не состоящих на учете УФМС (в инициативном порядке)</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="addressMessageFaceList.php?district=<?=$district?>"><?=$district?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<?php 
require ($kernel."connection_ukr.php");
$stmt = mysql_query('
	SELECT
		c.id,
		c.ФамилияКириллица,
		c.ИмяКириллица,
		c.ОтчествоКириллица,
		c.ДатаРождения,
		count(distinct d.messageId) as cnt
	FROM 
		message e
	JOIN 
    report as d ON 
      d.messageId = e.id AND 
      e.адресПроверки = "'.$address.'"
	JOIN 
    face as c ON 
      c.id = d.faceId
	GROUP BY 
		c.id,
		c.ФамилияКириллица,
		c.ИмяКириллица,
		c.ОтчествоКириллица,
		c.ДатаРождения
'); ?>
<h2><?=$address?></h2>
<table width="70%" border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th>Имя</th>
    <th width="60px">Кол-во<br/>проверок</th>
  </tr>
<?php
while($row = mysql_fetch_assoc($stmt)): ?>
	<tr>
    <td><?=$row['ФамилияКириллица']." ".$row['ИмяКириллица']." ".$row['ОтчествоКириллица']." ".date('d.m.Y', strtotime($row['ДатаРождения']))?></td>
    <td align="center"><a href="<?=$addr?>ukr/messageView.php?district=<?=$_REQUEST['district']?>&address=<?=$address?>&face_id=<?=$row['id']?>"><?=$row['cnt']?></a></td>
  </tr>
<?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
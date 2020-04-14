<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
if (!isset($_REQUEST['address'])){	
	header('Location: /ukr/district.php');
	die();
} else {
	$address = $_REQUEST['address'];
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
require ($kernel."connection_ukr.php");
?>
<div class="breadcrumbs">
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/districtFaceList.php">Проверено лиц, состоящих на учете УФМС</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="addressFaceList.php?district=<?=$_GET['district']?>"><?=$_GET['district']?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<?php
$stmt = mysql_query('
	SELECT 
		Строка
	FROM 
		address
	WHERE 
		id = '.$address.'
');
$addressInfo = mysql_fetch_assoc($stmt);
$stmt = mysql_query('
	SELECT
		c.id,
		c.ФамилияКириллица,
		c.ИмяКириллица,
		c.ОтчествоКириллица,
		c.ДатаРождения,
		count(distinct d.macroreportId) as cnt
	FROM (
		SELECT
			MAX(a.id) as id,
			a.faceId,
			MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет
		FROM 
			notice as a
		WHERE 
			a.ДатаУбытия IS NULL
		GROUP BY 
			a.faceId
	) a 
  JOIN 
    notice as b ON 
      a.id = b.id 
	JOIN 
    face as c ON 
      b.faceId = c.id AND 
      c.Гражданство = "UKR"
	JOIN 
    address as e ON 
      b.addrPrebId = e.id
	JOIN 
    address as f ON 
      b.addrSideId = f.id
	JOIN 
    report as d ON 
      b.faceId = d.faceId
	WHERE 
		e.id = "'.$address.'"
	GROUP BY 
		c.id,
		c.ФамилияКириллица,
		c.ИмяКириллица,
		c.ОтчествоКириллица,
		c.ДатаРождения
');
?>
<h2><?=$addressInfo['Строка']?></h2>
<table cellpadding="3" border="1" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="400px">Лицо</th>
    <th width="60px">Кол-во<br/>проверок</th>
  </tr>
<?php while($row = mysql_fetch_assoc($stmt)): ?>
	<tr>
    <td><?=$row['ФамилияКириллица']." ".$row['ИмяКириллица']." ".$row['ОтчествоКириллица']." ".date('d.m.Y', strtotime($row['ДатаРождения']))?></td>
    <td align="center"><a href="<?=$addr?>ukr/macroreportView.php?district=<?=$_GET['district']?>&address=<?=$address?>&face_id=<?=$row['id']?>"><?=$row['cnt']?></a></td>
  </tr>
<?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
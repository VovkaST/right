<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php

if (!isset($_GET['district'])){
	header('Location: '.$addr.'ukr/district.php');
	die();
} else {
	$district = $_GET['district'];
}
require ($kernel."connection_ukr.php");
$stmt = mysql_query('
	SELECT
		e.id, 
    e.Строка, 
    count(distinct b.faceId) as cnt
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
	) as a 
  JOIN 
    notice as b ON 
      a.id = b.id 
	JOIN 
    face as c ON 
      b.faceId = c.id AND 
      c.Гражданство = "UKR"
	JOIN address as e ON 
    b.addrPrebId = e.id
	JOIN 
    report as d ON 
      b.faceId = d.faceId
	WHERE 
		e.Район = "'.$district.'"
	GROUP BY 
		e.id, e.Строка
	ORDER BY 
		e.Строка
');
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
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/districtFaceList.php">Проверено лиц, состоящих на учете УФМС</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<h2><?=$district?></h2>
<table cellpadding="3" width="70%" border="1" rules="all" class="result_table" align="center">
  <tr class="table_head">
    <th width="90%" align="center">Адрес</th>
    <th width="10%" align="center">Кол-во<br/>лиц</th>
  </tr>
<?php while($row = mysql_fetch_assoc($stmt)): ?>
	<tr>
    <td><?=$row['Строка']?></td>
    <td align="center"><a href="<?=$addr?>ukr/peopleFaceList.php?district=<?=$district?>&address=<?=$row['id']?>"><?=$row['cnt']?></a></td>
  </tr>
<?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require ($kernel."connection_ukr.php");
$stmt = mysql_query('
	SELECT
		e.Район, 
    count(distinct b.faceId) as cnt
	FROM (
		SELECT
			MAX(a.id) as id,
			a.faceId,
			MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет
		FROM 
			notice a
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
	JOIN 
    address as e ON 
      b.addrPrebId = e.id
	LEFT JOIN 
    report as d ON 
      b.faceId = d.faceId
	WHERE 
		d.id IS NULL
	GROUP BY 
		e.Район
	ORDER BY 
		orderId
');
$cnt = 0;
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан, прибывших с Украины</title>
  <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?=$css?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?=$css?>head.css"></head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Требуется проверка (код 3 и 4)
</div>
<table border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="300px">Район</th>
    <th width="60px">Кол-во<br/>проверок</th>
  </tr>
<?php while($row = mysql_fetch_assoc($stmt)):
	$cnt += $row['cnt']; ?>
	<tr>
    <td><?=$row['Район']?></td>
    <td align="center"><a href="<?=$addr?>ukr/address.php?district=<?=$row['Район']?>"><?=$row['cnt']?></a></td>
  </tr>
<?php endwhile; ?>
  <tr style="background: #F4F4F4;">
    <th>ВСЕГО</th>
    <th><?=$cnt?></th>
  </tr>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
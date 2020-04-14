<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (isset($_GET['mode'])){
	$mode = $_GET['mode'];
} else {
	$mode = 0;
}
switch ($mode){
	case 1:
		$sql = ' and truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(c.ДатаРождения, \'%Y%m\')) / 12, 0) >= 18';
		$mode_type = " (взрослые)";
	break;
	
	case 2:
		$sql = ' and truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(c.ДатаРождения, \'%Y%m\')) / 12, 0) between 7 and 17';
		$mode_type = " (несовершеннолетние)";
	break;
	
	case 3:
		$sql = ' and truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(c.ДатаРождения, \'%Y%m\')) / 12, 0) < 7';
		$mode_type = " (малолетние)";
	break;
	
	default:
		$sql = '';
		$mode_type = "";
}
require ($kernel."connection_ukr.php");
$stmt = mysql_query('
	SELECT
		e.Район, 
    count(distinct b.faceId) as cnt
	FROM (
    SELECT
      MAX(a.id) as id,
      a.faceId,
      MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет,
      MAX(a.СрокПребыванияДо) as СрокПребыванияДо,
      MAX(a.ДатаУбытия) as ДатаУбытия
    FROM 
      notice a
    WHERE 
      a.ДатаУбытия IS NULL
    GROUP BY 
      a.faceId
		) as a 
  JOIN 
    notice as b ON 
      a.id = b.id AND 
      a.ДатаУбытия IS NULL AND 
      a.СрокПребыванияДо >= current_date()
	JOIN 
    face as c ON 
      b.faceId = c.id AND 
      c.Гражданство = "UKR" '.$sql.'
	JOIN 
    address as e ON 
      b.addrPrebId = e.id
	GROUP BY 
		e.Район
	ORDER BY 
		orderId
');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Проверка граждан, прибывших с Украины</title>
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
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан прибывших с Украины&nbsp</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Находящиеся в Кировской области<?=$mode_type?>
</div>
<table border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="300px" align="center">Район</th>
    <th width="60px" align="center">Кол-во<br/>проверок</th>
  </tr>
<?php
$cnt = 0;
while($row = mysql_fetch_assoc($stmt)):
	$cnt += $row['cnt']; ?>
	<tr>
    <td><?=$row['Район']?></td>
    <?php if ($row['cnt'] == "0"): ?>
      <td align="center"><?=$row['cnt']?></td>
    <?php else: ?>
      <td align="center"><a href="<?=$addr?>ukr/addressCurrent.php?district=<?=$row['Район']?>&mode=<?=$mode?>"><?=$row['cnt']?></a></td>
    <?php endif; ?>
  </tr>
<?php endwhile;?>
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
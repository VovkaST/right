<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET['district'])){
	header('Location: '.$addr.'ukr/district.php');
	die();
} else {
	$district = $_GET['district'];
}
require ($kernel."connection_ukr.php");
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
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан прибывших с Украины&nbsp</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/districtTotal.php?mode=<?=$_GET['mode']?>">Всего прибыло лиц (УФМС, с начала текущего года)<?=$mode_type?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<?=$district?>
</div>
<?php
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
			notice a
		GROUP BY 
			a.faceId
	) as a
	JOIN 
    notice as b ON 
      a.id = b.id
	JOIN 
    face as c ON 
      b.faceId = c.id AND 
      c.Гражданство = "UKR" AND 
      year(a.ДатаПостановкиНаУчет) = year(current_date()) '.$sql.'
	JOIN 
    address as e ON 
      b.addrPrebId = e.id
	WHERE 
		e.Район = "'.$district.'"
	GROUP BY 
		e.id, 
		e.Строка
	ORDER BY 
		e.Строка
');

$cnt = 0;?>
<table width="70%" cellpadding="3" border="1" rules="all" align="center" class="result_table">
  <tr align="center" class="table_head">
    <th>Адрес</th>
    <th width="60px">Кол-во<br/>лиц</th>
  </tr>
<?php
while($row = mysql_fetch_assoc($stmt)):
	$cnt += $row['cnt']; ?>
	<tr>
    <td><?=$row['Строка']?></td>
    <?php if ($row['cnt'] == "0"): ?>
      <td align="center"><?=$row['cnt']?></td>
    <?php else: ?>
      <td align="center"><a href="<?=$addr?>ukr/peopleTotal.php?district=<?=$district?>&address=<?=$row['id']?>&mode=<?=$mode?>"><?=$row['cnt']?></a></td>
    <?php endif; ?>
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
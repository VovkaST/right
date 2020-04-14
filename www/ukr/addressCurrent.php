<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET['district'])){
	header('Location: '.$addr.'ukr/districtCurrent.php');
	die();
} else {
	$district = $_GET['district'];
}
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
    e.id, 
    e.Строка, 
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
				a.ДатаУбытия is null
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
	WHERE 
		e.Район = "'.$district.'"
	GROUP BY 
		e.id, e.Строка
	ORDER BY 
		e.Строка
');
$cnt = 0;
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
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан прибывших с Украины&nbsp</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/districtCurrent.php?mode=<?=$mode?>">Находящиеся в Кировской области <?=$mode_type?></a>
</div>
<h2><?=$district?></h2>
<table cellpadding="3" width="70%" border="1" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th>Адрес</th>
    <th width="60px">Кол-во<br/>лиц</th>
  </tr>
<?php while($row = mysql_fetch_assoc($stmt)):
	$cnt += $row['cnt']; ?>
	<tr>
    <td><?=$row['Строка']?></td>
    <td align="center"><a href="<?=$addr?>ukr/peopleCurrent.php?address=<?=$row['id']?>&mode=<?=$mode?>&district=<?=$district?>"><?=$row['cnt']?></a></td>
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
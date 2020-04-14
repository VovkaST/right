<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET['address'])){
	header('Location: '.$addr.'ukr/districtTotal.php');
	die();
} else {
	$address = $_GET['address'];
}
require ($kernel."connection_ukr.php");
$loc = mysql_query('
	SELECT
		строка
	FROM
		address
	WHERE
		id = '.$address.'
');
while ($row = mysql_fetch_array($loc)) {
	$address_str = $row['строка'];
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
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан прибывших с Украины&nbsp</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/districtTotal.php?mode=<?=$_GET['mode']?>">Всего прибыло лиц (УФМС, с начала текущего года)<?=$mode_type?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/addressTotal.php?district=<?=$_GET['district']?>&mode=<?=$mode?>"><?=$_GET['district']?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<h2><?=$address_str?></h2>
<?php
$stmt = mysql_query('
  SELECT DISTINCT
    c.ФамилияКириллица,
    c.ИмяКириллица,
    c.ОтчествоКириллица,
    c.ДатаРождения
  FROM (SELECT
      MAX(a.id) as id,
      a.faceId,
      MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет
    FROM 
      notice as a
    GROUP BY 
      a.faceId) as a
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
    e.id = "'.$address.'"
  ORDER BY
    c.ФамилияКириллица,
    c.ИмяКириллица,
    c.ОтчествоКириллица,
    c.ДатаРождения
');
$cnt = 0; ?>
<table cellpadding="3" width="70%" align="center" border="1" rules="all" class="result_table">
  <tr class="table_head">
    <th width="5%">№<br/>п/п</th>
    <th width="28%">Фамилия</th>
    <th width="28%">Имя</th>
    <th width="28%">Отчество</th>
    <th width="15%">Дата<br/>рождения</th>
  </tr>
<?php
  while($row = mysql_fetch_assoc($stmt)):?>
	<tr>
    <td align="center"><?=++$cnt?></td>
    <td><?=$row['ФамилияКириллица']?></td>
    <td><?=$row['ИмяКириллица']?></td>
    <td><?=$row['ОтчествоКириллица']?></td>
    <td align="center"><?=date('d.m.Y', strtotime($row['ДатаРождения']))?></td>
  </tr>
<?php endwhile;?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Всего проверено лиц, не состоящих на учете УФМС (в инициативном порядке)</title>
  <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?=$css?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?=$css?>head.css">
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
require ($kernel."connection_ukr.php");?>
<div class="breadcrumbs">
<a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Всего проверено лиц, не состоящих на учете УФМС (в инициативном порядке)
</div>
<?php
require ("districtList.php");
$join = array();
$c = 0;
foreach ($districtList as $district){
	if ($district){
		$join[] = 'SELECT "'.$district.'" as dep, "'.$c.'" as orderId';
		$c++;
	}
}
$join = implode(' UNION ', $join);

$stmt = mysql_query('
	SELECT
		f.dep, 
		f.orderId,
		count(distinct d.faceId) as cnt
	FROM 
		(' . $join . ') f
	LEFT JOIN 
    message as e ON 
      e.department = f.dep
	LEFT JOIN 
    report as d ON 
      d.messageId = e.id
	LEFT JOIN 
    face as c ON 
      c.id = d.faceId
	GROUP BY 
		f.dep, f.orderId
	ORDER BY 
		f.orderId
');?>
<table align="center" cellpadding="3" border="1" rules="all" class="result_table">
  <tr class="table_head">
    <th width="300px">Район</th>
    <th width="60px">Кол-во<br/>проверок</th>
  </tr>
<?php while($row = mysql_fetch_assoc($stmt)): ?>
	<tr>
    <td><?=$row['dep']?></td>
    <?php if($row['cnt'] > 0): ?>
      <td align="center"><a href="<?=$addr?>ukr/addressMessageFaceList.php?district=<?=$row['dep']?>"><?=$row['cnt']?></a></td>
    <?php else: ?>
      <td align="center"><?=$row['cnt']?></td>
    <?php endif; ?>
  </tr>
<?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
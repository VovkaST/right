<?php 
$need_auth=0;
require($_SERVER['DOCUMENT_ROOT']."/sessions.php");
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset=utf-8>
 <title>Статистика обв. и отк.</title>
 <link rel="icon" href="<?= $img ?>favicon.ico" type="<?= $img ?>vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= $css ?>main.css">
 <link rel="stylesheet" href="<?= $css ?>new.css">
 <link rel="stylesheet" href="<?= $css ?>head.css">
 <script type="text/javascript" src="js/hltable.js"></script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= $index ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $accounting ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Сведения о количестве Уголовных Дел, по которым в текущем году вынесены обвинительные заключения/акты и (или) постановления о прекращении. Сведения о количестве отказных материалов
</div>
<table id="myTable" border="1" align="center" bgcolor="#F0FFB0">
  <tr align="center">
    <td rowspan="3" width="200px">Район</td>
    <td colspan="4">УМВД (обвинит. и прекращ.)</td>
    <td colspan="4">СУ СК (обвинит. и прекращ.)</td>
    <td colspan="4">Отказные</td>
  </tr>
  <tr align="center">
    <td rowspan="2" width="50px">ИЦ</td>
    <td colspan="3">ООРИ</td>
    <td rowspan="2" width="50px">ИЦ</td>
    <td colspan="3">ООРИ</td>
    <td rowspan="2" width="50px">СУ СК</td>
    <td colspan="3">ООРИ</td>
  </tr>
  <tr align="center">
    <td width="50px">Напр</td>
    <td width="50px">%, напр</td>
    <td width="50px">Ненапр</td>
    <td width="50px">Напр</td>
    <td width="50px">%, напр</td>
    <td width="50px">Ненапр</td>
    <td width="50px">Напр</td>
    <td width="50px">%, напр</td>
    <td width="50px">Ненапр</td>
  </tr>
<?php
require ($kernel."connection_debt.php");
# готовим и выполняем запрос к БД
$query = "";
$r = mysql_query("
	SELECT
		*
	FROM
		obvin
;") or die("SQL error: ".mysql_error());
// Выводим таблицу
$color = "";
for ($i=0; $i<mysql_num_rows($r); $i++):
	$f=mysql_fetch_array($r);
	$color<>"F0E0FF" ? $color="F0E0FF" : $color="DFDFDF"; ?>
	<tr align="center" bgcolor="<?=$color?>">
    <?php
    $f["vsicp"]<>0 ? $w=$f["vsicp"] : $w="-";
    $f["vscorip"]<>0 ? $w1=$f["vscorip"] : $w1="-";
    $f["vsic"]<>0 ? $z=round($f["vscoriopoz"]/$f["vsic"]*100,"2") : $z="-";
    $f["vsic"]<>0 ? $z1=round($f["vsic"]-$f["vscoriopoz"]) : $z1="-";
    $f["vsicp"]<>0 ? $z2=round($f["vscorip"]/$f["vsicp"]*100,"2") : $z2="-";
    $f["vsicp"]<>0 ? $z3=round($f["vsicp"]-$f["vscorip"]) : $z3="-";
    $f["pric"]<>0 ? $z4=round($f["prcori"]/$f["pric"]*100,"2") : $z4="-";
    $f["pric"]<>0 ? $z5=round($f["pric"]-$f["prcori"]) : $z5="-";
    $f["otkourd"]<>0 ? $z6=round($f["otkcori"]/$f["otkourd"]*100,"2") : $z6="-";
    $f["otkourd"]>$f["otkcori"] ? $z7=round($f["otkourd"]-$f["otkcori"]) : $z7="-";
    $raion = str_replace("?", "", $f["raion"]); ?>
    <td align="left"><?=$raion?></td>
    <td><?=$f["vsic"]?></td>
    <td><?=$f["vscoriopoz"]?></td>
    <td><?=$z?></td>
    <?php if ($z1 > 0):?>
      <td bgcolor="#F0FFB0"><a href="dolobpr.php?raion=<?=$raion?>"><b><?=$z1?></b></a></td>
    <?php else:?>
      <td bgcolor="#F0FFB0"><?=$z1?></td>
    <?php endif;?>
    <td><?=$w?></td>
    <td><?=$w1?></td>
    <td><?=$z2?></td>
    <?php if ($z3 > 0):?>
      <td bgcolor="#F0FFB0"><a href="dolobprs.php?raion=<?=$raion?>"><b><?=$z3?></b></a></td>
    <?php else:?>
      <td bgcolor="#F0FFB0"><?=$z3?></td>
    <?php endif;?>
    <td><?=$f["otkourd"]?></td>
    <td><?=$f["otkcori"]?></td>
    <td><?=$z6?></td>
    <td bgcolor="#F0FFB0"><?=$z7?></td>
	</tr>
<?php endfor; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

<script type="text/javascript">
 	highlightTableRows("myTable","","clickedRow",false);
</script>
</body>
</html>
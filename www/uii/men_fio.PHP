<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require (KERNEL."connection_uii.php");
$result = mysql_query('
  SELECT 
    *
	FROM
		journal a
	WHERE
		a.id = '.$_GET["men_id"]
) or die("Query failed : " . mysql_error());
$row = mysql_fetch_array($result);
$ADR_FAKT = strlen($row["ADR_FAKT"])>2 ? $row["ADR_FAKT"] : "там же" ;
$ADR_FAKT = str_ireplace("Кировская область, ", "", $ADR_FAKT);
$ADR_REG = strlen($row["ADR_REG"])>2 ? $row["ADR_REG"] : "по прож." ;
$ADR_REG = str_ireplace("Кировская область, ", "", $ADR_REG);
if ((strlen($row["ADR_REG"])<3) and (strlen($row["ADR_FAKT"])<3)) {
	$ADR_REG ="нет данных";
	$ADR_FAK ="нет данных";
};
$dat_post_uch = DATE("d.m.Y", strtotime($row["dat_post_uch"])) ? DATE("d.m.Y", strtotime($row["dat_post_uch"])) : "нет данных" ;
$obazannost = strlen($row["obazannost"])>2 ? $row["obazannost"] : "нет" ;
$ogranichenia = strlen($row["ogranichenia"])>2 ? $row["ogranichenia"] : "нет" ;
$obazannost = str_ireplace(", ,", ", ", $obazannost);
$ogranichenia = str_ireplace(", ,", ", ", $ogranichenia);
?>
<!DOCTYPE html>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <link rel="stylesheet" href="css/link.css">
</head>
<body bgcolor="#F0FFB9">
<table class="men_fio" align="center">
 <tr>
  <td width="15%" align="left">
   <a href="count.php?ovd_id=<?= $_GET["ovd_id"] ?>&mode=1" target="_top">к ФИО</a>
  </td>
  <td>
   <b><?= $row["fam"] ?> <?= $row["im"] ?> <?= $row["otch"] ?> <?= $row["datroj"]  ?> г.р.</b>
  </td>
  <td width="10%">
   <a href="index.php" target="_top">На главную</a>
  </td>
 </tr>
 <tr>
  <td colspan="3">
    Уроженец: <b><?= $row["gor_rai_reg"] ?> <?=  $row["nas_p_reg"] ?></b>, 
    рег-ия: <b><?= $ADR_REG ?></b>, 
    прож.: <b><?= $ADR_FAKT ?></b>, 
    № дела - <b><?= $row["num_delo"] ?></b>. 
    На учете с <b><?= $dat_post_uch ?></b> г. 
    Категория: <b><?= $row["kat_uch"] ?></b>. 
    Основание: <b><?= $row["osnov_uch"] ?></b>. 
    За совершение преступления, предусмотренного <b>ст.<?= $row["st_uk"] ?></b> УК РФ.
  </td>
 </tr>
 <tr>
  <td colspan="3">
   Обязанности: <b><?= $obazannost ?></b>
  </td>
 </tr>
 <tr>
  <td colspan="3">
   Ограничения: <b><?= $ogranichenia ?></b>
  </td>
 </tr>
</table>
</body>
</html>
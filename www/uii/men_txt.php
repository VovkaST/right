<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require (KERNEL."connection_uii.php");
$query = mysql_query('
	SELECT
    j.`fam`, r.`faktproj`, r.`skemproj`, r.`uslovij_proj`, r.`mrab`, r.`m_rab_xar`,
    r.`v_vid`, r.`sosedi`, r.`mproj_sosedi`, r.`harakteristika`, r.`v_ogran`, 
    r.`svazi`, r.`mobila`, r.`avto`, r.`gos_num`, r.`Inoe`, r.`dolj`, r.`zvan`, r.`sotr`,
    GROUP_CONCAT(
      DISTINCT
        DATE_FORMAT(rd.`check_date`, "%d.%m.%Y"), " около ", rd.`check_time`, ".00"
      ORDER BY
        rd.`check_date`
      SEPARATOR
        ", "
    ) as `check_date`
  FROM
    journal as j 
  LEFT JOIN
    raport as r ON 
      j.id = r.journal_id
    JOIN
      `raport_date` as rd ON
        rd.`raport_id` = r.`id`
  WHERE
    r.id = "'.$_GET["rap_id"].'"
') or die(mysql_error());
$row = mysql_fetch_array($query);
$faktproj = strlen($row["faktproj"])>2 ? $row["faktproj"] : "не введено !!!" ;
$skemproj = strlen($row["skemproj"])>2 ? $row["skemproj"] : "не введено !!!" ;
?>
<!DOCTYPE html>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <link rel="stylesheet" href="css/men_date.css">
</head>
<body>
<table rules="all" cellpadding="5" class="men_txt">
  <tr>
    <td><b>Проверялся(-ась)</b>: <?= $row["check_date"] ?></td>
  </tr>
</table>
<p>При проверке установлено, что <b><?= $row["fam"] ?></b> фактически проживает по адресу: <b><?= $faktproj ?></b> совместно c <b><?= $skemproj ?></b>.</p>
<p>Условия проживания: <b><?= $row["uslovij_proj"] ?></b>.</p>
<p>Место работы: <b><?= $row["mrab"] ?></b>.</p>
<p>По месту работы характеризуется: <b><?= $row["m_rab_xar"] ?></b>.</p>
<p>Внешний вид (особые приметы) подучетного лица: <b><?= $row["v_vid"] ?></b>.</p>
<p>Со слов членов семьи (родственников) и соседей - <b><?= $row["sosedi"] ?></b>, проживающих по адресу: <b><?= $row["mproj_sosedi"] ?></b>
, в быту <b><?= $row["fam"] ?></b> характеризуется  (склонность к употреблению спиртных напитков):<b><?= $row["harakteristika"] ?></b> </p>
<p>Возложенные обязанности/ограничения (выполняет в полном объеме, выполняет частично, не выполняет): <b><?= $row["v_ogran"] ?></b>.</p>
<p>Связи лица (круг общения): <b><?= $row["svazi"] ?></b>.</p>
<p>Мобильный телефон: <b><?= $row["mobila"] ?></b>.</p>
<p>Передвигается на автомашине: <b><?= $row["avto"] ?></b>, гос.№ <b><?= $row["gos_num"] ?></b>.</p>
<p>ИНОЕ: <b><?= $row["Inoe"] ?></b>.</p>
<p>
<p> проверил <b><?= $row["dolj"] ?>, <?= $row["zvan"] ?>, <?= $row["sotr"] ?></b></p>
</body>
</html>
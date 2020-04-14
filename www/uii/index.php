<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>ЛИЦА, состоящие на учете в УИИ</title>
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
</head>
<body>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Лица, состоящие на учете в УИИ
</div>
<table cellpadding="3" border="1" rules="all" class="result_table">
  <tr class="table_head">
   <th width="3%">Код OВД</th>
   <th width="30%">ОВД</th>
   <th width="35%">УИИ</th>
   <th width="10%">Всего на учете</th>
   <th width="10%">На проверку</th>
  </tr>
<?php
require (KERNEL."connection_uii.php");
$result = mysql_query("
  SELECT
    a.`ovd_id`,
    a.`ovd`,
    a.`uin`,
    count(a.`id`) as col_summ,
    count(a.`id`)-COUNT(IF(fl = 0, 1, null)) as pr
  FROM
    journal as a 
  LEFT JOIN
    (
      SELECT
        r.`journal_id`,
        ADDDATE(MAX(DATE(rd.`check_date`)), INTERVAL 90 day) < CURRENT_DATE() as fl
      FROM
        `raport` as r
      JOIN
        `raport_date` as rd ON
          rd.`raport_id` = r.`id`
      GROUP BY
        r.`journal_id`
    ) as b ON 
      a.`id` = b.`journal_id`
  WHERE 
    a.`VKOLDUCH` > 0
  GROUP BY 
    a.`ovd_id`
") or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_array($result)):?>
	<tr>
    <td align="center"><?=$row["ovd_id"]?></td>
    <td><?=$row["ovd"]?></td>
    <td><?=$row["uin"]?></td>
    <td align="center">
      <a href="count.php?mode=1&ovd_id=<?=$row["ovd_id"]?>&p=1"><?=$row["col_summ"]?></a>
    </td>
    <td align="center">
      <a href="count.php?mode=2&ovd_id=<?=$row["ovd_id"]?>&p=1"><?=$row["pr"]?></a>
    </td>
	</tr>
<?php
endwhile;?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
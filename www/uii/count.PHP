<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
<?php
require (KERNEL."connection_uii.php");
$ovd_id = isset($_GET["ovd_id"]) ? $_GET["ovd_id"] : 0;
$mode = isset($_GET["mode"]) ? $_GET["mode"] : 0;
if (!$mode || !$ovd_id) {
	header("Location: ".UII);
}
$query = "
	SELECT
		ovd,
	 	uin
	FROM
		journal
	WHERE
		OVD_Id=".$ovd_id."
	GROUP BY
		ovd_id
";
$result1 = @mysql_query($query) or die("Query failed : ".mysql_error());
while ($row = mysql_fetch_array($result1)) {	$ovd_result = $row["uin"];
}
?>
  <title>Подучетные лица <?= $ovd_result ?></title>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
if ($mode == 1) {$check = " (всего на учете)";
$query = '
  SELECT
    a.id,
    a.fam as fam,
    a.im as im,
    a.otch as otch,
    a.datroj  as datroj,
    DATE_FORMAT(a.dat_post_uch, "%d.%m.%y") as dat_post_uch,
    a.num_delo as delo_num,
    a.ADR_FAKT as adr_proj,
    a.ADR_REG as adr_reg,
    kol_pr,
    DATE_FORMAT(max_dat, "%d.%m.%y")as max_dat
  FROM
    journal as a 
  LEFT JOIN
      (
        SELECT
          r.journal_id,
          MAX(rd.`check_date`) max_dat,
          COUNT(r.journal_id = j.id) as kol_pr
        FROM
          journal as j
        JOIN
          raport as r ON
            j.id=r.journal_id
          JOIN
            `raport_date` as rd ON
              rd.`raport_id` = r.`id`
        WHERE
          j.VKOLDUCH > 0 AND 
          j.ovd_id = '.$ovd_id.'
        GROUP BY
          j.fam, j.im, j.otch, j.datroj
      ) as b ON 
        a.id = b.journal_id
  WHERE
    a.VKOLDUCH > 0 AND 
    ovd_id = '.$ovd_id.'
  GROUP BY   
    a.ITEMPERSON
  ORDER BY
    a.fam, a.im
';
}
elseif ($mode == 2) {$check = " (на проверку)";$query = '
  SELECT
    a.id,
    a.fam as fam,
    a.im as im,
    a.otch as otch,
    a.datroj  as datroj,
    DATE_FORMAT(a.dat_post_uch, "%d.%m.%y") as dat_post_uch,
    a.num_delo as delo_num,
    a.ADR_FAKT as adr_proj,
    a.ADR_REG as adr_reg,
    kol_pr,
    DATE_FORMAT(max_dat, "%d.%m.%y")as max_dat
  FROM
    journal as a left JOIN
      (
        SELECT
          r.journal_id,
          MAX(rd.`check_date`) max_dat,
          COUNT(r.journal_id = j.id) as kol_pr,
          ADDDATE(MAX(DATE(rd.`check_date`)), INTERVAL 90 day) < CURRENT_DATE() as fl
        FROM
          journal as j
        JOIN
          raport as r ON
            j.id=r.journal_id
          JOIN
            `raport_date` as rd ON
              rd.`raport_id` = r.`id`
        WHERE
          j.VKOLDUCH > 0 and 
          j.ovd_id = '.$ovd_id.'
        GROUP BY
          j.fam, j.im, j.otch, j.datroj
      ) as b on a.id=b.journal_id
  WHERE
    a.VKOLDUCH > 0 AND 
    ovd_id = '.$ovd_id.' AND
    (b.journal_id IS NULL OR 
     b.fl = 1)
  GROUP BY
    a.fam, a.im, a.otch, a.datroj
  ORDER BY
    adr_proj
';
}
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= UII ?>">Лица, состоящие на учете в УИИ</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Лица, состоящие на учете в <?= $ovd_result.$check ?>
</div>
<table border="1" rules="all" class="result_table">
<tr class="table_head">
 <th width="4%">№<br>п/п</th>
 <th width="32%">Лицо</th>
 <th width="6%">На учете с</th>
 <th width="3%">№ дела</th>
 <th style="max-width: 270px">Адрес регистрации</th>
 <th style="max-width: 270px">Адрес проживания</th>
 <th width="2%">Кол-во проверок</th>
 <th colspan="2">Последняя проверка</th>
</tr>
<?php
$kol_rec = mysql_num_rows(mysql_query($query));
$result = mysql_query($query);
$n = 1;
while ($row = mysql_fetch_array($result)) :
	$adr_reg = $row["adr_reg"];
	$adr_proj = $row["adr_proj"];
	if (strstr($adr_reg, "имеет")) {		$adr_reg = "";	}
	$adr_reg = str_ireplace("Кировская область, ", "", $adr_reg);
	$adr_reg = str_ireplace("кировская область, ", "", $adr_reg);
	if (stristr($adr_proj, "имеет")) {		$adr_proj = "";	}
	$adr_proj = str_ireplace("Кировская область, ", "", $adr_proj);
	$adr_proj = str_ireplace("кировская область, ", "", $adr_proj);?>
	<tr>
    <form action="add_raport.php?mode=<?=$mode?>&man_id=<?=$row["id"]?>"" name="raport<?=$row["id"]?>" method="POST">
      <input type="hidden" name="ovd_id" value="<?=$ovd_id?>"/>
      <input type="hidden" name="man_id" value="<?=$row["id"]?>"/>
      <td align="center"><?=$n?>.</td>
      <td><?=$row["fam"]." ".$row["im"]." ".$row["otch"]." ".$row["datroj"]?></td>
      <td align="center"><?=$row["dat_post_uch"]?></td>
      <td align="center"><?=$row["delo_num"]?></td>
      <td><?=$adr_reg?></td>
      <td><?=$adr_proj?></td>
      <td align="center"><a href="men.php?ovd_id=<?=$ovd_id?>&men_id=<?=$row["id"]?>"><?=$row["kol_pr"]?></a></td>
      <td align="center" width="60px" class="link_service"><?=$row["max_dat"]?></td>
      <td class="update_link">
        <input type="image" src="<?= IMG ?>plus.png" class="add_report"/>
      </td>
    </form>
	</tr>
<?php
  $n++;
endwhile;
?>
</table>
<span class="result_count">Результатов: <?=$kol_rec;?></span>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
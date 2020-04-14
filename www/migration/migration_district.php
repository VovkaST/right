<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан на соблюдение миграционного законодательства</title>
 <link rel="icon" href="<?= $img ?>favicon.ico" type="<?= $img ?>vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= $css ?>main.css">
 <link rel="stylesheet" href="<?= $css ?>new.css">
 <link rel="stylesheet" href="<?= $css ?>head.css">
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
require ($kernel."connection_ukr.php");
if (isset($_GET['mode'])) {
	switch($_GET['mode']) {
		case(1):
			$add_query = "WHERE (not_ukr.leaved = 0 OR not_ukr.leaved IS NULL) AND
                (
                 (not_ukr.CheckDate IS NULL AND not_ukr.LeaveDate IS NULL) OR
                 (not_ukr.LeaveDate IS NULL AND (not_ukr.RegDate + INTERVAL 3 month < current_date()) AND not_ukr.CheckDate + INTERVAL 2 week < current_date())
                 )";
			$mode = "&mode=".$_GET['mode'];
			$mode_type = "(на проверку)";
		break;
		case(2):
			$add_query = "WHERE not_ukr.CheckDate IS NOT NULL";
			$mode = "&mode=".$_GET['mode'];
			$mode_type = "(проверено)";
		break;
	}
}
else {
	$add_query = "";
	$mode = "";
	$mode_type = "(всего зарегистрировано)";
}
if (isset($_GET['district'])) $district_id = $_GET['district'];
$reg = mysql_query("
	SELECT
		a.id as adr_id,
		not_ukr.district,
		a.Строка as str,
		COUNT(DISTINCT not_ukr.face_id) as cnt
	FROM
		(
		SELECT
			f.id as face_id,
			MAX(mr.datpr) as CheckDate,
			mri.leaved,
			n.ДатаПостановкиНаУчет as RegDate,
			MAX(n.ДатаУбытия) as LeaveDate,
			n.addrPrebId,
			a.КЛАДР,
			a.OrderId,
			a.Район as district
		FROM
			notice as n
		JOIN
			face as f 
				ON f.id = n.faceId AND
				f.Гражданство <> 'UKR' AND
				year(n.ДатаПостановкиНаУчет) = year(current_date())
		LEFT JOIN
			migration_report_info as mri 
				ON mri.man_id = n.faceId
		LEFT JOIN
			migration_report as mr 
				ON mr.id = mri.report_id AND
                    mr.deleted = '0'
		LEFT JOIN
			address as a
				ON a.id = n.addrPrebId
		GROUP BY 
			n.faceId
		) as not_ukr
	JOIN
		address a ON a.id = not_ukr.addrPrebId AND 	a.orderId = $district_id
    $add_query
  GROUP BY
		a.id
	ORDER BY
		a.КЛАДР, a.Строка
");
$sql_district = mysql_query("
	SELECT
		Район
	FROM
		address
	WHERE
		orderId = $district_id
	GROUP BY
		orderId
");
while ($district = mysql_fetch_row($sql_district)) {
	 $district_str = $district[0];
}
?>
<div class="breadcrumbs">
  <a href="<?= $index ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $accounting ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $migration ?>">Проверка граждан на соблюдение миграционного законодательства (текущий год) <?= $mode_type ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<h2><?= $district_str ?></h2>
<table border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="600px" align="center">Адрес</th>
    <th width="80px" align="center">Всего<br/>зарег-но</th>
  </tr>
<?php
$cnt_reg = 0;
while ($row = mysql_fetch_assoc($reg)) :
$cnt_reg += $row['cnt'];
if (isset($_GET['mode'])) {
	switch($_GET['mode']) {
		case(1):
			$link = "migration_check.php?adr_id=".$row['adr_id'].$mode;
		break;
		case(2):
			$link = "migration_list.php?adr_id=".$row['adr_id'].$mode;
		break;
	}
}
else {$link = "migration_list.php?adr_id=".$row['adr_id'];}?>
  <tr>
    <td><?= $row['str'] ?></td>
    <td align="center">
      <a href="<?= $link ?>"><?= $row['cnt'] ?></a>
    </td>
  </tr>
<?php endwhile; ?>
  <tr style="background: #F4F4F4;">
    <th>ВСЕГО</th>
    <th><?= $cnt_reg ?></th>
  </tr>
</table>
<?php require ($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
</body>
</html>
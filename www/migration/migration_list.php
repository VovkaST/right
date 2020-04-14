<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
if (isset($_GET['adr_id'])) {
	if (isset($_GET['mode']) && !$_GET['mode'] == 2) {
		header('Location: migration.php');
	}
}
else {header('Location: migration.php');}
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
			$add_query = "WHERE	(not_ukr.CheckDate IS NULL OR not_ukr.RegDate + INTERVAL 3 month <= current_date()) AND leaveDate IS NULL";
			$mode = "&mode=".$_GET['mode'];
			$mode_type = "(на проверку)";
		break;
		case(2):
			$mode = "&mode=".$_GET['mode'];
			$mode_type = "(проверено)";
			$add_query = "WHERE
				not_ukr.CheckDate IS NOT NULL OR
				(not_ukr.RegDate + INTERVAL 3 month < current_date() AND not_ukr.CheckDate + INTERVAL 2 week > current_date())";
		break;
	}
}
else {
	$add_query = "";
	$mode = "";
	$mode_type = "(всего зарегистрировано)";
}
if (isset($_GET['adr_id'])) $adr_id = $_GET['adr_id'];
$reg = mysql_query("
	SELECT
        face_id,
		f.ФамилияКириллица as face_sn,
		f.ИмяКириллица as face_n,
		f.ОтчествоКириллица as face_fn,
		f.ДатаРождения as face_bd,
        ArriveDate,
        term,
		leaveDate,
		(SELECT 
			COUNT(DISTINCT datpr, vrpr) 
		FROM
			migration_report mr
        LEFT JOIN
        	migration_report_info mri ON mri.report_id = mr.id
		WHERE
			mri.man_id = not_ukr.face_id AND
                    mr.deleted = '0'
		) as cnt
	FROM
		(SELECT
			f.id as face_id,
			MAX(mr.datpr) as CheckDate,
			mri.leaved,
			n.ДатаПостановкиНаУчет as RegDate,
			MAX(n.ДатаУбытия) as LeaveDate,
            n.ДатаПрибытия as ArriveDate,
            n.СрокПребыванияДо as term,
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
		address a ON a.id = not_ukr.addrPrebId AND
		a.id = '$adr_id'
	JOIN
		face f ON f.id = not_ukr.face_id
	$add_query
	ORDER BY
		leaveDate, term, face_sn, face_n, face_fn
");
$sql_district = mysql_query("
	SELECT
		orderId,
		Район,
		Строка
	FROM
		address
	WHERE
		id = '$adr_id'
");
while ($district = mysql_fetch_row($sql_district)) {
	$district_id = $district[0];
	$district_str = $district[1];
	$address_str = $district[2];
}
?>
<div class="breadcrumbs">
  <a href="<?= $index ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $accounting ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;</a><a href="<?= $migration ?>">Проверка граждан на соблюдение миграционного законодательства (текущий год) <?= $mode_type ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="migration_district.php?district=<?= $district_id ?>"><?= $district_str ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<h2><?= $address_str ?></h2>
<table border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="30px" align="center">№<br/>п/п</th>
    <th width="150px" align="center">Фамилия</th>
    <th width="150px" align="center">Имя</th>
    <th width="150px" align="center">Отчество</th>
    <th width="80px" align="center">Дата рождения</th>
    <th width="5px" align="center"></th>
    <th width="80px" align="center">Дата прибытия</th>
    <th width="80px" align="center">Срок пребывания</th>
    <th width="80px" align="center">Дата убытия</th>
    <th width="50px" align="center">Кол-во<br/>проверок</th>
  </tr>
<?php
$cnt = 0;
while ($row = mysql_fetch_assoc($reg)) :
    $style_red = $style = "";
    if (isset($row['face_bd'])) {$face_bd = date('d.m.Y', strtotime($row['face_bd']));} else {$face_bd = "";}
    if (isset($row['ArriveDate'])) {$arriveDate = date('d.m.Y', strtotime($row['ArriveDate']));} else {$arriveDate = "";}
    if (isset($row['term'])) {
        $term = date('d.m.Y', strtotime($row['term']));
        if ($row['term'] < date('Y-m-d')) {
            $style_red = 'style="color: red;"';
        }
    } 
    else {
        $term = "";
    }
    if (isset($row['leaveDate'])) {
        $leaveDate = date('d.m.Y', strtotime($row['leaveDate'])); 
        $style = 'style="color: #9E9E9E;"';
    } 
    else {
        $leaveDate = "";
    }
    ?>
      <tr <?= $style.$style_red ?>>
        <th><?= ++$cnt ?>.</th>
        <td><?= $row['face_sn'] ?></td>
        <td><?= $row['face_n'] ?></td>
        <td><?= $row['face_fn'] ?></td>
        <td align="center"><?= $face_bd ?></td>
        <td></td>
        <td align="center"><?= $arriveDate ?></td>
        <td align="center"><?= $term ?></td>
        <td align="center"><?= $leaveDate ?></td>
        <?php if ($row['cnt']) : ?>
          <td align="center"><a href="migration_report_list.php?adr_id=<?= $adr_id ?>&face_id=<?= $row['face_id'] ?>"><?= $row['cnt'] ?></a></td>
        <?php else : ?>
          <td align="center"><?= $row['cnt'] ?></td>
        <?php endif; ?>
      </tr>
    <?php endwhile; ?>

</table>
<?php require ($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
</body>
</html>
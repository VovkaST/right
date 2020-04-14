<?php
$need_auth = 0;
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
?>
<div class="breadcrumbs">
<a href="<?= $index ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $accounting ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Проверка граждан на соблюдение миграционного законодательства (текущий год)
</div>
<table border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="300px" align="center">Район</th>
    <th width="80px" align="center">Всего<br/>зарег-но</th>
    <th width="80px" align="center">На<br/>проверку</th>
    <th width="80px" align="center">Проверено</th>
  </tr>
<?php
$reg = mysql_query("
	SELECT
		orderId,
		district,
		count(DISTINCT not_ukr.face_id) as reg,
		count(DISTINCT IF((not_ukr.leaved = 0 OR not_ukr.leaved IS NULL) AND
			(
			 (not_ukr.CheckDate IS NULL AND not_ukr.LeaveDate IS NULL) OR
			 (not_ukr.LeaveDate IS NULL AND (not_ukr.RegDate + INTERVAL 3 month < current_date()) AND not_ukr.CheckDate + INTERVAL 2 week < current_date())
			 ),
			not_ukr.face_id, NULL)
		) as on_check,
		count(DISTINCT IF(not_ukr.CheckDate IS NOT NULL OR
			(not_ukr.RegDate + INTERVAL 3 month < current_date() AND not_ukr.CheckDate + INTERVAL 2 week > current_date())
			, not_ukr.face_id, NULL)
		) as checked
	FROM
		(SELECT
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
	GROUP BY
		not_ukr.district
	ORDER BY
		not_ukr.КЛАДР
");
$cnt_reg = $cnt_on_check = $cnt_checked = 0;
while ($row = mysql_fetch_assoc($reg)) :
$cnt_reg += $row['reg'];
$cnt_on_check += $row['on_check'];
$cnt_checked += $row['checked'];
?>
  <tr>
    <td><?php echo $row['district'] ?></td>
    <td align="center">
      <?php if($row['reg'] == 0):?>
        <?= $row['reg'] ?>
      <?php else: ?>
        <a href="migration_district.php?district=<?= $row['orderId'] ?>"><?= $row['reg'] ?></a>
      <?php endif; ?>
    </td>
    <td align="center">
      <?php if($row['on_check'] == 0):?>
        <?= $row['on_check'] ?>
      <?php else: ?>
        <a href="migration_district.php?district=<?= $row['orderId'] ?>&mode=1"><?= $row['on_check'] ?></a>
      <?php endif; ?>
    </td>
    <td align="center">
      <?php if($row['checked'] == 0):?>
        <?= $row['checked'] ?>
      <?php else: ?>
        <a href="migration_district.php?district=<?= $row['orderId'] ?>&mode=2"><?= $row['checked'] ?></a>
      <?php endif; ?>
    </td>
  </tr>
<?php endwhile; ?>
  <tr style="background: #F4F4F4;">
    <th>ВСЕГО</th>
    <th><?= $cnt_reg ?></th>
    <th><?= $cnt_on_check ?></th>
    <th><?= $cnt_checked ?></th>
  </tr>
</table>
<?php require ($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
</body>
</html>
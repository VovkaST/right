<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php

if (!isset($_GET['face_id']) || !isset($_GET['adr_id'])){
	header('Location: migration.php');
	die();
} else {
	$faceId = $_GET['face_id'];
    $adr_id = $_GET['adr_id'];
}

?>
<?php 
require ($kernel."connection_ukr.php");
//если приходим с рапортом на удаление
if (isset($_GET['delete'])) {
    $error = array();
    $del_report = $_GET['delete'];
    $delete_info = $_SESSION['user'].", id сеанса ".$_SESSION['activity_id'].", ".date('d.m.Y, H:i');
    //удаляем рапорт
    $query = mysql_query("
        UPDATE
            migration_report
        SET
            deleted = '1',
            delete_info = '$delete_info'
        WHERE
            id = '$del_report'
    ");
    //если удален
    if ($query) {
        //удаляем информацию по рапорту
        $query = mysql_query("
            UPDATE
                migration_report_info
            SET
                deleted = '1',
                delete_info = '$delete_info'
            WHERE
                report_id = '$del_report'
        ");
        //если удалена
        if ($query) {
            header("location: ".$_SERVER['PHP_SELF']."?adr_id=$adr_id&face_id=$faceId");
        } else {
            $error[] = "Ошибка удаления информации по рапорту: ".mysql_error();
            //восстанавливаем рапорт
            mysql_query("
                UPDATE
                    migration_report
                SET
                    deleted = '0',
                    delete_info = '$delete_info'
                WHERE
                    id = '$del_report'
            ");
        }
    } else {
        $error[] = "Ошибка удаления рапорта: ".mysql_error();
    }
    
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан на соблюдение миграционного законодательства</title>
 <link rel="icon" href="<?= $img ?>favicon.ico" type="image/vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= $css ?>main.css">
 <link rel="stylesheet" href="<?= $css ?>head.css">
 <link rel="stylesheet" href="<?= $css ?>new.css">
 <script src="js/confirm_box.js"></script>
 <script src="<?=$js?>jquery-1.10.2.js"></script>
 <script>
  $(function () {
    $(".report_date").click(
        function() {
          $(".delete_report").attr("id", $(this).attr("id"));
        }
    );
  });
 </script>
 <script>
  $(function() {
    $(".delete_report").click(
      function() {
        $('body').append('<div id="opacity_back" class="opacity_back" style="height: '+$('html').css("height")+';"></div> \
          <form name="confirm_form" id="confirm_form" class="confirm_box" action="<?=$_SERVER['PHP_SELF']."?adr_id=$adr_id&face_id=$faceId&delete='+$(this).attr('id')+'" ?>" method="POST"> \
          <div id="msg_txt"> \
          <img src="../images/error_record.png" height="40" width="40"/> \
          <span>Вы действительно хотите удалить рапорт?</span> \
          </div> \
          <input type="button" value="Да" name="error_record" id="error_record_confirm"/ onclick="submit();"> \
          <input type="button" value="Нет" id="error_record_cancel"/ onclick="close_confirm_box();"></form> \
          ');
        $('#error_record_confirm').focus();
      }
    );
  });
 </script>
</head>
<body>
<?php

require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
$sql_district = mysql_query('
	SELECT
		orderId,
		Район,
		Строка
	FROM
		address
	WHERE
		id = "'.$adr_id.'"
');
while ($district = mysql_fetch_row($sql_district)) {
	$district_id = $district[0];
	$district_str = $district[1];
	$address_str = $district[2];
}
?>
<div class="breadcrumbs">
<a href="<?= $index ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $accounting ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;</a><a href="<?= $migration ?>">Проверка граждан на соблюдение миграционного законодательства (текущий год) (проверено)</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>migration/migration_district.php?district=<?= $district_id ?>"><?= $district_str ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $addr?>migration/migration_list.php?adr_id=<?=$adr_id?>"><?= $address_str ?></a>
</div>
<?php if (isset($error)) {echo implode(', ', $error);} ?>
<?php
$stmt = mysql_query("
    SELECT
        f.id,
        f.ФамилияКириллица,
        f.ИмяКириллица,
        f.ОтчествоКириллица,
        f.ДатаРождения,
        MAX(mri.id) as Check_id
    FROM 
        face as f
    LEFT JOIN
        migration_report_info as mri ON
            mri.man_id = f.id
	WHERE 
		f.id = '$faceId'
");
$faceInfo = mysql_fetch_assoc($stmt);
if ($faceInfo) : 
    $Check_id = $faceInfo['Check_id']; ?>
	<h2><?php echo $faceInfo['ФамилияКириллица']." ".$faceInfo['ИмяКириллица']." ".$faceInfo['ОтчествоКириллица']." ".date('d.m.Y', strtotime($faceInfo['ДатаРождения'])) ?> г.р.</h2>
    <table border="1" id="report_table">
     <tr>
      <td width="16%" style="vertical-align: top;" rowspan="2">
        <center><b>Проверки:</b></center><br/>
	    <?php 
            $sql_report = mysql_query("
                SELECT
                    mr.id as Check_id,
                    mr.datpr as Check_date,
                    mr.vrpr as Check_time
                FROM
                    migration_report as mr
                WHERE
                    mr.id IN
                    (
                     SELECT
                        mri.report_id
                     FROM
                        migration_report_info as mri
                     WHERE
                        mri.man_id = '$faceId'
                    ) AND
                    mr.deleted = '0'
                ORDER BY
                    Check_date
            ");
        $cnt = 0;
        while ($report = mysql_fetch_array($sql_report)) : 
        $Check_id = $report['Check_id'];
        $Check_date = date('d.m.Y', strtotime($report['Check_date']));
        if (strlen($report['Check_time']) == 2) {
            $Check_time = $report['Check_time'].":00";
        }
        else {
            $Check_time = date('H:i', strtotime($report['Check_time']));
        }                
        ?>
		  <?php echo ++$cnt.". " ?><a href="migration_report.php?check_id=<?php echo $Check_id ?>" class="report_date" id="<?php echo $Check_id ?>" target="frm"><?php echo $Check_date.", ".$Check_time ?></a><br/>
	    <?php endwhile; ?>
      </td>
      <td id="button_cell">
        <form action="migration_download_file.php" method="POST">
          <input type="image" src="<?=$img?>printer.png" id="download_file" height="30px"/>
        </form>
        <input type="button" value="Удалить" class="delete_report" id="<?php echo $Check_id ?>"/>
      </td>
    </tr>
    <tr>
      <td style="border-top: none;">
        <iframe src="migration_report.php?check_id=<?php echo $Check_id ?>" name="frm" id="frm" width="100%" frameborder="0"></iframe>
      </td>
	</tr>
    </table>
<?php endif; ?>
<?php require ($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
</body>
</html>
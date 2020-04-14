<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php

if (!isset($_GET['district']) || !isset($_GET['address']) || !isset($_GET['face_id'])){
	
	header('Location: /ukr/district.php');
	die();
} else {

	$district = $_REQUEST['district'];
    $address = $_REQUEST['address'];
    $faceId = $_REQUEST['face_id'];
}


//если приходим с рапортом на удаление
if (isset($_GET['delete'])) {
    $error = array();
    $del_report = $_GET['delete'];
    $delete_info = $_SESSION['user'].", id сеанса ".$_SESSION['activity_id'].", ".date('d.m.Y, H:i');
    //удаляем макрорапорт
    $query = mysql_query("
        UPDATE
            macroreport
        SET
            deleted = '1',
            delete_info = '$delete_info'
        WHERE
            id = '$del_report'
    ");
    //если удален
    if ($query) {
        //получаем ID рапорта
        $query = mysql_query("
            SELECT
                id
            FROM
                report
            WHERE
                macroreportId = '$del_report'
        ");
        while ($report = mysql_fetch_array($query)) {
            $report_id = $report['id'];
        }
        //удаляем его
        $query = mysql_query("
            UPDATE
                report
            SET
                deleted = '1',
                delete_info = '$delete_info'
            WHERE
                macroreportId = '$del_report'
        ");
        //если удален
        if ($query) {
            //удаляем информацию по рапорту
            $query = mysql_query("
                UPDATE
                    report_info
                SET
                    deleted = '1',
                    delete_info = '$delete_info'
                WHERE
                    reportId = '$report_id'
            ");
            //если удалена
            if ($query) {
                header("location: ".$_SERVER['PHP_SELF']."?district=$district&address=$address&face_id=$faceId");
            } else {
                $error[] = "Ошибка удаления информации по рапорту: ".mysql_error();
                //восстанавливаем макрорапорт и рапорт
                mysql_query("
                    UPDATE
                        macroreport
                    SET
                        deleted = '0',
                        delete_info = '$delete_info'
                    WHERE
                        id = '$del_report'
                ") or die("Ошибка MySQL: ".mysql_error());
                mysql_query("
                    UPDATE
                        report
                    SET
                        deleted = '0',
                        delete_info = '$delete_info'
                    WHERE
                        macroreportId = '$del_report'
                ") or die("Ошибка MySQL: ".mysql_error());
            }            
        } else {
            $error[] = "Ошибка удаления рапорта: ".mysql_error();
            //восстанавливаем рапорт
            mysql_query("
                UPDATE
                    macroreport
                SET
                    deleted = '0',
                    delete_info = '$delete_info'
                WHERE
                    id = '$del_report'
            ") or die("Ошибка MySQL: ".mysql_error());
        }
    } else {
        $error[] = "Ошибка удаления макрорапорта: ".mysql_error();
    }
    
}
?>
<?php

require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
require ($kernel."connection_ukr.php");
$loc = mysql_query('
	SELECT
		строка
	FROM
		address
	WHERE
		id = '.$_GET['address'].'
');
while ($row = mysql_fetch_array($loc)) {
	$address_str = $row['строка'];
}
$stmt = mysql_query('
	SELECT
		c.id,
		c.ФамилияКириллица,
		c.ИмяКириллица,
		c.ОтчествоКириллица,
		c.ДатаРождения
	FROM 
		face as c
	WHERE 
		id = '.$faceId.'
');
$faceInfo = mysql_fetch_assoc($stmt);
$stmt = mysql_query('
	SELECT 
		macroreport.id,
		датаПроверки,
		времяПроверки,
		department,
		должность,
		звание,
		сотрудник,
		телефон
	FROM 
		macroreport 
	WHERE 
		id in (
			SELECT 
				macroreportId
			FROM 
				report
			WHERE 
				faceId = '.$faceId.'	
		)
	ORDER BY 
		датаПроверки desc
');
$cnt = 0;

?>
<?php
if ($faceInfo):	?>
  <!DOCTYPE html>
  <html>
  <head>
   <meta charset="utf-8">
   <title>Проверка граждан, прибывших с Украины</title>
    <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
    <link rel="stylesheet" href="<?=$css?>main.css">
    <link rel="stylesheet" href="<?= $css ?>new.css">
    <link rel="stylesheet" href="<?=$css?>head.css">
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
            <form name="confirm_form" id="confirm_form" class="confirm_box" action="<?php echo $_SERVER['PHP_SELF']."?district=$district&address=$address&face_id=$faceId&delete='+$(this).attr('id')+'" ?>" method="POST"> \
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
  <div class="breadcrumbs">
    <a href="<?= $index ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= $accounting ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="districtFaceList.php">Проверено лиц, состоящих на учете УФМС</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/addressFaceList.php?district=<?=$_GET['district']?>"><?= $_GET['district'] ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$addr?>ukr/peopleFaceList.php?district=<?= $_GET['district'] ?>&address=<?= $_GET['address'] ?>"><?= $address_str ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
  </div>
	<h2><?=$faceInfo['ФамилияКириллица']." ".$faceInfo['ИмяКириллица']." ".$faceInfo['ОтчествоКириллица']." ".date('d.m.Y', strtotime($faceInfo['ДатаРождения']))?></h2>
  <table border="1" rules="all" id="report_table" width="100%" style="font-size: 9pt;">
    <tr>
      <td width="16%" rowspan="2" style="vertical-align:top">
        <center><b>Проверки:</b></center><br/>
        <?php while ($report = mysql_fetch_assoc($stmt)): 
          $reportId = $report['id']; ?>
          <?=++$cnt?>. <a href="<?=$addr?>ukr/download_raport.php?id=<?=$reportId?>" class="report_date" id="$reportId" target="frm"><?=date('d.m.Y', strtotime($report['датаПроверки'])).", ".$report['времяПроверки']?></a><br/>
        <?php endwhile; ?>
      </td>
      <td id="button_cell">
        <form action="<?=$addr?>ukr_download_file.php?id=<?=session_id()?>" method="POST">
          <input type="image" src="<?=$img?>printer.png" id="download_file" height="30px"/>
        </form>
        <input type="button" value="Удалить" class="delete_report" id="<?=$reportId?>"/>
      </td>
    </tr>
    <tr>
      <td width="85%" style="border-top: none;">
        <iframe src="<?=$addr?>ukr/download_raport.php?id=<?=$reportId?>" name="frm" width="99%" style="min-height: 600px" frameborder="0"></iframe>
      </td>
    </tr>
  <?php endif; ?>
  </table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
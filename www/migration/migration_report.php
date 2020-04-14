<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
</head>
<style>
body{
  margin: 0;
  padding: 0;
    }
</style>
<body>

<?php

require ($kernel."connection_ukr.php");

if (!isset($_GET['check_id'])){
	
	header('Location: migration.php');
	die();
} else {
	
	$check_id = $_GET['check_id'];
}
$sql_report_address = mysql_query('
    SELECT
        mr.id,
        mr.dolj, 
        mr.department,
        mr.zvan,
        mr.sotr,
        mr.datpr as Check_Date,
        mr.vrpr as Check_Time,
        a.Строка as address,
        n.НаименованиеПринимающейСтороны as receive_side,
        n.ОтветственноеЛицо as receiver,
        n.ФактическийАдресПринимающейСтороны as reciever_address
    FROM
        migration_report as mr
    LEFT JOIN
        address as a ON
            a.id = mr.address_id
    LEFT JOIN
        notice as n ON
            n.addrPrebId = a.id
    WHERE
        mr.id = "'.$check_id.'"
    GROUP BY 
        mr.id
');
?>	

    <?php $sql_arrived_men = mysql_query('
        SELECT
            MAX(n.id),
            f.ФамилияКириллица as surname,
            f.ИмяКириллица as name,
            f.ОтчествоКириллица as sec_name,
            f.ДатаРождения as bd,
            n.ДатаПрибытия as arrive_date,
            n.Цель as reason
        FROM
            migration_report_info as mri
        LEFT JOIN
            face as f ON
                f.id = mri.man_id
        LEFT JOIN
            notice as n ON
                n.faceId = f.id
        WHERE
            mri.report_id = "'.$check_id.'"
        GROUP BY
            n.faceId
        ORDER BY
            f.ФамилияКириллица,
            f.ИмяКириллица,
            f.ОтчествоКириллица
    '); 
    while ($arrived_men = mysql_fetch_array($sql_arrived_men)) : 
    $bd = date('d.m.Y', strtotime($arrived_men['bd']));
    $arrive_date = date('d.m.Y', strtotime($arrived_men['arrive_date']));
    ?>
    
    <?php while($report_address = mysql_fetch_array($sql_report_address)) : 
    $reportId = $report_address["id"];
    $Check_Date = $report_address['Check_Date'];
    if (!empty($report_address['receive_side'])) {
        $receive_side = "принимающая сторона - <b>".$report_address['receive_side']."</b>. ";
    } else {$receive_side = "";}
    if (!empty($report_address['receiver'])) {
        $receiver = "ответственное лицо - <b>".$report_address['receiver']."</b>";
    } else {$receiver = "";}
    if (!empty($report_address['reciever_address'])) {
        $reciever_address = ", его адрес - <b>".$report_address['reciever_address']."</b>";
    } else {$reciever_address = "";}
    ?>
    <table>
      <tr>
        <td width="66%"></td>
        <td width="33%">Начальнику<br/><?php echo $report_address['department'] ?></td>
      </tr>
      <tr><td colspan="2">&nbsp;</td></tr>
      <tr><td colspan="2">&nbsp;</td></tr>
      <tr><td colspan="2" align="center">Рапорт.</td></tr>
      <tr>   
        <td style='text-indent: 40px' colspan="2">
          Докладываю Вам, что <b><?php echo date('d.m.Y', strtotime($Check_Date)) ?> г.</b> около <b><?php echo $report_address['Check_Time'] ?></b> часов мною проверен адрес: <b><?php echo $report_address['address'] ?></b> по которому, в соответствии с данными ФМС, пребывают иностранные граждане:
        </td>
      </tr>
      <tr>
        <td colspan="2" style='text-indent: 40px'>
          <b>- <?php echo $arrived_men['surname']." ".$arrived_men['name']." ".$arrived_men['sec_name']." ".$bd ?> г.р.</b> Дата приезда: <b><?php echo $arrive_date ?> г.</b>, цель: <b><?php echo $arrived_men['reason'] ?></b>.
        </td>
      </tr>
      <tr>
        <td colspan="2" style='text-indent: 40px'><?php echo $receive_side.$receiver.$reciever_address ?>.</td>
      </tr>
      <tr>
        <td colspan="2" style='text-indent: 40px'>при проверке установлено:</td>
      </tr>
  <?php $sql_man_info = mysql_query('
    SELECT
        n.ДатаПрибытия as arive_date,
        n.СрокПребыванияДо as leave_date,
        f.ФамилияКириллица as surname, 
        f.ИмяКириллица as name, 
        f.ОтчествоКириллица as sec_name, 
        f.ДатаРождения as bd,
        mri.mobila, mri.avtomod, mri.avtogosn, mri.avtocvet, mri.selpiezda, 
        mri.faktproj, mri.skemproj, mri.ustanovleno, mri.mrab, mri.rodsv, 
        mri.leaved, mri.arab_lang, mri.short_pants, mri.hijab, 
        mri.beard, mri.nervousness, mri.islamic_literature, mri.religion_goods, 
        mri.visit_mosque, mri.ethnic_diaspora, mri.relative_NKR, 
        mri.relative_arabian, mri.violation
    FROM
        migration_report_info mri
    LEFT JOIN
        face as f ON
            f.id = mri.man_id
    LEFT JOIN
        notice as n ON
            n.faceId = f.id
    WHERE
        mri.report_id = "'.$check_id.'"
    GROUP BY
        n.faceId
    ORDER BY
        f.ФамилияКириллица,
        f.ИмяКириллица,
        f.ОтчествоКириллица
  ');
  while ($man_info = mysql_fetch_array($sql_man_info)) : 
    $arive_date = date('d.m.Y', strtotime($man_info['arive_date']));
    $leave_date = date('d.m.Y', strtotime($man_info['leave_date']));
    $bd = date('d.m.Y', strtotime($man_info['bd']));
    $fio = str_replace('..', '.', mb_strcut($man_info['name'], 1, 2, "UTF-8") . '.' . mb_strcut($man_info['sec_name'], 1, 2, "UTF-8") . '.');
    $har = array();
    if ($man_info['selpiezda']){
		
		$har[] = "Цель приезда: <b>" . $man_info['selpiezda'] . ".</b>";
	}
    if ($man_info['mobila']){
		
		$har[] = "Мобильный: <b>" . $man_info['mobila'] . ".</b>";
	}
	if ($man_info['faktproj']){
		
		$har[] = "Фактически проживает по адресу: <b>" . $man_info['faktproj'] . ".</b>";
	}
	if ((int)$man_info['skemproj']){
		
		$har[] = "Совместно проживает(-ют): <b>" . $man_info['skemproj'] . ".</b>";
	}
    if ((int)$man_info['mrab']){
		
		$har[] = "Место работы: <b>" . $man_info['mrab'] . ".</b>";
	}
    if ((int)$man_info['avtomod']){
		
		$har[] = "Автомобиль: <b>" . $man_info['avtomod'] . ".</b>";
	}
    if ((int)$man_info['avtocvet']){
		
		$har[] = "Цвет: <b>" . $man_info['avtocvet'] . ".</b>";
	}
    if ((int)$man_info['avtogosn']){
		
		$har[] = "Гос.номер: <b>" . $man_info['avtogosn'] . ".</b>";
	}
    if ((int)$man_info['rodsv']){
		
		$har[] = "Родственники на территории Кировской области: <b>" . $man_info['rodsv'] . ".</b>";
	}
    if ((int)$man_info['ustanovleno']){
		
		$har[] = "Иное: <b>" . $man_info['ustanovleno'] . ".</b>";
	}
    //-------- характерные признаки --------
    if ((int)$man_info['leaved']){
		
		$leaved = "Убыл.";
	}
    $info = array();
    if ((int)$man_info['arab_lang']){
		
		$info[] = "арабская речь";
	}
    if ((int)$man_info['short_pants']){
		
		$info[] = "подворачивание брюк или короткие штаны (выше щиколотки)";
	}
    if ((int)$man_info['hijab']){
		
		$info[] = "хиджаб";
	}
    if ((int)$man_info['beard']){
		
		$info[] = "борода без усов и брита¤ голова";
	}
    if ((int)$man_info['nervousness']){
		
		$info[] = "нервозность";
	}
    if ((int)$man_info['islamic_literature']){
		
		$info[] = "наличие исламской литературы";
	}
    if ((int)$man_info['religion_goods']){
		
		$info[] = "наличие религиозных предметов";
	}
    if ((int)$man_info['visit_mosque']){
		
		$info[] = "посещает мечеть";
	}
    if ((int)$man_info['ethnic_diaspora']){
		
		$info[] = "член этнической диаспоры";
	}
    if ((int)$man_info['relative_NKR']){
		
		$info[] = "наличие родственников в — –";
	}
    if ((int)$man_info['relative_arabian']){
		
		$info[] = "наличие родственников в арабских странах";
	}
    if ((int)$man_info['violation']) {
		$violation = "<b>выявлены нарушения.</b>";
	} else {$violation = "Нарушений не выявлено.";}    
    //-------- характерные признаки --------
    
    
?>
    <tr>
      <td colspan="2" style='text-indent: 40px'><b>- <?php echo $man_info['surname']." ".$fio." ".$bd ?> г.р.</b>
      <?php if (!empty($leaved)) {echo "<b>$leaved</b>";} ?>
      Дата прибытия: <b><?php echo $arive_date ?> г.</b>. 
      Планирует убыть: <b><?php echo $leave_date ?> г.</b>.
      <?php 
      echo $violation;
      echo implode(' ', $har);
      if ($info) {
          echo " Имеет характерные признаки экстремистской (террористической) направленности: <b>".implode(', ', $info)."</b>.";
      }
      endwhile; // информаци§ на лицо 
      $cnt = count($sql_man_info) == 1 ? 'иностранным гражданином' : 'иностранными гражданами'; ?>
      </td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2" style='text-indent: 40px'>C <?php echo $cnt ?> проведена профилактическая беседа о необходимости соблюдения законодательства Российской Федерации, разъяснен порядок обращения за помощью в органы власти и другие государственные учреждения.</td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2"><?= $report_address['dolj'] ?></td>
    </tr>
    <tr>
      <td colspan="2"><?= $report_address['department'] ?></td>
    </tr>
    <tr>
      <td colspan="2">
        <table style="width:90%">
          <tr>
            <td align="left"><?= $report_address['zvan'] ?></td>
            <td align="right"><?= $report_address['sotr'] ?></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="2"><?= date('d.m.Y', strtotime($Check_Date)) ?></td>
    </tr>
    </table>
    <?php endwhile; // информация по рапорту?>
    <?php endwhile; // прибывшие?>
</body>
</html>
<?php
$dir = $_SESSION['dir_session'];
if (!file_exists($dir."/Report_migration.doc")) {
    fopen($dir."/Report_migration.doc",'x');
}
$file = ob_get_contents();
file_put_contents($dir."/Report_migration.doc", $file);

require("copy_report_file.php");
?>
<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (!isset($_REQUEST['face_id'])){
	header('Location: district.php');
	die();
} else {
	$faceId = $_GET['face_id'];
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Проверка граждан прибывших с Украины</title>
  <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?=$css?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?=$css?>head.css">
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
require ($kernel."connection_ukr.php");
?>
<div class="breadcrumbs">
<a href=<?=$index?>>Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href=<?=$ukr?>>Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="districtMessageFaceList.php">Всего проверено лиц, не состоящих на учете УФМС (в инициативном порядке)</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="addressMessageFaceList.php?district=<?=$_GET['district']?>"><?=$_GET['district']?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="peopleMessageFaceList.php?address=<?=$_GET['address']?>&district=<?=$_GET['district']?>"><?=$_GET['address']?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<?php
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
		id = "'.$faceId.'"
');
$faceInfo = mysql_fetch_assoc($stmt);
if ($faceInfo){
	$stmt = mysql_query('
		SELECT 
			message.id,
			датаПроверки,
			времяПроверки,
			department,
			должность,
			звание,
			сотрудник,
			телефон
		FROM message 
		WHERE id in (
			SELECT 
				messageId
			FROM 
				report
			WHERE 
				faceId = "'.$faceId.'"
      )
		ORDER BY 
			датаПроверки DESC
	');
	$cnt = 0;
	$html = '<h2>'.$faceInfo['ФамилияКириллица']." ".$faceInfo['ИмяКириллица']." ".$faceInfo['ОтчествоКириллица']." ".date('d.m.Y', strtotime($faceInfo['ДатаРождения'])).'</h2><table width="1000px" border="1" rules="all">';
	while ($report = mysql_fetch_assoc($stmt)){		
		if (!$cnt){
			$html .= '<tr><td width="15%" style="vertical-align:top"><center><b>Проверки:</b></center><br/>'.($cnt+1).'. <a href="'.$addr.'ukr/download_message.php?id='.$report['id'].'" target="frm">'.date('d.m.Y', strtotime($report['датаПроверки'])).', '.$report['времяПроверки'].'</a></td><td width="85%" rowspan="[%]"><iframe src="'.$addr.'ukr/download_message.php?id='.$report['id'].'" name="frm" width="99%" style="min-height: 600px" frameborder="none"></iframe></td></tr>';
		} else {
			$html .= '<tr><td width="15%" style="vertical-align: top"><a href="'.$addr.'ukr/download_message.php?id='.$report['id'].'" target="frm">'.date('d.m.Y', strtotime($report['датаПроверки'])).", ".$report['времяПроверки'].'</a></td></tr>';
		}
		$cnt++;
	}
	echo str_replace('[%]', $cnt, $html);
} ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
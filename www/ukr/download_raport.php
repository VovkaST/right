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

if (!isset($_GET['id'])){
	
	header('Location: /ukr/index.php');
	die();
} else {
	
	$macroreportId = $_GET['id'];
}

$stmt = mysql_query('
	SELECT * 
	FROM 
		macroreport 
	JOIN address ON 
    addressId = address.id 
	WHERE 
		macroreport.id = "'.$macroreportId.'"
');
$macroreport = mysql_fetch_assoc($stmt);
mysql_free_result($stmt);

if ($macroreport){
	$reportList = array();
	$stmt = mysql_query('
		SELECT
			b.ВидПринимающейСтороны,
			b.ОтветственноеЛицо,
			f.Строка as "АдресОтветственногоЛица",
			b.НаименованиеПринимающейСтороны,
			b.ФактическийАдресПринимающейСтороны,
			c.id as faceId,
			c.ФамилияКириллица,
			c.ИмяКириллица,
			c.ОтчествоКириллица,
			c.ДатаРождения,
			b.ДатаПрибытия,
			b.Цель,
			d.*,
			g.*
		FROM (
			SELECT
				MAX(a.id) as id,
				a.faceId,
				MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет
			FROM 
				notice a
			WHERE 
				a.ДатаУбытия is null
			GROUP BY 
				a.faceId
		) a JOIN notice b ON a.id = b.id 
		JOIN face c ON b.faceId = c.id and c.Гражданство = \'UKR\'
		JOIN address e ON b.addrPrebId = e.id
		JOIN address f ON b.addrSideId = f.id
		JOIN report d ON b.faceId = d.faceId and d.macroreportId = '.$macroreportId.'
		LEFT JOIN report_info g ON d.id = g.reportId
		ORDER BY 
			c.ФамилияКириллица,
			c.ИмяКириллица,
			c.ОтчествоКириллица,
			c.ДатаРождения
	');
	while ($report = mysql_fetch_assoc($stmt)){
		$reportList[] = $report;
	}
	if ($reportList){
?>	
<table style="width:100%">
<tr><td align="right">Начальнику</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td align="center">Рапорт.</td></tr>
<?php
$datPr = date('d.m.Y', strtotime($macroreport['датаПроверки']));
$html = <<<HTML
\n<tr><td style='text-indent: 40px'>Докладываю Вам, что <b>{$datPr}</b> около <b>{$macroreport['времяПроверки']}</b> часов мною проверен адрес: <b>{$macroreport['Строка']}</b> по которому, в соответствии с данными ФМС, пребывают граждане Украины:</td>
HTML;

foreach ($reportList as $face){
	
	$dat = date('d.m.Y', strtotime($face['ДатаРождения']));
	$datPr = date('d.m.Y', strtotime($face['ДатаПрибытия']));
	$html .= "\n<tr><td style='text-indent: 40px'><b>- {$face['ФамилияКириллица']} {$face['ИмяКириллица']} {$face['ОтчествоКириллица']} {$dat}</b>. Дата приезда - <b>{$datPr}</b>, цель - <b>{$face['Цель']}</b>.</td></tr>";
}

$uniq = array();
foreach ($reportList as $face){

	$st = "\n<tr><td style='text-indent: 40px'>Принимающая сторона - <b>{$face['ВидПринимающейСтороны']}</b>: <b>{$face['НаименованиеПринимающейСтороны']}</b>, ответственное лицо - <b>{$face['ОтветственноеЛицо']}</b>, его адрес <b>{$face['АдресОтветственногоЛица']}</b>.</td></tr>";
	if (!isset($uniq[$st])){
		
		$uniq[$st] = $st;
		$html .= $st;
	}
}

$html .= "\n<tr><td style='text-indent: 40px'>При проверке установлено, что:</td></tr>";
foreach ($reportList as $face){
	
	$dtPr = date('d.m.Y', strtotime($face['датаПрибытия']));
	$dtUb = date('d.m.Y', strtotime($face['датаВыезда']));
	$dat = date('d.m.Y', strtotime($face['ДатаРождения']));
	$fio = mb_ereg_replace('..', '.', mb_strcut($face['ИмяКириллица'], 0, 2, 'UTF-8') . '.' . mb_strcut($face['ОтчествоКириллица'], 0, 2, 'UTF-8') . '.');
	
	$har = array();
	if ($face['сфераТрудовойДеятельности']){
		
		$har[] = "\nОсуществляет трудовую деятельность в сфере <b>" . $face['сфераТрудовойДеятельности'] . ".</b>";
	}
	if ((int)$face['нарушениеТрудЗак']){
		
		$har[] = "\n¬ том числе <b>с нарушением Трудового Законодательства</b>.";
	}
	if ((int)$face['ОбучениеНЛ']){
		
		$har[] = "\n<b>Обучается в учебном заведении</b>.";
	}
	if ((int)$face['ПланируетОбучатьсяНЛ']){
		
		$har[] = "\n<b>Планирует обучаться в учебном заведении</b>.";
	}
	if ((int)$face['ПосещаетДошкольноеУчрНЛ']){
		
		$har[] = "\n<b>Посещает дошкольное учреждение</b>.";
	}
	if ((int)$face['ПланируетПосещатьДошУчрНЛ']){
		
		$har[] = "\n<b>Планирует посещать дошкольное учреждение</b>.";
	}
	if ((int)$face['ПроживаетБезЗакПредНЛ']){
		
		$har[] = "\n<b>проживает без родителей (законных представителей)</b>.";
	}
	if ((int)$face['ПроживаетБезОформлДокНЛ']){
		
		$har[] = "\nВ том числе <b>без оформления документов на законное представительство</b>.";
	}
	if ((int)$face['НарушениеЗакона']){
		
		$nar = "\n<b>Выявлены нарушения законодательства</b>.";
	} else {
		
		$nar = "\n<b>Нарушения законодательства не выявлены</b>.";
	}
	$html .= "\n<tr><td style='text-indent: 40px'><b>- {$face['ФамилияКириллица']} {$fio} {$dat}</b>. 
	Адрес проживания на Украине: <b>{$face['адресУкраина']}</b>. 
	Документ: <b>{$face['документ']}</b>. 
	Дата прибытия: <b>{$dtPr}</b>. 
	Планирует убыть: <b>{$dtUb}</b>. 
	Цель пребывания: <b>{$face['цельПребывания']}</b>. " . implode(' ', $har) . " 
	Адрес пребывания: <b>{$face['адресПребывания']}</b>. 
	Совместно проживает: <b>{$face['совместноПроживает']}</b>. 
	Мобильный телефон: <b>{$face['контакныйТелефон']}</b>. {$nar} 
	прочее: <b>{$face['прочее']}</b>.</td></tr>";
}
$cnt = count($reportList) == 1 ? 'гражданином' : 'гражданами';
$html .= "\n<tr><td>&nbsp;</td></tr><tr><td style='text-indent: 40px;'>С {$cnt} Украины проведена профилактическая беседа о необходимости соблюдения законодательства Российской Федерации, разъяснен порядок обращения за помощью в орган ФМС и другие государственные учреждения.</td></tr>";
$html .= "\n<tr><td>&nbsp;</td></tr><tr><td>{$macroreport['должность']}</td></tr>";
$html .= "\n<tr><td>{$macroreport['department']}</td></tr>";
$html .= "\n<tr><td><table style=\"width:90%\"><tr><td align=\"left\">{$macroreport['звание']}</td><td align=\"right\">{$macroreport['сотрудник']}</td></tr></table></td></tr>";
$datPr = date('d.m.Y', strtotime($macroreport['датаПроверки']));
$html .= "\n<tr><td>{$datPr}</td></tr>";
echo $html;
	}
}
?>
</table>
</body>
</html>
<?php
$dir = $_SESSION['dir_session'];
if (!file_exists($dir."/Report_migration_Ukraine.doc")) {
    fopen($dir."/Report_migration_Ukraine.doc",'x');
}
$file = ob_get_contents();
file_put_contents($dir."/Report_migration_Ukraine.doc", $file);
require('copy_report_file.php');
?>
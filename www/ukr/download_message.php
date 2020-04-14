<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<body>
<?php
require ("/../../connection_ukr.php");
if (!isset($_GET['id'])){
	header('Location: /ukr/index.php');
	die();
} else {
	$messageId = $_GET['id'];
}
$stmt = mysql_query('SELECT * FROM message WHERE id = ' . $messageId);
$message = mysql_fetch_assoc($stmt);
mysql_free_result($stmt);

if ($message){
	$reportList = array();
	$stmt = mysql_query('
		SELECT
			c.id as faceId,
			c.ФамилияКириллица,
			c.ИмяКириллица,
			c.ОтчествоКириллица,
			c.ДатаРождения,
			c.МестоРождения,
			d.*,
			g.*
		FROM 
			report d 
		LEFT JOIN report_info g ON d.id = g.reportId
		JOIN face c ON d.faceId = c.id AND c.Гражданство = \'UKR\'
		WHERE 
			d.messageId = ' . $messageId . '
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
<tr><td><table style=\"width:90%\"><tr><td width="70%px">&nbsp;</td><td align=\"right\">Руководителю территориального органа следственного отдела Следственного комитета Российской Федерации по Кировской области</td></tr><tr><td>&nbsp;</td></tr><tr><td width="70%">&nbsp;</td><td align=\"right\">Руководителю территориального структурного подразделения УФМС по Кировской области</td></tr></table></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td align="center">Сообщение.</td></tr>
<?php
$datPr = date('d.m.Y', strtotime($message['датаПроверки']));
$cnt = count($reportList) == 1 ? 'выявлен гражданин' : 'выявлены граждане';
$html = <<<HTML
<tr><td style='text-indent: 40px'>Сообщаем Вам, что {$datPr} около {$message['времяПроверки']} часов при проверке адреса: {$message['адресПроверки']} {$cnt} Украины:</td>
HTML;

$txt = array();
foreach ($reportList as $face){
	$txt[] = array();
	$index = count($txt) - 1;
	$txt[$index][] = "{$face['ФамилияКириллица']} {$face['ИмяКириллица']} {$face['ОтчествоКириллица']} " . date('d.m.Y', strtotime($face['ДатаРождения'])) . ".";
	if ($face['МестоРождения']){
		
		$txt[$index][] = "Уроженец: {$face['МестоРождения']}.";
	}
	if ($face['адресУкраина']){
	
		$txt[$index][] = "Адрес проживания на Украине: {$face['адресУкраина']}.";
	}
	if ($face['документ']){
		
		$txt[$index][] = "Документ: {$face['документ']}.";
	}
	if ($face['датаПрибытия']){
		
		$txt[$index][] = "Дата прибытия: " . date('d.m.Y', strtotime($face['датаПрибытия'])) . ".";
	}
	if ($face['датаВыезда']){
		
		$txt[$index][] = "Планирует убыть: " . date('d.m.Y', strtotime($face['датаВыезда'])) . ".";
	}
	if ($face['цельПребывания']){
		
		$txt[$index][] = "Цель пребывания: {$face['цельПребывания']}.";
	}
	if ($face['сфераТрудовойДеятельности']){
		
		$txt[$index][] = 'Осуществляет трудовую деятельность в сфере "' . $face['сфераТрудовойДеятельности'] . '".';
	}
	if ((int)$face['нарушениеТрудЗак']){
		
		$txt[$index][] = 'В том числе с нарушением Трудового Законодательства.';
	}
	if ((int)$face['ОбучениеНЛ']){
		
		$txt[$index][] = 'Обучается в учебном заведении.';
	}
	if ((int)$face['ПланируетОбучатьсяНЛ']){
		
		$txt[$index][] = 'Планирует обучаться в учебном заведении.';
	}
	if ((int)$face['ПосещаетДошкольноеУчрНЛ']){
		
		$txt[$index][] = 'Посещает дошкольное учреждение.';
	}
	if ((int)$face['ПланируетПосещатьДошУчрНЛ']){
		
		$txt[$index][] = 'Планирует посещать дошкольное учреждение.';
	}
	if ((int)$face['ПроживаетБезЗакПредНЛ']){
		
		$txt[$index][] = 'Проживает без родителей (законных представителей).';
	}
	if ((int)$face['ПроживаетБезОформлДокНЛ']){
		
		$txt[$index][] = 'В том числе без оформления документов на законное представительство.';
	}
	if ($face['адресПребывания']){
		
		$txt[$index][] = "Адрес пребывания: {$face['адресПребывания']}.";
	}
	if ($face['совместноПроживает']){
		
		$txt[$index][] = "Совместно проживает: {$face['совместноПроживает']}.";
	}
	if ($face['контакныйТелефон']){
		
		$txt[$index][] = "Мобильный телефон: {$face['контакныйТелефон']}. ";
	}
	if ((int)$face['НарушениеЗакона']){
		
		$txt[$index][] = 'Выявлены нарушения законодательства.';
	} else {
		
		$txt[$index][] = 'Нарушение законодательства не выявлены.';
	}
	if ($face['прочее']){
		
		$txt[$index][] = "Прочее: {$face['прочее']}.";
	}
	
	$html .= "<tr><td style='text-indent: 40px'>Ц " . implode(' ', $txt[$index]) . "</td></tr>";
}

$txt[] = array();
$index = count($txt) - 1;
$txt[$index][] = "С " . (count($reportList) == 1 ? 'гражданином' : 'гражданами') . " Украины проведена профилактическая беседа о необходимости соблюдения законодательства Российской Федерации, разъяснен порядок обращения за помощью в орган ФМС и другие государственные учреждения.";
$html .= "<tr><td style='text-indent: 40px'>" . implode(' ', $txt[$index]) . "</td></tr>";

$txt[] = array();
$index = count($txt) - 1;
$txt[$index][] = "Проверку осуществил {$message['должность']}, {$message['звание']}, {$message['сотрудник']}, к.т. {$message['телефон']}.";
$html .= "<tr><td style='text-indent: 40px'>" . implode(' ', $txt[$index]) . "</td></tr>";

$txt[] = array();
$index = count($txt) - 1;
$txt[$index][] = "Начальник (Заместитель начальника) {$message['department']}";
$html .= "<tr><td>&nbsp;</td></tr><tr><td>" . implode(' ', $txt[$index]) . "</td></tr>";

echo $html;
echo "</table>";
ob_start();

include_once(dirname(__FILE__) . '/template_raport.php');

file_put_contents(dirname(__FILE__) . '/message/' . $messageId . '.xml', ob_get_clean());


	}
}
?>
</body>
</html>
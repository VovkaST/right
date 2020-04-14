<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
require ("../../connection_ukr.php");
if (!isset($_GET['id'])){
	header('Location: organisationTotal.php');
	die();
} else {
	$macroreportId = $_GET['id'];
}
$stmt = mysql_query('
	SELECT
		c.ovd,
		c.position,
		c.checker_range,
		c.name,
		c.telephone as tel_check,
		c.check_date,
		c.check_time,
		c.workers_year,
		c.workers_current,
		c.workers_plan,
		c.other as other_check,
		o.name as name_org,
		o.INN,
		o.address,
		o.guide,
		o.telephone,
		o.industry,
		o.farming,
		o.forest,
		o.building,
		o.trading,
		o.consumer_service,
		o.transport,
		o.other
	FROM 
		organisations o
	JOIN check_org c ON o.id = c.id_org 
	WHERE 
		c.id = '.$macroreportId
);
$macroreport = mysql_fetch_assoc($stmt);
$industry = array();
if ($macroreport['industry']) {
	$industry[] = "Промышленного производства";
}
if ($macroreport['farming']) {
	$industry[] = "Сельского хозяйства";
}
if ($macroreport['forest']) {
	$industry[] = "Лесного хозяйства";
}
if ($macroreport['building']) {
	$industry[] = "Строительства";
}
if ($macroreport['trading']) {
	$industry[] = "Торговли";
}
if ($macroreport['consumer_service']) {
	$industry[] = "Бытовых услуг";
}
if ($macroreport['transport']) {
	$industry[] = "Транспорта";
}
if ($macroreport['other']) {
	$industry[] = "прочих отраслях";
}
?>	
<table style="width:100%">
<tr><td align="right">Начальнику</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td align="center">Рапорт.</td></tr>
<?php
$datPr = date('d.m.Y', strtotime($macroreport['check_date']));
$timePr = date('H:i', strtotime($macroreport['check_time']));
if (count($industry)) {
	count($industry) == 1 ? $kol_ind = "отрасли" : $kol_ind = "отраслях";
	$ind = ", осуществляющая свою деятельность в ".$kol_ind." ".implode(", ", $industry);
}
else {
	$ind = "";
}
$html = <<<HTML
\n<tr><td style='text-indent: 40px'>Докладываю Вам, что <b>{$datPr}</b> около <b>{$timePr}</b> часов мною по адресу: <b>{$macroreport['address']}</b>, проверена организация "<b>{$macroreport['name_org']}</b>", ИНН <b>{$macroreport['INN']}</b>, руководитель - <b>{$macroreport['guide']}</b>, контактный телефон - <b>{$macroreport['telephone']}</b>{$ind}, в которой трудоустроены граждане Украины.</td>
HTML;
$migr = $macroreport['workers_current'];
if ($migr) {
	switch($migr) {
		case ($migr == 1):
			$end = "ец";
		break;
		case ($migr > 1 && $migr < 5):
			$end = "ца";
		break;
		case ($migr > 4):
			$end = "цев";
		break;
	}
	$workers_current = "работает <b>{$macroreport['workers_current']}</b> переселен".$end;
}
else {
	$workers_current = "переселенцы в ней не трудоустроены";
}
if ($macroreport['workers_plan']) {
	$macroreport['workers_plan'] == 1 ? $human = "человека" : $human = "человек";
	$plan = "планируется принять <b>{$macroreport['workers_plan']}</b> ".$human;
}
else {
	$plan = "трудоустраивать граждан Украины не планруется";
}
$html .= "\n<tr><td style='text-indent: 40px'>При проверке установлено, что всего с начала текущего года в организации работало <b>{$macroreport['workers_year']}</b> граждан Украины. В настоящее время {$workers_current}. В ближайшее время {$plan}.</td></tr>";
if ($macroreport['other_check']) {
	$html .= "\n<tr><td style='text-indent: 40px'>{$macroreport['other_check']}</td></tr>";
}
$html .= "\n<tr><td>&nbsp;</td></tr><tr><td>{$macroreport['position']}</td></tr>";
$html .= "\n<tr><td>{$macroreport['ovd']}</td></tr>";
$html .= "\n<tr><td><table style=\"width:90%\"><tr><td align=\"left\">{$macroreport['checker_range']}</td><td align=\"right\">{$macroreport['name']}</td></tr></table></td></tr>";
$html .= "\n<tr><td>{$datPr}</td></tr>";
echo $html;
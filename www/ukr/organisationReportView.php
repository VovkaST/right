<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
if (!isset($_REQUEST['id'])){
	header('Location: organisationTotal.php');
	die();
} else {
	$Id = $_REQUEST['id'];
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан, прибывших с Украины</title>
 <link rel="icon" href="../images/favicon.ico" type="../image/vnd.microsoft.icon">
 <link rel="stylesheet" href="css/migration.css">
 <link rel="stylesheet" href="../css/main.css">
 <link rel="stylesheet" href="../css/head.css">
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
require ("/../../connection_ukr.php");
echo "\n<div class=\"breadcrumbs\">";
echo "\n<a href=\"".$index."\">Главная</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"".$accounting."\">Формирование учетов</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."Проверка граждан, прибывших с Украины";
echo "\n</div>";
$stmt = mysql_query('
	SELECT
		name,
		INN,
		address
	FROM 
		organisations
	WHERE 
		id = '.$Id.'
');
$OrgInfo = mysql_fetch_assoc($stmt);
if ($OrgInfo){
	$stmt = mysql_query('
		SELECT 
			id,
			ovd,
			position,
			checker_range,
			name,
			telephone,
			check_date,
			check_time,
			workers_year,
			workers_current,
			workers_plan,
			other,
			id_org
		FROM 
			check_org 
		WHERE 
			id_org = '.$Id.'	
		ORDER BY 
			check_date desc
	');
	$cnt = 0;
	$html = "\n<h2>\"".$OrgInfo['name']."\" (ИНН ".$OrgInfo['INN']."), ".$OrgInfo['address']."</h2>\n<table width=\"1000px\" border=\"1\" style=\"border-collapse: collapse; border: 1px solid black\">";
	while ($report = mysql_fetch_assoc($stmt)){
		if (!$cnt){
			$html .= "\n<tr><td width=\"15%\" style=\"vertical-align:top\"><a href=\"organisationReport.php?id=".$report['id']."\" target=\"frm\">#".$report['id']."<br>".date('d.m.Y', strtotime($report['check_date']))." ".date('H:i', strtotime($report['check_time']))."<br>".$report['ovd']."<br>".$report['position']." ".$report['checker_range']."<br>".$report['name']." ".$report['telephone']."</a></td><td width=\"85%\" rowspan=\"[%]\"><iframe src=\"organisationReport.php?id=".$report['id']."\" name=\"frm\" width=\"99%\" style=\"min-height: 600px\"></iframe></td></tr>";
		} else {
			$html .= "\n<tr><td width=\"15%\" style=\"vertical-align: top\"><a href=\"organisationReport.php?id=".$report['id']."\" target=\"frm\">#".$report['id']."<br>".date('d.m.Y', strtotime($report['check_date']))." ".date('H:i', strtotime($report['check_time']))."<br>".$report['ovd']."<br>".$report['position']." ".$report['checker_range']."<br>".$report['name']." ".$report['telephone']."</a></td></tr>";
		}
		$cnt++;
	}
	echo str_replace('[%]', $cnt, $html);
}
echo "\n</table>";
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
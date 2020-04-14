<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
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
require ("../../connection_ukr.php");
require ("districtList.php");
echo "\n<div class=\"breadcrumbs\">";
echo "\n<a href=\"".$index."\">Главная</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"".$accounting."\">Формирование учетов</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"index.php\">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Проверено работодателей";
echo "\n</div>";
echo "\n<table align=\"center\" border=\"1\" style=\"border-collapse: collapse; border: 1px solid black\">";
echo "\n <tr><td width=\"300px\" align=\"center\">Район</td><td width=\"60px\" align=\"center\">Кол-во<br/>проверок</td></tr>";
ksort($districtList);
$join = array();
foreach ($districtList as $district) {
	if ($district) {
		$str = mysql_query("
			SELECT 
				ovd, 
				COUNT(distinct id) as cnt 
			FROM 
				check_org 
			WHERE 
				ovd = '".$district."'"
		);
		while($row = mysql_fetch_assoc($str)){
		$row['cnt'] == 0 ? $script = "organisation.php" : $script = "organisationList.php";
		echo "\n <tr><td>".$district."</td><td align=\"center\"><a href=\"{$script}?district={$district}\">{$row['cnt']}</a></td></tr>";
		}
	}
}
echo "\n</table>";
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
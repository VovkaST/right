<?php
$need_auth = 1;
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
require ("/../../connection_ukr.php");
$district = $_REQUEST['district'];
echo "\n<div class=\"breadcrumbs\">";
echo "\n<a href=\"".$index."\">Главная</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"".$accounting."\">Формирование учетов</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"index.php\">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href=\"organisationTotal.php\">Проверено работодателей</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;".$district;
echo "\n</div>";
$cnt = 1;
echo "\n<table border=\"1\" style=\"border-collapse: collapse; border: 1px solid black\">";
echo "\n <tr align=\"center\"><td width=\"5%\">№<br/>п/п</td><td width=\"27%\">Наименование</td><td width=\"60%\">Адрес</td><td width=\"5%\">Кол-во<br/>проверок</td></tr>";
$stmt = mysql_query("
	SELECT
		o.id,
		o.name,
		o.address,
		(SELECT COUNT(*) FROM check_org WHERE o.id = id_org) as cnt
	FROM
		check_org c    
	LEFT JOIN
		organisations o ON o.id = c.id_org
	WHERE
		ovd = '".$district."'
	GROUP BY
		o.id
");
while($row = mysql_fetch_assoc($stmt)){
	echo "\n <tr><td align=\"center\">".$cnt++.".</td><td>".$row['name']."</td><td>".$row['address']."</td><td><a href=\"organisationReportView.php?id=".$row['id']."\">".$row['cnt']."</a>&nbsp;&nbsp;&nbsp;<a href=\"organisation.php?district=".$district."&org_id=".$row['id']."\">+Доб.</a></td></tr>";
}
echo "\n</table>";
echo "<p align=\"right\"><a href=\"organisation.php?district=".$district."\">+Добавить новую организацию</a></p>";
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
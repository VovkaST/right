<?php
$db = mysql_connect('localhost', '...', '...', "set names 'utf-8'") or die('mysql');
mysql_select_db('ukraine', $db) or die('mysql database');
mysql_query("set names 'cp1251'");
$ovd = "";
$sql_ovd_query = mysql_query('
  SELECT
	  a.Район
  FROM 
    address as a
  JOIN
    migration_report as mr ON
      mr.address_id = a.id AND
      mr.id = "'.$check_id.'"
') or die("Ошибка SQL: ".mysql_error());
while ($sql_ovd = mysql_fetch_array($sql_ovd_query)) {
  $ovd = $sql_ovd["Район"]; // имя каталога ОВД
}
$year = date("Y", strtotime($Check_Date)); // год
$dir_year = $dir_migration.$year; // полный путь каталога года
$dir_ovd = $dir_year."/".$ovd; // полный путь каталога ОВД
$dir_month = $dir_ovd."/".$direction[date("n", strtotime($Check_Date))]; // полный путь каталога месяца
$file_old = $_SESSION['dir_session']."Report_migration.doc";
$file_new = "Report_migration_(".$check_id.").doc";
//-------- проверяем и создаем дерево каталогов --------//
  //если нет каталога года
  if (!is_dir($dir_year)) {
    mkdir($dir_year);
    mkdir($dir_ovd);
    mkdir($dir_month);
  }
  //если есть каталог года, но нет каталога ОВД
  if (is_dir($dir_year)) {
    if (!is_dir($dir_ovd)) {
      mkdir($dir_ovd);
      mkdir($dir_month);
    }
  }
  //если есть каталог года, ОВД, но нет каталога месяца
  if (is_dir($dir_year)) {
    if (is_dir($dir_ovd)) {
      if (!is_dir($dir_month)) {
        mkdir($dir_month);
      }
    }
  }
//-------- проверяем и создаем дерево каталогов --------//
if (!is_file($dir_month."/".$file_new)) {
  copy($file_old, $dir_month."/".$file_new);
}
?>
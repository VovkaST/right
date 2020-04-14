<?php
$db = mysql_connect('localhost', '...', '...') or die('mysql');
mysql_select_db('ukraine', $db) or die('mysql database');
mysql_query("set names 'cp1251'");
$sql_ovd_query = mysql_query('
  SELECT 
    a.Район
  FROM 
    address as a
  JOIN
    macroreport as mr ON
      mr.addressId = a.id AND
      mr.id = "'.$macroreportId.'"
') or die("Ошибка SQL: ".mysql_error());
while ($sql_ovd = mysql_fetch_array($sql_ovd_query)) {
  $ovd = $sql_ovd["Район"]; // имя каталога ОВД
}
$year = date("Y", strtotime($datPr)); // год
$dir_year = $dir_ukraine.$year; // полный путь каталога года
$dir_ovd = $dir_year."/".$ovd; // полный путь каталога ОВД
$dir_month = $dir_ovd."/".$direction[date("n", strtotime($datPr))]; // полный путь каталога месяца
$file_old = $_SESSION['dir_session']."Report_migration_Ukraine.doc";
$file_new = "Report_migration_Ukraine_(".$macroreportId.").doc";
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
<?php
$db = mysql_connect('localhost', '...', '...') or die('mysql');
mysql_select_db('ukraine', $db) or die('mysql database');
mysql_query("set names 'cp1251'");
$sql_ovd_query = mysql_query('
  SELECT 
    a.�����
  FROM 
    address as a
  JOIN
    macroreport as mr ON
      mr.addressId = a.id AND
      mr.id = "'.$macroreportId.'"
') or die("������ SQL: ".mysql_error());
while ($sql_ovd = mysql_fetch_array($sql_ovd_query)) {
  $ovd = $sql_ovd["�����"]; // ��� �������� ���
}
$year = date("Y", strtotime($datPr)); // ���
$dir_year = $dir_ukraine.$year; // ������ ���� �������� ����
$dir_ovd = $dir_year."/".$ovd; // ������ ���� �������� ���
$dir_month = $dir_ovd."/".$direction[date("n", strtotime($datPr))]; // ������ ���� �������� ������
$file_old = $_SESSION['dir_session']."Report_migration_Ukraine.doc";
$file_new = "Report_migration_Ukraine_(".$macroreportId.").doc";
//-------- ��������� � ������� ������ ��������� --------//
  //���� ��� �������� ����
  if (!is_dir($dir_year)) {
    mkdir($dir_year);
    mkdir($dir_ovd);
    mkdir($dir_month);
  }
  //���� ���� ������� ����, �� ��� �������� ���
  if (is_dir($dir_year)) {
    if (!is_dir($dir_ovd)) {
      mkdir($dir_ovd);
      mkdir($dir_month);
    }
  }
  //���� ���� ������� ����, ���, �� ��� �������� ������
  if (is_dir($dir_year)) {
    if (is_dir($dir_ovd)) {
      if (!is_dir($dir_month)) {
        mkdir($dir_month);
      }
    }
  }
//-------- ��������� � ������� ������ ��������� --------//
if (!is_file($dir_month."/".$file_new)) {
  copy($file_old, $dir_month."/".$file_new);
}
?>
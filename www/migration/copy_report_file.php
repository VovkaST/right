<?php
$db = mysql_connect('localhost', '...', '...', "set names 'utf-8'") or die('mysql');
mysql_select_db('ukraine', $db) or die('mysql database');
mysql_query("set names 'cp1251'");
$ovd = "";
$sql_ovd_query = mysql_query('
  SELECT
	  a.�����
  FROM 
    address as a
  JOIN
    migration_report as mr ON
      mr.address_id = a.id AND
      mr.id = "'.$check_id.'"
') or die("������ SQL: ".mysql_error());
while ($sql_ovd = mysql_fetch_array($sql_ovd_query)) {
  $ovd = $sql_ovd["�����"]; // ��� �������� ���
}
$year = date("Y", strtotime($Check_Date)); // ���
$dir_year = $dir_migration.$year; // ������ ���� �������� ����
$dir_ovd = $dir_year."/".$ovd; // ������ ���� �������� ���
$dir_month = $dir_ovd."/".$direction[date("n", strtotime($Check_Date))]; // ������ ���� �������� ������
$file_old = $_SESSION['dir_session']."Report_migration.doc";
$file_new = "Report_migration_(".$check_id.").doc";
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
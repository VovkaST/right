<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
header('Content-Type: text/html; charset=Windows-1251');

if (isset($_GET["id"])) {
  $m_dirs = array(1 => "01_январь", "02_Февраль", "03_Март", "04_Апрель", "05_Май", "06_Июнь", "07_Июль", "08_Август", "09_Сентябрь", "10_Октябрь", "11_Ноябрь", "12_Декабрь");
  query_log($_SESSION['activity_id'], 'refuse_download='.$_GET["id"]);
  require_once(KERNEL.'connection.php');
  $sql_file_query = mysql_query('
    SELECT
      d.`date`, d.`file_final`, o.`name_dir_otk`
    FROM
      `l_decisions` as d
    JOIN
      `spr_ovd` as o ON
        o.`id_ovd` = d.`ovd`
    WHERE
      d.`id` = '.$_GET["id"].'
  ') or die("Ошибка SQL: ".mysql_error());
  $file_query = mysql_fetch_array($sql_file_query);
    $file = $file_query["file_final"];
    $date = $file_query["date"];
    $name_dir_otk = $file_query["name_dir_otk"];
  $year = date("Y", strtotime($date))."год";
  $month_ind = date("m", strtotime($date))+0;
  $month = $m_dirs[$month_ind];
  $dir = $dir_files."/Отказные/".$year."/".$name_dir_otk."/".$month."/".$file;
  $dir = mb_convert_encoding($dir, 'Windows-1251', 'UTF-8');
  if (file_exists($dir)) {
    if (ob_get_level()) {
      ob_end_clean();
    }
    header("Content-Description: File Transfer");
    header("Content-Tipe: application/octet-stream");
    header("Content-Disposition: attachment; filename=".basename($dir));
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Cache-Control: must-revalidate");
    header("Pragma: public");
    header("Content-Length: ".filesize($dir));
    readfile($dir);
    exit;
  } else {
    echo "Файл ".basename($dir)." отсутствует!";
  }
}
else {
	echo "Ошибка парметра GET";
}
?>
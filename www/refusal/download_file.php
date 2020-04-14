<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (isset($_GET["id"])) {
  query_log($_SESSION['activity_id'], 'refuse_download='.$_GET["id"]);
  $db = mysql_connect('localhost', '...', '...', "set names 'utf8'");
  mysql_select_db('obv_otk', $db);
  mysql_query("set names 'cp1251'");
  $sql_file_query = mysql_query('
    SELECT
      o.data_resh,
      o.file_final,
      ovd.name_dir_otk
    FROM
      otkaz as o
    JOIN
      spr_ovd as ovd ON
        ovd.id_ovd = o.id_ovd
    WHERE
      o.id = "'.$_GET["id"].'"
  ') or die("Ошибка SQL: ".mysql_error());
  while ($file_query = mysql_fetch_array($sql_file_query)) {
    $file = $file_query["file_final"];
    $date = $file_query["data_resh"];
    $name_dir_otk = $file_query["name_dir_otk"];
  }
  $year = date("Y", strtotime($date))."год";
  $month_ind = date("m", strtotime($date))+0;
  $month = $direction[$month_ind];
  $dir = $dir_files."/Отказные/".$year."/".$name_dir_otk."/".$month."/".$file;
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
  }
  else {
    echo "Файл ".basename($dir)." отсутствует!";
  }
}
else {
	echo "Ошибка парметра GET";
}
?>
<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
$dir = $_SESSION["dir_session"]."Report_migration.doc";
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
  exit;}
else {    echo "Файл ".basename($dir)." отсутствует!";}
?>
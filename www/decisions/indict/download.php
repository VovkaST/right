<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
header('Content-Type: text/html; charset=UTF-8');
require_once('require.php');

try {
  if (!empty($_GET["file"])) {

    $file = new ElFile($_GET["file"]);
    
    if (file_exists($file->get_path())) {
      if (ob_get_level()) {
        ob_end_clean();
      }
      header("Content-Description: File Transfer");
      header("Content-Tipe: application/octet-stream");
      header("Content-Disposition: attachment; filename=".$file->get_file_name());
      header("Content-Transfer-Encoding: binary");
      header("Expires: 0");
      header("Cache-Control: must-revalidate");
      header("Pragma: public");
      header("Content-Length: ".filesize($file->get_path()));
      readfile($file->get_path());
    } else {
      throw new Exception("Файл ".$file->get_file_name()." отсутствует!");
    }
  } else {
    throw new Exception("Что-то пошло не так...");
  }
} catch (Exception $exc) {
  define("ERROR", $exc->getMessage());
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}


?>
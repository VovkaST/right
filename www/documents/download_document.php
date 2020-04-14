<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (isset($_GET["id"])) {
	$id = $_GET["id"];
	$db = mysql_connect('localhost', '...', '...', "set names 'utf8'");
  if(!$db) {
    echo '<center><p><b>Невозможно подключиться к серверу базы данных !</b></p></center>';
    exit();
  }
  //Проверяем доступность нужной БД
  if(!mysql_select_db('obv_otk', $db)) {
    echo '<center><p><b>База данных obv_otk недоступна!</b></p></center>';
    exit();
  }
  mysql_query("set names 'cp1251'");
	$sql_select_document_query = mysql_query('
		SELECT
			directory, file_name, need_auth
		FROM
			documents
		WHERE
			id = "'.$id.'"
	') or die("Ошибка SQL: ".mysql_error());
	while ($sql_select_document = mysql_fetch_array($sql_select_document_query)) {
		$dir = $sql_select_document["directory"];
		$name = $sql_select_document["file_name"];
		$source = DIR_DOCS."/".$dir."/".$name;
    $doc_auth = $sql_select_document["need_auth"];
	}
  if (!isset($_SESSION['user']) && $doc_auth == 1) {
    header('location: '.ADDR.'auth.php');
  } else {
    if (file_exists($source)) {
      if (ob_get_level()) {
        ob_end_clean();
      }
      header("Content-Description: File Transfer");
      header("Content-Tipe: application/octet-stream");
      header("Content-Disposition: attachment; filename=\"{$name}\"");
      header("Content-Transfer-Encoding: binary");
      header("Expires: 0");
      header("Cache-Control: must-revalidate");
      header("Pragma: public");
      header("Content-Length: ".filesize($source));
      readfile($source);
      exit;
    }
    else {
      echo "Файл ".$name." отсутствует!";
    }
  }
}
else {
	echo "Ошибка парметра GET";
}
?>
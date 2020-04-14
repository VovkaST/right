<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (isset($_GET["id"])) {
  //query_log($_SESSION['activity_id'], 'refuse_download='.$_GET["id"]);
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      request_file
    FROM
      l_requests
    WHERE
      id ="'.$_GET["id"].'"
  ');
  $result = mysql_fetch_assoc($query);
  $dir = iconv("UTF-8", "Windows-1251", REQUESTS.$result["request_file"]);
  
  if (file_exists($dir)) {
    if (ob_get_level()) {
      ob_end_clean();
    }
    header("Content-Description: File Transfer");
    header("Content-Tipe: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"".basename($dir)."\"");
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Cache-Control: must-revalidate");
    header("Pragma: public");
    header("Content-Length: ".filesize($dir));
    readfile($dir);
    exit;
  }
  else {
    echo 'Файл "'.iconv("Windows-1251", "UTF-8", basename($dir)).'" отсутствует!';
  }
}
if (isset($_GET['wallets'])) {
  foreach($_GET['wallets'] as $k => $v) {
    $wallets[] = $v;
  }
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      w.`number`
    FROM
      `o_wallet` as w
    WHERE
      w.`id` IN ('.implode(',', $wallets).')
  ');
  $wallets = array();
  while($result = mysql_fetch_assoc($query)) {
    $number = $result['number'];
    if (($len = strlen($number)) > 10) {
      $number = substr($number, $len - 10);
    }
    $wallets[] = $number;
  }
  if (count($wallets) > 1) {
    $wallet = 'следующим электронным кошелькам: №№ '.implode(', ', $wallets);
  } else {
    $wallet = 'электронному кошельку № '.$wallets[0];
  }
  $replace[] = iconv("UTF-8", "Windows-1251", $wallet);
  $replace[] = date('.m.Y');
  header('Content-Type: application/msword');
  header('Content-Disposition: inline; filename="QIWI_request.rtf"');
  $out = file_get_contents('QIWI_blank.rtf');
  $out = str_replace(array('wallet', 'currentData'), $replace, $out);
  echo $out;
}
?>
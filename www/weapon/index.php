<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['weapon'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

if ($_SESSION['user']['ovd_id'] != 59) {
  $where[] = 'ovd.`id_ovd` = '.$_SESSION['user']['ovd_id'];
  $ovd='and o.`id_ovd` = '.$_SESSION['user']['ovd_id'];
} else $ovd='';
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Учет "Оружие"' => ''
);
$page_title = 'Учет "Оружие" &ndash; '.$_SESSION['user']['ovd'];

if (empty($_GET['volume']))
  $_GET['volume'] = 'full';

switch ($_GET['volume']) {
  case 'short': include('index_short.php'); break;
  case 'full': include('index_full.php'); break;
  case 'using': include('index_using.php'); break;
  case 'kusp': include('index_kusp.php'); break;
}
?>
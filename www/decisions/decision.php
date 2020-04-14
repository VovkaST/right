<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Формирование учетов' => '/accounting.php'
);
require_once($_SERVER['DOCUMENT_ROOT'].'/head.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
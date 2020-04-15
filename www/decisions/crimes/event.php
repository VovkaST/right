<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));
$object = 2;
switch ($_SESSION['crime']['ais']) {
  case 1:
    include('event_card_fraud.php');
    break;
  case 2:
    include('event_card_drug_dealer.php');
    break;
}
<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));

switch ($_SESSION['crime']['ais']) {
  case 1:
    include('events_dept_fraud.php');
    break;
  case 2:
    include('events_dept_drug_dealer.php');
    break;
}
?>
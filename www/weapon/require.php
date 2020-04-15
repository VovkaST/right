<?php
define('CLASSES', 'd:/www.sites/Classes/');
class Site_DB {
  public $db;
  public $active_id;
  
  public function db_connect() {
    $this->active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db = new mysqli('localhost', 'логин', 'пароль', 'база');
    $this->db->set_charset('utf8');
    mysql_select_db('база');
  }
}

require_once(CLASSES.'class.weapon_account.php');
require_once(CLASSES.'class.weapon.php');
require_once(CLASSES.'class.weapon_decision.php');
require_once(CLASSES.'class.kusp.php');
require_once(CLASSES.'class.crime_case.php');
?>
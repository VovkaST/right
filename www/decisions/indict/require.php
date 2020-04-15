<?php
define('CLASSES', 'd:/www.sites/Classes/');

class Site_DB {
  public $active_id;
  
  public function db_connect() {
    $this->active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    require(KERNEL.'connection.php');
  }
}

require_once(CLASSES.'class.ElFile.php');
?>
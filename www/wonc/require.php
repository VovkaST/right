<?php
define('CLASSES', 'd:/www.sites/Classes/');
class Site_DB {
  public $active_id;
  
  public function db_connect() {
    $this->active_id = (!empty($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    require_once(KERNEL.'connection.php');
  }
}

require_once(CLASSES.'class.ElFile.php');
require_once(CLASSES.'class.event.php');
require_once(CLASSES.'class.event_decision.php');
require_once(CLASSES.'class.orientation.php');
require_once(CLASSES.'class.kusp.php');
require_once(CLASSES.'class.crime_case.php');
require_once(CLASSES.'class.reference.php');
?>
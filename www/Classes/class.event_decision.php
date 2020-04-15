<?php
class Event_decision extends Site_DB {
  private $id;
  private $event_code;
  private $kusp;
  private $dec_date;
  private $dec_code;
  private $decision;
  private $dec_number;
  private $qualification;
  private $proc_par;
  private $emp_person;
  private $emp_position;
  private $term;
  private $prolongator_person;
  private $prolongator_position;
  private $org_target;
  private $notification_number;
  private $notification_date;
  private $notification_method;
  private $error_rec;
  
  public function __construct($decision = 0) {
    $this->on_construct($decision);
  }
  
  private function on_construct($decision) {
    $row = null;
    if ($decision != 0 and is_numeric($decision)) {
      $this->db_connect();
      $query = '
        SELECT
          dh.`id`, dh.`event_code`, dh.`kusp`,
          DATE_FORMAT(dh.`dec_date`, "%d.%m.%Y") as `dec_date`,
          dh.`dec_code`, dh.`decision`, dh.`dec_number`, dh.`qualification`, dh.`proc_par`, dh.`emp_person`, dh.`emp_position`,
          DATE_FORMAT(dh.`term`, "%d.%m.%Y") as `term`,
          dh.`prolongator_person`, dh.`prolongator_position`, dh.`org_target`,
          dh.`notification_number`, dh.`notification_method`,
          DATE_FORMAT(dh.`notification_date`, "%d.%m.%Y") as `notification_date`,
          CASE
            WHEN dh.`error_rec` = 0 THEN "false"
            WHEN dh.`error_rec` = 1 THEN "true"
            ELSE "false"
          END as `error_rec`
        FROM
          `ek_dec_history` as dh
        WHERE
          dh.`id` = '.$decision;
      $result = mysql_query($query) or die('<b>Event_decision constructor error</b>: '.mysql_error().'.Query string: '.$query);
      $row = mysql_fetch_assoc($result);
    }
    if ($row) {
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
    }
  }
  
  public function get_dec_date() {
    return $this->dec_date;
  }
  
  public function get_decision_code() {
    return $this->dec_code;
  }
  
  public function get_decision() {
    return $this->decision;
  }
  
  public function get_dec_number() {
    return $this->dec_number;
  }
  
  public function get_emp_person() {
    return $this->emp_person;
  }
  
  public function get_emp_position() {
    return $this->emp_position;
  }
  
  public function get_qualification() {
    return $this->qualification;
  }
  
  public function get_term() {
    return $this->term;
  }
  
  public function get_org_target() {
    return $this->org_target;
  }
  
  public function is_error() {
    return $this->error_rec;
  }
}
?>
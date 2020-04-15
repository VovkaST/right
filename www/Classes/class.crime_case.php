<?php
class CrimeCase extends Site_DB {
  private $id;
  private $crime_case_number;
  private $crim_case_date;
  private $ovd;
  private $ovd_string;
  private $error;
  
  public function __construct($f = 0) {
    $this->on_construct($f);
  }
  
  private function on_construct($i) {
    $row = null;
    if ($i != 0 and is_numeric($i)) {
      $this->db_connect();
      $query = '
        SELECT
          cc.`id`, cc.`crime_case_number`,
          DATE_FORMAT(cc.`crim_case_date`, "%d.%m.%Y") as `crim_case_date`, cc.`ovd`,
          ovd.`ovd` as `ovd_string`
        FROM
          `l_crime_cases` as cc
        JOIN
          `spr_ovd` as ovd ON
            ovd.`id_ovd` = cc.`ovd`
        WHERE
          cc.`id` = '.$i;
      $result = mysql_query($query) or die('<b>Crime case constructor error</b>: '.mysql_error().' .Query string: '.$query);
      $row = mysql_fetch_assoc($result);
    }
    if ($row) {          // если есть запись с заданным id
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
    } else {
      foreach (get_class_vars('CrimeCase') as $ch => $v) {
        $this->$ch = null;
      }
    }
  }
  
  private function set_last_error($error) {
    $this->error = $error;
  }
  
  public function get_last_error() {
    return $this->error;
  }
  
  public function get_id() {
    return $this->id;
  }
  
  public function set_ovd($ovd) {
    require(KERNEL.'spr_ovd.php');
    if (!is_numeric($ovd) or !isset($spr_ovd[$ovd]))
      die('<b>OVD code is incorrect.</b>');
    
    $this->ovd = $ovd;
    $this->ovd_string = $spr_ovd[$ovd];
  }
  
  public function set_case($сс, $date, $ovd, $id = null) {
    if (!is_numeric($сс))
      die('<b>Value of Crime case number must be a numeric.</b>');
    if (!is_numeric($ovd))
      die('<b>Value of OVD number must be a numeric.</b>');
    $this->crime_case_number = $сс;
    $this->crim_case_date = date('d.m.Y', strtotime($date));
    $this->set_ovd($ovd);
    $this->id = $id;
  }
  
  public function set_date($date) {
    $this->crim_case_date = date('d.m.Y', strtotime($date));
  }
  
  public function get_date() {
    return $this->crim_case_date;
  }
  
  public function set_number($number) {
    if (!is_numeric($number))
      die('<b>Number value must be a numeric.</b>');
    $this->crime_case_number = $number;
  }
  
  public function get_number() {
    return $this->crime_case_number;
  }
  
  public function get_ovd() {
    return $this->ovd;
  }
  
  public function get_ovd_string() {
    return $this->ovd_string;
  }
  
  public function get_hash() {
    return md5(serialize($this));
  }
  
  public function is_empty() {
    return (
        empty($this->crime_case_number) and
        empty($this->crim_case_date) and
        empty($this->ovd)
       ) ? true : false;
  }
  
  public function save() {
    $this->db_connect();
    $query = '
      INSERT IGNORE INTO
        `l_crime_cases`(`crime_case_number`, `crim_case_date`, `ovd`, `create_date`, `create_time`, `active_id`)
      VALUES
        ('.$this->crime_case_number.', "'.date('Y-m-d', strtotime($this->crim_case_date)).'", '.$this->ovd.',
        CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
    ';
    
    if (!mysql_query($query)) {
      $this->set_last_error('<b>Crime case save error (1)</b>: '.mysql_error().' .Query string: '.$query);
      return false;
    }
    $this->id = mysql_insert_id();
    
    if (empty($this->id)) {
      $query = '
        SELECT
          `id`
        FROM
          `l_crime_cases`
        WHERE
          `crime_case_number` = '.$this->crime_case_number.' AND
          `crime_case_year` = '.date('Y', strtotime($this->crim_case_date)).' AND
          `ovd` = '.$this->ovd.'
      ';
      if (!$result = mysql_query($query)) {
        $this->set_last_error('<b>Crime case save error (2)</b>: '.mysql_error().' .Query string: '.$query);
        return false;
      }
      $row = mysql_fetch_assoc($result);
      $this->id = $row['id'];
      
      if (empty($this->id)) {
        $this->set_last_error('<b>Crime case save error (3)</b>: вводимое у/д не найдено в БД. Возможно, ошибка в реквизитах. Уточните регистрационные данные и повторите попытку.');
        return false;
      }
      
      
      
      $query = '
        UPDATE
          `l_crime_cases`
        SET
          `crim_case_date` = "'.date('Y-m-d', strtotime($this->crim_case_date)).'"
        WHERE
          `id` = '.$this->id.' AND
          `crim_case_date` = "0000-00-00"
      ';
      if (!$result = mysql_query($query)) {
        $this->set_last_error('<b>Crime case save error (4)</b>: '.mysql_error().' .Query string: '.$query);
        return false;
      }
    }
    return true;
  }
}
?>
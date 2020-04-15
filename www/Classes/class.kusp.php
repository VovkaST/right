<?php
class Kusp extends Site_DB {
  private $id;
  private $kusp;
  private $date;
  private $ovd;
  private $ovd_string;
  private $ek;
  private $error;
  private $qualif;
  
  public function __construct($f = 0) {
    $this->on_construct($f);
  }
  
  private function on_construct($i) {
    $row = null;
    if ($i != 0 and is_numeric($i)) {
      $this->db_connect();
      $query = '
        SELECT
          k.`id`, k.`kusp`, 
          DATE_FORMAT(k.`date`, "%d.%m.%Y") as `date`, 
          k.`ovd`, ovd.`ovd` as `ovd_string`, k.`ek`
        FROM
          `l_kusp` as k
        JOIN
          `spr_ovd` as ovd ON
            ovd.`id_ovd` = k.`ovd`
        WHERE
          k.`id` = '.$i;
      $result = mysql_query($query) or die('<b>KUSP constructor error</b>: '.mysql_error().' .Query string: '.$query);
      $row = mysql_fetch_assoc($result);
    }
    if ($row) {          // если есть КУСП с заданным id
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
    } else {
      foreach (get_class_vars('Kusp') as $ch => $v) {
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
    if (empty($this->id))
      $this->save();
    return $this->id;
  }
  
  public function set_ovd($ovd) {
    require(KERNEL.'spr_ovd.php');
    if (!is_numeric($ovd) or !isset($spr_ovd[$ovd])) {
      $this->set_last_error('<b>ОВД КУСП</b>: некорректное значение ОВД.');
      return false;
    }
    $this->ovd = (integer)$ovd;
    $this->ovd_string = (string)$spr_ovd[$ovd];
    return true;
  }
  
  public function get_ovd() {
    return $this->ovd;
  }
  
  public function get_ovd_string() {
    return $this->ovd_string;
  }
  
  public function set_number($k) {
    if (!is_numeric($k)) {
      $this->set_last_error('<b>Рег.№ КУСП</b>: значение должно быть числовым.');
      return false;
    }
    $this->kusp = (integer)$k;
    return true;
  }
  
  public function set_qulif($k) {
    if (!is_numeric($k)) {
      $this->set_last_error('<b>Квалификация</b>: значение должно быть числовым.');
      return false;
    }
    $this->qualif = (integer)$k;
    return true;
  }
  
  public function get_kusp() {
    return $this->kusp;
  }
  
  public function set_date($date) {
    try {
      if (empty($date)) {
        $this->reg_date = null;
        return true;
      }
      if (!preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $date))
        throw new Exception('<b>Дата КУСП</b>: Дату необходимо вводить в формате "'.date('d.m.Y').'"');
        
      if (strtotime($date) > strtotime('now'))
        throw new Exception('<b>Дата КУСП</b>: Вводимая дата не может быть больше текущей');
      
      $this->date = $date;
      return true;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
  }
  
  public function get_date() {
    return $this->date;
  }
  
  public function get_ek() {
    return $this->ek;
  }
  
  public function set_kusp($kusp, $date, $ovd, $id = null) {
    $this->id = $id;
    if ($this->set_number($kusp) and $this->set_date($date) and $this->set_ovd($ovd)) {
      return true;
    } else {
      return false;
    }
  }

  public function set_kusp_with_qualif($kusp, $date, $ovd, $qualif, $id = null) {
    $this->id = $id;
    if ($this->set_number($kusp) and $this->set_date($date) and $this->set_ovd($ovd) and $this->set_qulif($qualif)) {
      return true;
    } else {
      return false;
    }
  }  
    
  public function get_kusp_array() {
    $ret = null;
    foreach (get_class_vars('Kusp') as $ch => $v) {
      if ($ch == 'active_id') continue;
      $ret[$ch] = $this->$ch;
    }
    return $ret;
  }
  
  public function set_ek() {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        ek.`id`
      FROM
        `ek_kusp` as ek
      WHERE
        ek.`reg_number` = '.$this->kusp.' AND
        DATE_FORMAT(ek.`reg_date`, "%Y-%m-%d") = "'.date("Y-m-d", strtotime($this->date)).'" AND
        ek.`ovd` = '.$this->ovd.'
      LIMIT 1
    ');
    $result = mysql_fetch_assoc($query);
    $this->ek = $result['id'];
  }
  
  public function get_hash() {
    return md5(serialize($this));
  }
  
  public function save() {
    $this->set_ek();
    $this->db_connect();
    $query = '
      INSERT IGNORE INTO
        `l_kusp`(`kusp`, `date`, `ovd`, `ek`, `create_date`, `create_time`, `active_id`, `st`)
      VALUES
        ('.$this->kusp.', "'.date('Y-m-d', strtotime($this->date)).'", '.$this->ovd.', '.((!empty($this->ek)) ? $this->ek : 'NULL').',
        CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', '.((!empty($this->qualif)) ? $this->qualif : 'NULL').')';
    
    if (!mysql_query($query)) {
      $this->set_last_error('<b>KUSP save error (1)</b>: '.mysql_error().' .Query string: '.$query);
      return false;
    }
    $this->id = mysql_insert_id();
    if (empty($this->id)) {
      $query = '
        SELECT
          `id`
        FROM
          `l_kusp`
        WHERE
          `kusp` = '.$this->kusp.' AND
          `date` = "'.date('Y-m-d', strtotime($this->date)).'" AND
          `ovd` = '.$this->ovd.'
      ';
      if (!$result = mysql_query($query)) {
        $this->set_last_error('<b>KUSP save error (2)</b>: '.mysql_error().' .Query string: '.$query);
        return false;
      }
      $row = mysql_fetch_assoc($result);
      $this->id = $row['id'];
    }
    return true;
  }
}
?>
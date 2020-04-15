<?php

class Delivery extends Site_DB {
  private $id;
  private $ovd;
  private $ovd_string;
  private $reg_number;
  private $reg_date;
  private $reg_year;
  private $reg_time;
  private $protocol;
  private $surname;
  private $name;
  private $patronymic;
  private $borth;
  private $face;
  private $face_addr;
  private $face_doc;
  private $reason;
  private $reason_string;
  private $story;
  private $decision;
  private $decision_string;
  private $decision_datetime;
  private $create_datetime;
  private $update_datetime;
  private $error;
  
  public function __construct($id = 0) {
    $this->on_construct($id);
  }
  
  private function on_construct($id) {
    $row = null;
     
    if (is_numeric($id) and $id > 0) {
      $this->db_connect();
      $query = '
        SELECT
          `id`, `ovd`, `reg_number`, `reg_date`, `reg_year`, `reg_time`, `protocol`, 
          `surname`, `name`, `patronymic`, `borth`, `face`, `face_addr`, `face_doc`, 
          `reason`, `story`, `decision`, `decision_datetime`, 
          `create_datetime`, `update_datetime`
        FROM
          `l_delivery` as d
        WHERE
          d.`id` = '.$id;
      if (!$result = $this->db->query($query)) {
        $this->set_last_error('<b>'.get_class($this).' constructor error</b>: '.$this->db->error.' .Query string: '.$query);
        return false;
      }
      
      $row = $result->fetch_assoc();
      $result->close();
    }
    if ($row) {
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
    }
    return true;
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
    if (!is_numeric($ovd)) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: значение должно быть числовым.');
      return false;
    }
    
    if (empty($this->db))
      $this->db_connect();
    
    $query = 'SELECT `id_ovd`, `ovd` FROM `spr_ovd` WHERE `id_ovd` = '.$ovd;
    $result = $this->db->query($query);
    if ($result->num_rows == 0) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: Значение "'.$ovd.'" вне допустимого диапазона.');
      $result->close();
      return false;
    }
    
    $row = $result->fetch_object();
    $result->close();
    
    $this->ovd = $row->id_ovd;
    $this->ovd_string = $row->ovd;    
    
    return true;
  }
  
  public function get_ovd() {
    return $this->ovd;
  }
  
  public function get_ovd_string() {
    return $this->ovd_string;
  }
  
  public function set_reg_number($num) {
    $num = trim($num);
    if (!is_numeric($num) or strlen($num) > 10)
      return false;
    
    $this->reg_number = (integer)$num;
    return true;
  }
  
  public function get_reg_number() {
    return $this->reg_number;
  }
  
  public function set_reg_time($time) {
    if (!preg_match('/(\d\d:\d\d)(:\d\d){0,1}/', $time, $time))
      return false;
    
    $this->reg_time = $time[1];
    return true;
  }
  
  public function get_reg_time() {
    return $this->reg_time;
  }
  
  public function set_reg_date($date) {
    $date = trim($date);
    if (!$date = check_date_format($date))
      return false;
    
    $this->reg_date = $date;
    $this->reg_year = date('Y', strtotime($date));
  }
  
  public function get_reg_date($format = 'd.m.Y') {
    if (is_null($this->reg_date)) return null;
    return date($format, strtotime($this->reg_date));
  }
  
  public function get_reg_year() {
    return $this->reg_year;
  }
  
  public function set_protocol($prot) {
    $prot = trim($prot);
    
    if (empty($prot))
      return false;
    
    $this->protocol = mb_substr($prot, 0, 20, 'UTF-8');
    return true;
  }
  
  public function get_protocol() {
    return $this->protocol;
  }
  
  public function set_surname($str) {
    $this->surname = mb_convert_case(trim($str), MB_CASE_UPPER);
    return true;
  }
  
  public function get_surname($case = MB_CASE_TITLE) {
    return mb_convert_case($this->surname, $case);
  }
  
  public function set_name($str) {
    $this->name = mb_convert_case(trim($str), MB_CASE_UPPER);
    return true;
  }
  
  public function get_name($case = MB_CASE_TITLE) {
    return mb_convert_case($this->name, $case);
  }
  
  public function set_patronymic($str) {
    $this->patronymic = mb_convert_case(trim($str), MB_CASE_UPPER);
    return true;
  }
  
  public function get_patronymic($case = MB_CASE_TITLE) {
    return mb_convert_case($this->patronymic, $case);
  }
  
  public function set_borth($date) {
    $date = trim($date);
    
    if (!$date = check_date_format($date))
      return false;
    
    $this->borth = $date;
    return true;
  }
  
  public function get_borth($format = 'd.m.Y') {
    if (is_null($this->borth)) return null;
    return date($format, strtotime($this->borth));
  }
  
  public function set_reason($r) {
    if (!is_numeric($r)) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: значение должно быть числовым.');
      return false;
    }
    
    if (empty($this->db))
      $this->db_connect();
    
    $query = 'SELECT `id`, `name` FROM `spr_delivery_reason` WHERE `id` = '.$r;
    $result = $this->db->query($query);
    if ($result->num_rows == 0) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: Значение "'.$r.'" вне допустимого диапазона.');
      $result->close();
      return false;
    }
    
    $row = $result->fetch_object();
    $result->close();
    
    $this->reason = $row->id;
    $this->reason_string = $row->name;    
    
    return true;
  }
  
  public function get_reason() {
    return $this->reason;
  }
  
  public function get_reason_string() {
    return $this->reason_string;
  }
  
  public function set_face($id) {
    if (!is_numeric($id)) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: значение должно быть числовым.');
      return false;
    }
    
    if (empty($this->db))
      $this->db_connect();
    $query = '
      SELECT
        l.`id`,
        TRIM(l.`surname`) as `surname`, TRIM(l.`name`) as `name`, TRIM(l.`fath_name`) as `fath_name`,
        IF(l.`borth` IS NOT NULL AND l.`borth` <> "0000-00-00", DATE_FORMAT(l.`borth`, "%d.%m.%Y"), NULL) as `borth`
      FROM
        `o_lico` as l
      WHERE
        `id` = '.$id;
    
    $result = $this->db->query($query);
    if ($result->num_rows == 0) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: Значение "'.$id.'" вне допустимого диапазона.');
      $result->close();
      return false;
    }
    
    $row = $result->fetch_object();
    $result->close();
    
    $this->face = $row->id;
    $this->set_surname($row->surname);
    $this->set_name($row->name);
    $this->set_patronymic($row->fath_name);
    $this->set_borth($row->borth);
    return true;
  }
  
  public function get_face() {
    return $this->face;
  }
  
  public function set_face_addr($str) {
    $str = trim($str);
    $str = preg_replace(array('/(\r|\n)/sui', '/\s{2,}/ui', '/Ё/u', '/ё/u'), array(' ', ' ', 'Е', 'е'), $str);
    
    if (empty($str))
      return false;
    
    $this->face_addr = mb_substr($str, 0, 150, 'UTF-8');
    return true;
  }
  
  public function get_face_addr() {
    return $this->face_addr;
  }
  
  public function set_face_doc($doc) {
    $doc = trim($doc);
    
    if (!is_numeric($doc)) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: значение должно быть числовым.');
      return false;
    }
    
    $this->face_doc = (integer)$doc;
    return true;
  }
  
  public function get_face_doc() {
    return $this->face_doc;
  }
  
  public function set_story($str) {
    $str = trim($str);
    $str = preg_replace(array('/(\r|\n)/sui', '/\s{2,}/ui', '/Ё/u', '/ё/u'), array(' ', ' ', 'Е', 'е'), $str);
    
    if (empty($str))
      return false;
    
    $this->story = $str;
    return true;
  }
  
  public function get_story() {
    return $this->story;
  }
  
  public function set_decision($dec) {
    if (!is_numeric($dec)) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: значение должно быть числовым.');
      return false;
    }
    
    if (empty($this->db))
      $this->db_connect();
    
    $query = 'SELECT `id`, `name` FROM `spr_delivery_decision` WHERE `id` = '.$dec;
    $result = $this->db->query($query);
    if ($result->num_rows == 0) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: Значение "'.$dec.'" вне допустимого диапазона.');
      $result->close();
      return false;
    }
    
    $row = $result->fetch_object();
    $result->close();
    
    $this->decision = $row->id;
    $this->decision_string = $row->name;    
    
    return true;
  }
  
  public function get_decision() {
    return $this->decision;
  }
  
  public function get_decision_string() {
    return $this->decision_string;
  }
  
  public function set_decision_datetime($dt) {
    if (!$dt = check_datetime_format($dt))
      return false;
    
    $this->decision_datetime = $dt;
  }
  
  public function get_decision_datetime($format = 'd.m.Y H:i:s') {
    if (is_null($this->decision_datetime)) return null;
    return date($format, strtotime($this->decision_datetime));
  }
  
  public function set_create_datetime($dt) {
    if (!$dt = check_datetime_format($dt))
      return false;
    
    $this->create_datetime = $dt;
  }
  
  public function get_create_datetime($format = 'd.m.Y H:i:s') {
    if (is_null($this->create_datetime)) return null;
    return date($format, strtotime($this->create_datetime));
  }
  
  public function set_update_datetime($dt) {
    if (!$dt = check_datetime_format($dt))
      return false;
    
    $this->update_datetime = $dt;
  }
  
  public function get_update_datetime($format = 'd.m.Y H:i:s') {
    if (is_null($this->update_datetime)) return null;
    return date($format, strtotime($this->update_datetime));
  }
  
  public function set_delivery($data) {
    if (!is_array($data)) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: Входные данные должны являться массивом.');
      return false;
    }
    
    foreach ($data as $k => $v) {
      $data[$k] = get_var_in_data_type($v);
    }
    
    if (isset($data['reg_date']) and !($data['reg_date'] = check_date_format($data['reg_date'])))
      unset($data['reg_date']);
    
    if (isset($data['borth']) and !($data['borth'] = check_date_format($data['borth'])))
      unset($data['borth']);
    
    // проверяем прочие свойства и при наличии - добавляем
    foreach ($data as $k => $v) {
      if (in_array($k, array_keys(get_class_vars(get_class($this)))))
        call_user_func(array($this, 'set_'.$k), get_var_in_data_type($v));
    }
   
    return true;   
  }
  
  public function save($con = null) {
    
    if (empty($this->ovd))        $req[] = 'ОВД';
    if (empty($this->reg_number)) $req[] = 'Рег.номер';
    if (empty($this->reg_date))   $req[] = 'Дата доставления';
    if (empty($this->reg_time))   $req[] = 'Время доставления';
    if (empty($this->reason))     $req[] = 'Основание';
    if (empty($this->surname))    $req[] = 'Фамилия';
    if (empty($this->name))       $req[] = 'Имя';
    
    if (isset($req)) {
      $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: Не все обязательные поля заполнены ('.implode(', ', $req).').');
      return false;
    }
    
    if (empty($con) and empty($this->db)) {
      $this->db_connect();
    } elseif (!empty($con)) {
      $this->db = $con;
    }
    
    $this->db->autocommit(false);
    
    $query = '
      INSERT INTO `l_delivery`(`ovd`, `reg_number`, `reg_date`, `reg_time`, 
                               `protocol`, `surname`, `name`, `patronymic`, `borth`, 
                               `face`, `face_addr`, `face_doc`, `reason`, `story`, 
                               `decision`, `decision_datetime`, `create_datetime`, `update_datetime`)
                  VALUES ('.$this->get_ovd().', '.$this->get_reg_number().', "'.$this->get_reg_date('Y-m-d').'", "'.$this->get_reg_time('Y-m-d').'",
                          '.((!is_null($this->get_protocol())) ? '"'.$this->db->real_escape_string($this->get_protocol()).'"' : 'NULL').',
                          "'.$this->db->real_escape_string($this->get_surname(MB_CASE_UPPER)).'",
                          "'.$this->db->real_escape_string($this->get_name(MB_CASE_UPPER)).'", 
                          '.((!is_null($this->get_patronymic())) ? '"'.$this->db->real_escape_string($this->get_patronymic(MB_CASE_UPPER)).'"' : 'NULL').',
                          '.((!is_null($this->get_borth())) ? '"'.$this->get_borth('Y-m-d').'"' : 'NULL').',
                          '.((!is_null($this->get_face())) ? $this->get_face() : 'NULL').',
                          '.((!is_null($this->get_face_addr())) ? $this->get_face_addr() : 'NULL').',
                          '.((!is_null($this->get_face_doc())) ? $this->get_face_doc() : 'NULL').',
                          '.((!is_null($this->get_reason())) ? $this->get_reason() : 'NULL').',
                          '.((!is_null($this->get_story())) ? '"'.$this->db->real_escape_string($this->get_story()).'"' : 'NULL').',
                          '.((!is_null($this->get_decision())) ? $this->get_decision() : 'NULL').',
                          '.((!is_null($this->get_decision_datetime())) ? $this->get_decision_datetime('Y-m-d H:i:s') : 'NULL').',
                          '.((!is_null($this->get_create_datetime())) ? '"'.$this->get_create_datetime('Y-m-d H:i:s').'"' : 'CURRENT_TIMESTAMP').',
                          '.((!is_null($this->get_update_datetime())) ? '"'.$this->get_update_datetime('Y-m-d H:i:s').'"' : 'CURRENT_TIMESTAMP').'
                          )
    ';
    
    if (!$this->db->query($query)) {
    
      if ($this->db->errno == 1062) {
        $query = 'SELECT `id` FROM `l_delivery` WHERE 
                      `ovd` = '.$this->get_ovd().' AND
                      `reg_number` = '.$this->get_reg_number().' AND
                      `reg_year` = '.$this->get_reg_year();
        
       
        if (!$result = $this->db->query($query)) {
          $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: '.$this->db->error.' .Query string: '.$query);
          return false;
        }
        
        $row = $result->fetch_object();
        $result->close();
        
        if (empty($row->id)) {
          $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: `id` записи не опеределен.');
          return false;
        }
          
        $this->id = $row->id;
        
      } else {
        $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: '.$this->db->error.' .Query string: '.$query);
        return false;
      }
      
    } else {
      $this->id = $this->db->insert_id;
    }
    return true;
  }
}
?>
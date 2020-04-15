<?php
define('FACEREL_F2', 'Ф2 (Лицо, совершившее преступление)');
define('FACEREL_F6', 'Ф6 (Решение суда первой инстанции)');
define('FACEREL_REFUSAL', 'Электронные копии постановлений об отказе в ВУД');
define('FACEREL_FRAUD', 'АИС "Мошенник"');
define('FACEREL_DRUG_DEALER', 'АИС "Наркодилер"');
define('FACEREL_DELIVERY', 'Доставления в ОВД');


class Face extends Site_DB {
  private $id;
  private $surname;
  private $name;
  private $fath_name;
  private $borth;
  private $leg_code;
  private $hash;
  private $relatives;
  private $error;
  
  public function __construct($id = 0, $conn = null) {
    $this->on_construct($id);
    if (!is_null($conn))
      $this->db = $conn;
  }
  
  private function on_construct($id) {
    $row = null;
    
    if (is_numeric($id) and $id > 0) {
      $where = 'l.`id` = '.$id.' AND l.`hash` IS NULL';
      
    } elseif (!is_numeric($id) and preg_match('/[a-z0-9]{32}/is', trim($id))) {
      $where = 'MATCH(l.`hash`) AGAINST ("'.$id.'") LIMIT 1';
    }
    
     
    if (isset($where)) {
    
      if (empty($this->db))
        $this->db_connect();
      
      $query = '
        SELECT
          l.`id`,
          TRIM(l.`surname`) as `surname`, TRIM(l.`name`) as `name`, TRIM(l.`fath_name`) as `fath_name`,
          IF(l.`borth` IS NOT NULL AND l.`borth` <> "0000-00-00", DATE_FORMAT(l.`borth`, "%d.%m.%Y"), NULL) as `borth`,
          l.`leg_code`, l.`hash`
        FROM
          `o_lico` as l
        WHERE
          '.$where;
      if (!$result = $this->db->query($query)) {
        $this->set_last_error('<b>Face constructor error</b>: '.$this->db->error.' .Query string: '.$query);
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
  
  public function set_face($data, $req_borth = true) {
    if (!is_array($data)) {
      $this->set_last_error('<b>Face::set_face</b>: Входные данные должны являться массивом.');
      return false;
    }
    
    foreach ($data as $k => $v) {
      $data[$k] = get_var_in_data_type($v);
    }

    if (!empty($data['borth'])) {
      // если формат "ГГГГ-ММ-ДД", то преобразуем в "ДД.ММ.ГГГГ"
      if (preg_match("|^[1-2]\d{3}-[0-1]\d\-[0-3]\d$|", $data['borth'])) {
        $data['borth'] = date('d.m.Y', strtotime($data['borth']));
      }
      // если формат не "ДД.ММ.ГГГГ", то удаляем элемент
      if (!preg_match("|^[0-3]\d[\./][0-1]\d[\./][1-2]\d{3}$|", $data['borth']))
        unset($data['borth']);
      else
        $data['borth'] = preg_replace('|/|', '.', $data['borth']);
    }

    if (empty($data['surname']) or empty($data['name']) or ($req_borth and empty($data['borth']))) {
      $this->set_last_error('<b>Face::set_face</b>: Отсутствуют обязательные параметры.');
      return false;
    }
    
    $this->set_surname($data['surname']);
    $this->set_name($data['name']);
    if (!empty($data['fath_name'])) {
      $this->set_fath_name($data['fath_name']);
      unset($data['fath_name']);
    }
    if (!empty($data['borth'])) {
      $this->set_borth($data['borth']);
      unset($data['borth']);
    }
    
    unset($data['surname'], $data['name']);
    
    // проверяем прочие свойства и при наличии - добавляем
    foreach ($data as $k => $v) {
      if (in_array($k, array_keys(get_class_vars(get_class($this)))))
        $this->$k = get_var_in_data_type($v);
    }
   
    return true;   
  }
  
  public function set_surname($str) {
    $this->surname = mb_convert_case(trim($str), MB_CASE_UPPER);
  }
  
  public function get_surname($case = MB_CASE_TITLE) {
    return mb_convert_case($this->surname, $case);
  }
  
  public function set_name($str) {
    $this->name = mb_convert_case(trim($str), MB_CASE_UPPER);
  }
  
  public function get_name($case = MB_CASE_TITLE) {
    return mb_convert_case($this->name, $case);
  }
  
  public function set_fath_name($str) {
    $this->fath_name = mb_convert_case(trim($str), MB_CASE_UPPER);
  }
  
  public function get_fath_name($case = MB_CASE_TITLE) {
    return mb_convert_case($this->fath_name, $case);
  }
  
  public function set_borth($date) {
    $date = trim($date);
    
    if (!empty($date)) {
      if (preg_match("|[1-2]\d{3}-[0-1]\d\-[0-3]\d\^$|", $date)) {
        $date = date('d.m.Y', strtotime($date));
      }
      
      if (!preg_match("|^[0-3]\d[\./][0-1]\d[\./][1-2]\d{3}$|", $date)) {
        $this->set_last_error('<b>Face::set_borth</b>: Неверный формат даты.');
        return false;
      } else $date = preg_replace('|/|', '.', $date);
      
    } else return false;
    
    $this->borth = $date;
    return true;
  }
  
  public function get_borth($format = 'd.m.Y') {
    if (is_null($this->borth)) return null;
    return date($format, strtotime($this->borth));
  }
  
  public function get_man() {
    $ret[] = $this->get_surname();
    $ret[] = $this->get_name();
    $ret[] = $this->get_fath_name();
    $ret[] = $this->get_borth();
    return implode(' ', $ret);
  }
  
  public function get_hash() {
    if (!is_null($this->hash))
      return $this->hash;
  
    $_t[] = $this->get_surname(MB_CASE_UPPER);
    $_t[] = $this->get_name(MB_CASE_UPPER);
    $_t[] = $this->get_fath_name(MB_CASE_UPPER);
    $_t[] = $this->get_borth('Y-m-d');
    return md5(implode('$', $_t));
  }
  
  public function get_photos($type = null) {
    if (is_null($this->id)) 
      return false;
    
    $where[] = 'p.`face` = '.$this->id;
    if (!is_null($type) and is_numeric($type))
      $where[] = 'p.`type` = '.$type;
    
    if (empty($this->db))
      $this->db_connect();
      
    $query = '
      SELECT
        p.`type`, pt.`name` as `type_string`,
        IF(p.`date` IS NOT NULL AND p.`date` <> "0000-00-00", DATE_FORMAT(p.`date`, "%d.%m.%Y"), NULL) as `date`,
        p.`photo_path`, p.`note`
      FROM
        `l_photo` as p
      JOIN
        `spr_photo_types` as pt ON
          pt.`id` = p.`type`
      WHERE
        '.implode(' AND ', $where).'
      ORDER BY
        p.`date` DESC, p.`type`
    ';
    
    $result = $this->db->query($query);
    if ($result->num_rows != 0) {
      while ($row = $result->fetch_object()) {
        $ret[] = $row;
      }
      $result->close();
      return $ret;
    }
    return false;
  }
  
  public function get_ava() {
    if (is_null($this->id)) 
      return false;
    
    if (empty($this->db))
      $this->db_connect();
    $query = '
      SELECT
        p.`type`, "Фас" as `type_string`,
        IF(p.`date` IS NOT NULL AND p.`date` <> "0000-00-00", DATE_FORMAT(p.`date`, "%d.%m.%Y"), NULL) as `date`,
        p.`photo_path`, p.`note`
      FROM
        `l_photo` as p
      WHERE
        p.type = 1
        AND p.`face` = '.$this->id.'
      ORDER BY
        p.`date` DESC
      LIMIT 1
    ';
    
    // если есть фото "Фас"
    $result = $this->db->query($query);
    if ($result->num_rows != 0) {
      $row = $result->fetch_object();
      $result->close();
      return $row;
    }
    $result->close();
    
    // ищем любую последнюю фотографию
    $query = '
      SELECT
        p.`type`, pt.`name` as `type_string`,
        IF(p.`date` IS NOT NULL AND p.`date` <> "0000-00-00", DATE_FORMAT(p.`date`, "%d.%m.%Y"), NULL) as `date`,
        p.`photo_path`, p.`note`
      FROM
        `l_photo` as p
      JOIN
        `spr_photo_types` as pt ON
          pt.`id` = p.`type`
      WHERE
        p.`face` = '.$this->id.'
      ORDER BY
        p.`date` DESC
      LIMIT 1
    ';
    
    // если есть фото "Фас"
    $result = $this->db->query($query);
    if ($result->num_rows != 0) {
      $row = $result->fetch_object();
      $result->close();
      return $row;
    }
    $result->close();
    
    return false;
  }
  
  public function is_relatives($par = array(FACEREL_F2, FACEREL_F6, FACEREL_REFUSAL, FACEREL_FRAUD, FACEREL_DRUG_DEALER, FACEREL_DELIVERY)) {
    if (!is_array($par))
      $par = (array)$par;
    
    if (empty($this->db))
      $this->db_connect();
      
    $query = array();
    
    foreach ($par as $key) {
      switch ($key) {
        case FACEREL_F2:
          $query[] = '
            SELECT
              "f2" as `section`, "'.$this->db->real_escape_string($key).'" as `name`, f2.`id`
            FROM
              `ic_f2` as f2
            WHERE
              f2.`d04a_f2` = 1 
              AND f2.`face` = '.$this->id.'
            GROUP BY
              f2.`d01o_f2`, f2.`d01_f2`, f2.`d3n_f2`, YEAR(f2.`d06_f2`)
          ';
          break;
        
        case FACEREL_F6:
          $query[] = '
            SELECT
              "f6" as `section`, "'.$this->db->real_escape_string($key).'" as `name`, f6.`id`
            FROM
              `ic_f6` as f6
            WHERE
              f6.`face` = '.$this->id.'
            GROUP BY
              f6.`d01o_f6`, f6.`d01_f6`, f6.`d03n_f6`, f6.`d03g_f6`
          ';
          break;
        
        case FACEREL_REFUSAL:
          $query[] = '
            SELECT
              "ref" as `section`, "'.$this->db->real_escape_string($key).'" as `name`, dl.`id`
            FROM
              `l_dec_lico` as dl
            WHERE
              dl.`face` = '.$this->id.'
              AND dl.deleted = 0
          ';
          break;
        
        case FACEREL_FRAUD:
          $query[] = '
            SELECT
              "fr" as `section`, "'.$this->db->real_escape_string($key).'" as `name`, r.id
            FROM
              `l_relatives` as r
            WHERE
              r.from_obj = '.$this->id.'
              AND r.from_obj_type = 1
              AND r.ais = 1
          ';
          break;
        
        case FACEREL_DRUG_DEALER:
          $query[] = '
            SELECT
              "dd" as `section`, "'.$this->db->real_escape_string($key).'" as `name`, r.id
            FROM
              `l_relatives` as r
            WHERE
              r.from_obj = '.$this->id.'
              AND r.from_obj_type = 1
              AND r.ais = 2
          ';
          break;
        
        case FACEREL_DELIVERY:
          $query[] = '
            SELECT 
              "dv" as `section`, "'.$this->db->real_escape_string($key).'" as `name`, d.id
            FROM
              `l_delivery` as d
            WHERE
              d.`face` = '.$this->id;
          break;
      }
    }
    
    if (empty($query))
      return false;
    
    $query = implode(' UNION ', $query);
    $result = $this->db->query($query);
    
    $ret = array();
    if ($result->num_rows == 0) {
      $result->close();
      return false;
    }
    
    while ($row = $result->fetch_object()) {
      $this->relatives[$row->section]['name'] = $row->name;
      $this->relatives[$row->section]['id'][] = $row->id;
    }
    $result->close();
    
    return true;
  }
  
  public function get_relatives($par = null) {
    if (empty($this->relatives))
      return false;
    
    if (empty($par))
      return $this->relatives;
      
    switch ($par) {
      case 'f2':
      case 'f6':
      case 'ref':
      case 'fr':
      case 'dd':
      case 'dv':
        return $this->relatives[$par];
        break;
      
      default:
        return false;
        break;
    }
    
    
  }
  
  public function save($con = null) {
    if (empty($this->surname)) $req[] = 'Фамилия';
    if (empty($this->name))    $req[] = 'Имя';
    if (empty($this->borth))   $req[] = 'Дата рождения';
    
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
    
    $query = 'INSERT INTO `o_lico` (`surname`, `name`, `fath_name`, `borth`, `leg_code`,
                                    `create_date`, `create_time`, `active_id`,
                                    `update_date`, `update_time`, `update_active_id`)
             VALUES ("'.$this->db->real_escape_string($this->get_surname(MB_CASE_UPPER)).'",
                     "'.$this->db->real_escape_string($this->get_name(MB_CASE_UPPER)).'",
                     "'.$this->db->real_escape_string($this->get_fath_name(MB_CASE_UPPER)).'",
                     "'.$this->get_borth('Y-m-d').'",
                     '.((!empty($this->leg_code)) ? $this->leg_code : 'NULL').',
                     CURRENT_DATE, CURRENT_TIME, '.$this->active_id.',
                     CURRENT_DATE, CURRENT_TIME, '.$this->active_id.'
             )';
    if (!$this->db->query($query)) {
    
      if ($this->db->errno == 1062) {
        $query = 'SELECT `id`, `hash`, `leg_code` FROM `o_lico` WHERE 
                      `surname` = "'.$this->db->real_escape_string($this->get_surname(MB_CASE_UPPER)).'" AND
                      `name` = "'.$this->db->real_escape_string($this->get_name(MB_CASE_UPPER)).'" AND
                      `fath_name` = "'.$this->db->real_escape_string($this->get_fath_name(MB_CASE_UPPER)).'" AND
                      `borth` = "'.$this->get_borth('Y-m-d').'"';
        
       
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
        $this->hash = $row->hash;
        
        if (empty($row->leg_code) and !empty($this->leg_code)) {
          $query = ' UPDATE `o_lico` SET `leg_code` = '.$this->leg_code.' WHERE `id` = '.$this->id;
          
          if (!$this->db->query($query)) {
            $this->set_last_error('<b>'.get_class($this).'::'.__FUNCTION__.'</b>: '.$this->db->error.' .Query string: '.$query);
            return false;
          }
        }
        
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
<?php
class Photo extends Site_DB {
  private $id;
  private $type;
  private $type_string;
  private $date;
  private $face;
  private $temp_path = null;
  private $photo_path;
  private $note;
  private $deleted = false;
  private $error;
  
  public function __construct($id = 0) {
    $this->on_construct($id);
  }
  
  private function on_construct($id) {
    $row = null;
    if ($id != 0 and is_numeric($id)) {
      $this->db_connect();
      $query = '
        SELECT
          p.`id`, p.`type`, pt.`name` as `type_string`,
          DATE_FORMAT(p.`date`, "%d.%m.%Y") as `date`,
          p.`face`, p.`photo_path`, p.`note`,
          IF(p.`deleted` = 0, "false", "true") as `deleted`
        FROM
          `l_photo` as p
        JOIN
          `spr_photo_types` as pt ON
            pt.`id` = p.`type`
        WHERE
          p.`id` = '.$id;
      if (!$result = $this->db->query($query)) {
        $this->set_last_error('<b>Photo constructor error</b>: '.$this->db->error.' .Query string: '.$query);
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
  
  public function set_type($t) {
    try {
    
      if (!is_numeric($t) or (integer)$t < 1)
        throw new Exception('Тип должен представлять числовое значение.');
      
      $this->db_connect();
      $query = 'SELECT `id`, `name` FROM `spr_photo_types` WHERE `id` = '.$t;
      
      if (!$result = $this->db->query($query))
        throw new Exception($this->db->error.' .Query string: '.$query);
      
      $row = $result->fetch_object();
      $result->close();
      
      if (empty($row))
        throw new Exception('Значение "'.$t.'" вне допустимого диапазона.');
      
      $this->type = (integer)$row->id;
      $this->type_string = $row->name;
    
    } catch (Exception $exc) {
    
      $this->set_last_error('<b>Photo::set_type</b>: '.$exc->getMessage());
      return false;
    }
    
    return true;
  }
  
  public function get_type() {
    return $this->type;
  }
  
  public function get_type_string() {
    return $this->type_string;
  }
  
  public function set_date($date) {
    $date = trim($date);
    
    if (!empty($date)) {
      if (preg_match("|[1-2]\d{3}-[0-1]\d\-[0-3]\d\^$|", $date)) {
        $date = date('d.m.Y', strtotime($date));
      }
      
      if (!preg_match("|^[0-3]\d[\./][0-1]\d[\./][1-2]\d{3}$|", $date)) {
        $this->set_last_error('<b>Photo::set_date</b>: Неверный формат даты.');
        return false;
      } else $date = preg_replace('|/|', '.', $date);
      
    } else return false;
    
    $this->date = $date;
    return true;
  }
  
  public function get_date($format = 'd.m.Y') {
    return date($format, strtotime($this->date));
  }
  
  public function set_temp_path($p) {
    $p = trim($p);
    $p = str_replace('\\', '/', $p);
    
    if (is_file(mb_convert_encoding($p, 'Windows-1251', 'UTF-8'))) {
      $this->temp_path = $p;
      return true;
    }
    
    $cd = getcwd();
    $cd = str_replace('\\', '/', $cd);
    $cd .= '/'.$p;
    if (is_file(mb_convert_encoding($cd.'/'.$p, 'Windows-1251', 'UTF-8'))) {
      $this->temp_path = $p;
      return true;
    }
    
    $this->set_last_error('<b>Photo::set_temp_path</b>: Не удается найти указанный файл "'.$cd.'".');
    return false;
  }
  
  public function get_temp_path() {
    return $this->temp_path;
  }
  
  public function set_photo_path($p) {
    $p = trim($p);
    
    $pi = pathinfo($p);
    if (!empty($pi['extension']))    // если задается файл (существует расширение)
      $_t = mb_convert_encoding($pi['dirname'], 'Windows-1251', 'UTF-8');  // извлекаем каталог назначения
    else {
      $_t = mb_convert_encoding($p, 'Windows-1251', 'UTF-8');    // иначе просто извлекаем каталог назначения
      if (!preg_match('/^(.*)(\\\|\/)$/s', $p))
        $p .= '/';                                               // проверяем, чтобы последний символ был слэшем 
    }
      
    if (!is_dir($_t) and !mkdir($_t, 0777, true)) {
      $this->set_last_error('<b>Photo::set_photo_path</b>: не удается создать каталог назначения.');
      return false;
    }   
      
    $this->photo_path = $p;
    return true;
  }
  
  public function get_photo_path() {
    return $this->photo_path;
  }
  
  public function set_note($p) {
    $this->note = mb_substr(trim($p), 0, 255, 'UTF-8');
    return true;
  }
  
  public function get_note() {
    return $this->note;
  }
  
  public function set_face($p) {
    if (!is_numeric($p) or $p < 1) {
      $this->set_last_error('<b>Photo::set_face</b>: Тип должен представлять числовое значение.');
      return false;
    }
    $this->face = (integer)$p;
  }
  
  public function get_face() {
    return $this->face;
  }
  
  public function is_deleted() {
    return $this->deleted;
  }
  
  private function get_hash() {
    
    if (empty($this->temp_path)) {
      $this->set_last_error('<b>Photo::get_hash</b>: не указан временный файл.');
      return false;
    }
    
    return md5(filesize(mb_convert_encoding($this->temp_path, 'Windows-1251', 'UTF-8')));
  }
  
  public function save($con = null, $move = false) {
    try {
      if (empty($this->type))       $req[] = 'Тип фото';
     // if (empty($this->date))       $req[] = 'Дата фото';
      if (empty($this->face))       $req[] = 'Связь "Лицо->Фото"';
      
      if (empty($this->id) and empty($this->temp_path)) 
                                    $req[] = 'Временный каталог хранения оригинала изображения';
      if (empty($this->id) and empty($this->photo_path)) 
                                    $req[] = 'Каталог назначения';
      
      if (!empty($req))
        throw new Exception('Заполнены не все обязательные реквизиты ('.implode(', ', $req).')');
      
      
      if (empty($con) and empty($this->db)) {
        $this->db_connect();
      } else {
        $this->db = $con;
      }
      
      $this->db->autocommit(false);
        
      if (empty($this->id)) {
      
        $pi = pathinfo($this->photo_path);
        $this->photo_path = (empty($pi['extension'])) ? $this->photo_path.$this->get_hash().'.jpg' : $this->photo_path;  // если не задан6о расширение файла, то генерируем новое имя файла
        
        
        $from = mb_convert_encoding($this->temp_path, 'Windows-1251', 'UTF-8');
        $to = mb_convert_encoding($this->photo_path, 'Windows-1251', 'UTF-8');
        
        if (!copy($from, $to)) 
          throw new Exception('Невозможно скопировать файл `'.$from.'` в `'.$to.'`!');
        
        if ($move) {
          if (!unlink($from))
            throw new Exception('Невозможно удалить файл `'.$from.'`!');
        }
      
        $query = '
          INSERT INTO `l_photo`(`type`,`date`,`face`,`photo_path`,`note`,
                                `create_date`,`create_time`,`active_id`,
                                `update_date`,`update_time`,`update_active_id`)
          VALUES ('.$this->type.', 
                  '.((!is_null($this->get_date())) ? '"'.$this->get_date('Y-m-d').'"' : 'NULL').', 
                  '.$this->face.',
                  "'.substr($this->photo_path, -69, 69).'", 
                  '.((!empty($this->note)) ? '"'.$this->db->real_escape_string($this->note).'"' : 'NULL').',
                  CURRENT_DATE, CURRENT_TIME, '.$this->active_id.',
                  CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
        ';
        if (!$this->db->query($query)) {
          if ($this->db->errno == 1062) {
            $this->set_last_error('<b>Photo::save</b>: Такое фотоизображение уже существует.');
          } else
            throw new Exception($this->db->error.' .Query string: '.$query);
        }
      } else {
        
        $query = '
          UPDATE `l_photo`
          SET
            `type` = '.$this->type.',
            `date` = '.((!is_null($this->get_date())) ? '"'.$this->get_date('Y-m-d').'"' : 'NULL').',
            `note` = '.((!empty($this->note)) ? '"'.$this->db->real_escape_string($this->note).'"' : 'NULL').',
            `update_date` = CURRENT_DATE,
            `update_time` = CURRENT_TIME,
            `update_active_id` = '.$this->active_id.'
          WHERE
            `id` = '.$this->id;
            
        if (!$this->db->query($query))
          throw new Exception($this->db->error.' .Query string: '.$query);
        
      }
      
    } catch (Exception $exc) {
    
      $this->set_last_error('<b>Photo::save</b>: '.$exc->getMessage());
      return false;
    }
    
    return true;
  }
}





?>
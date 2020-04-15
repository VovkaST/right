<?php
class WeaponDecision extends Site_DB {
  private $id;
  private $weapon;
  private $date;
  private $number;
  private $decision;
  private $decision_string;
  private $quantity;
  private $case;
  private $page;
  private $error;
  private $tmp_wpn;
  
  public function __construct($f = 0) {
    if ($this->on_construct($f))
      return true;
    return $this->get_last_error();
  }
  
  private function on_construct($i) {
    $row = null;
    if ($i != 0 and is_numeric($i)) {
      $this->db_connect();
      $query = '
        SELECT
          wd.`id`, wd.`weapon`, 
          DATE_FORMAT(wd.`date`, "%d.%m.%Y") as `date`,
          wd.`number`, wd.`decision`, sd.`name` as `decision_string`,
          wd.`quantity`, wd.`case`, wd.`page`
        FROM
          `l_weapons_decision` as wd
        JOIN
          `spr_decision_in_arms` as sd ON
            sd.`id` = wd.`decision`
        WHERE
          wd.`id` = '.$i;
      if (!$result = $this->db->query($query)) {
        if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
          $this->set_last_error('<b>WeaponDecision constructor error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
        } else {
          $this->set_last_error('<center><b>Ошибка объекта "Решение"!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
        }
        return false;
      }
      $row = $result->fetch_assoc();
      $result->close();
    }
    if ($row) {
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
      $this->weapons = null;
    } else {
      foreach (get_class_vars('WeaponDecision') as $ch => $v) {
        $this->$ch = null;
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
  
  public function set_weapon($w = null) {
    if (empty($w)) {
      $this->weapon = null;
      return true;
    }
    if (is_numeric($w) or is_object($w)) {
      $this->weapon = $w;
      return true;
    } else {
      $this->set_last_error('<b>Оружие (решение)</b>: значение должно быть числовым или являться объектом.');
      return false;
    }
  }
  
  public function get_weapon() {
    return $this->weapon;
  }
  
  public function set_date($d = null) {
    try {
      if (empty($d)) {
        $this->date = null;
        return true;
      }
      if (!preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $d))
        throw new Exception('<b>Дата (решение)</b>: Дату необходимо вводить в формате "'.date('d.m.Y').'"');
      
      if (strtotime($d) > strtotime('now'))
        throw new Exception('<b>Дата (решение)</b>: Вводимая дата не может быть больше текущей');
      
      $this->date = $d;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
    return true;
  }
  
  public function get_date() {
    return $this->date;
  }
  
  public function set_number($n = null) {
    if (empty($n)) {
      $this->number = null;
      return true;
    }
    $this->number = substr($n, 0, 10);
  }
  
  public function get_number() {
    return $this->number;
  }
  
  public function set_decision($d = null) {
    $res = true;
    try {
      if (empty($d)) {
        $this->decision = null;
        $this->decision_string = null;
        return true;
      }
      
      if (!is_numeric($d))
        throw new Exception('<b>Решение</b>: значение должно быть числовым.');
        
      $this->db_connect();
      $query = 'SELECT `id`, `name` FROM `spr_decision_in_arms` WHERE `id` = '.$d;
      
      if (!$result = $this->db->query($query)) {
        if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
          throw new Exception('<b>Set_decision error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
        } else {
          throw new Exception('<center><b>Ошибка выбора решения!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
        }
      }
        
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Решение:</b>: значение "'.$d.'" вне допустимого диапазона.');
      
      $this->decision = (integer)$row['id'];
      $this->decision_string = (string)$row['name'];
      
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      $res = false;
    }
    return $res;
  }
  
  public function get_decision() {
    return (empty($this->decision)) ? null : (integer)$this->decision;
  }
  
  public function get_decision_string() {
    return $this->decision_string;
  }
  
  public function set_quantity($q = 1) {
    if (is_numeric($q)) {
      $this->quantity = (integer)$q;
      return true;
    } else {
      $this->set_last_error('<b>Количество (решение)</b>: значение должно быть числовым.');
      return false;
    }
  }
  
  public function get_quantity() {
    return $this->quantity;
  }
  
  public function set_case($c = null) {
    if (empty($c)) {
      $this->case = null;
    } else {
      $this->case = substr($c, 0, 15);
    }
    return true;
  }
  
  public function get_case() {
    return $this->case;
  }
  
  public function set_page($p = null) {
    if (empty($p)) {
      $this->page = null;
      return true;
    }
    if (!is_numeric($p)) {
      $this->set_last_error('<b>Страница дела (решение)</b>: значение должно быть числовым.');
      return false;
    }
    if (strlen($p) > 3) {
      $this->set_last_error('<b>Страница дела (решение)</b>: значение "'.$p.'" превышает допустимое.');
      return false;
    }
    $this->page = $p;
    return true;
  }
  
  public function get_page() {
    return $this->page;
  }
  
  public function set_tmp_wpn($wpn = null) {
    if (empty($wpn)) {
      $this->tmp_wpn = null;
      return true;
    }
    if (!is_array($wpn)) {
      $this->tmp_wpn = array($wpn => 1);
      return true;
    } else {
      $this->tmp_wpn = $wpn;
    }
  }
  
  public function get_tmp_wpn() {
    return $this->tmp_wpn;
  }
  
  public function save($con = null) {
    try {
    
      if (empty($this->weapon) and empty($this->tmp_wpn))
        $emptyF[] = 'Связь "Оружие"';
      if (empty($this->date))
        $emptyF[] = '"Дата"';
      if (empty($this->number) and $this->get_decision() != 2)
        $emptyF[] = '"Рег.номер"';
      if (empty($this->decision))
        $emptyF[] = '"Решение"';
      
      if (in_array($this->get_decision(), array(1, 4))) {
        if (empty($this->case))
          $emptyF[] = '"№ дела"';
        if (empty($this->page))
          $emptyF[] = '"Страница"';
      }
      
      
      if (!empty($this->weapon)) {
        if (empty($this->quantity)) {
          $this->set_tmp_wpn($this->weapon);
        } else {
          $this->set_tmp_wpn(array($this->weapon => $this->quantity));
        }
      }
      
      if (empty($con)) {
        $this->db_connect();
      } else {
        $this->db = $con;
      }
      $this->db->autocommit(false);
      
      foreach ($this->tmp_wpn as $wpn => $qnt) {
        if (empty($qnt)) {
          $emptyF[] = '"Количество"';
          break;
        }
        
        $query = '
          INSERT INTO 
            `l_weapons_decision`(`weapon`, `date`, `number`, `decision`, `quantity`, `case`, `page`, 
                                 `create_date`, `create_time`, `active_id`, `update_date`, `update_time`, `update_active_id`)
          VALUES(
            '.$wpn.',
            "'.date('Y-m-d', strtotime($this->date)).'",
            "'.$this->db->real_escape_string($this->number).'", '.$this->decision.', '.$qnt.',
            '.((!empty($this->case)) ? '"'.$this->db->real_escape_string($this->case).'"' : 'NULL').',
            '.((!empty($this->page)) ? '"'.$this->db->real_escape_string($this->page).'"' : 'NULL').',
            CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
        ';
        if (!$this->db->query($query)) {
          if ($this->db->errno == 1062) {
            throw new Exception('В БД уже существует запись с таким решением и датой.');
          } else {
            if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
              throw new Exception('<b>Decision save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
            } else {
              throw new Exception('<center><b>Ошибка сохранения!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
            }
          }
        }
        
        $d = $this->db->insert_id;
        
        $wpn = new Weapon($wpn);
        
        switch ($this->get_decision()) {
          case 1:
          case 2:
          case 3:
          case 4:
            if ($wpn->get_quantity_total() < $qnt)
              throw new Exception('<b>Ошибка</b>: остаток ('.$wpn->get_quantity_total().') меньше списываемого количества ('.$qnt.').');
              
            $wpn->set_quantity_total($wpn->get_quantity_total() - $qnt);
            $wpn->set_last_decision($d);
            break;
          case 5:
            if (($wpn->get_quantity_total() + $qnt) > $wpn->get_quantity_incoming())
              throw new Exception('<b>Ошибка</b>: принятое количество ('.$wpn->get_quantity_total().') меньше возвращаемого ('.$qnt.').');
              
            $wpn->set_quantity_total($wpn->get_quantity_total() + $qnt);
            $wpn->set_last_decision($d);
            break;
          case 6:
          case 7:
            // неучитываемые решения
            break;
        }
        
        if (!$wpn->save($this->db))
          throw new Exception($wpn->get_last_error());
      }
      
      if (!empty($emptyF))
        throw new Exception('<b>Решение</b>: не заполнены поля '.implode(', ', $emptyF).'.');
      
      return true;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
  }
}
?>
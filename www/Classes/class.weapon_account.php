<?php
class WeaponAccount extends Site_DB {
  private $id;
  private $ovd;
  private $ovd_string;
  private $reg_number;
  private $reg_date;
  private $base_receiving;
  private $base_receiving_string;
  private $purpose_placing;
  private $purpose_placing_string;
  private $kusp;
  private $crime_case;
  private $incoming_number;
  private $incoming_date;
  private $weapons;
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
          wa.`id`, wa.`ovd`, ovd.`ovd` as `ovd_string`,
          DATE_FORMAT(wa.`reg_date`, "%d.%m.%Y") as `reg_date`, wa.`reg_number`,
          wa.`base_receiving`, brw.`name` as `base_receiving_string`,
          wa.`purpose_placing`, pp.`name` as `purpose_placing_string`,
          wa.`kusp`, wa.`crime_case`, wa.`incoming_number`, DATE_FORMAT(wa.`incoming_date`, "%d.%m.%Y") as `incoming_date`
        FROM
          `l_weapons_account` as wa
        LEFT JOIN
          `spr_ovd` as ovd ON
            ovd.`id_ovd` = wa.`ovd`
        LEFT JOIN
          `spr_base_receiving_weapons` as brw ON
            brw.`id` = wa.`base_receiving`
        LEFT JOIN
          `spr_purpose_placing` as pp ON
            pp.`id` = wa.`purpose_placing`
        WHERE
          wa.`id` = '.$i.'
          AND wa.`deleted` = 0
        ';
      if (!$result = $this->db->query($query)) {
        $this->set_last_error('<b>WeaponAccount constructor error</b>: '.$this->db->error.' .Query string: '.$query);
        return false;
      }
      $row = $result->fetch_assoc();
      $result->close();
    }
    if ($row) {          // если есть КУСП с заданным id
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
      $this->weapons = null;
    } else {
      foreach (get_class_vars('WeaponAccount') as $ch => $v) {
        $this->$ch = null;
      }
    }
    return true;
  }
  
  public function full_data() {
    if (!empty($this->kusp)) {
      $this->kusp = new Kusp($this->kusp);
    }
    
    if (!empty($this->crime_case)) {
      $this->crime_case = new CrimeCase($this->crime_case);
    }
    
    if (empty($this->id))
      return true;
    
    $this->db_connect();
    $query = 'SELECT `weapon_type`, `id` FROM `l_weapons` WHERE `deleted` = 0 AND `weapons_account` = '.$this->id;
    
    if (!$result = $this->db->query($query)) {
      if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
        $this->set_last_error('<b>Full_data error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
      } else {
        $this->set_last_error('<center><b>Ошибка объекта "Квитанция"!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
      }
      return false;
    }
    
    while ($row = $result->fetch_assoc()) {
      $this->weapons[$row['weapon_type']][] = new Weapon($row['id']);
    }
    $result->close();
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
    require(KERNEL.'spr_ovd.php');
    if (!is_numeric($ovd) or !isset($spr_ovd[$ovd])) {
      $this->set_last_error('<b>Квитанция</b>: значение ОВД должно быть числовым.');
      return false;
    } else {
      $this->ovd = $ovd;
      $this->ovd_string = $spr_ovd[$ovd];
      return true;
    }
  }
  
  public function get_ovd() {
    return $this->ovd;
  }
  
  public function get_ovd_string() {
    return $this->ovd_string;
  }
  
  public function set_reg_number($n = null) {
    if (empty($n)) {
      $this->reg_number = null;
      return true;
    }
    if (!is_numeric($n)) {
      $this->set_last_error('<b>Рег.номер</b>: Значение должно быть числовым.');
      return false;
    }
    $this->reg_number = (integer)$n;
    return true;
  }
  
  public function get_reg_number() {
    return $this->reg_number;
  }
  
  public function set_reg_date($date = null) {
    try {
      if (empty($date)) {
        $this->reg_date = null;
        return true;
      }
      if (!preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $date))
        throw new Exception('<b>Дата квитанции</b>: Дату необходимо вводить в формате "'.date('d.m.Y').'"');
        
      if (strtotime($date) > strtotime('now'))
        throw new Exception('<b>Дата квитанции</b>: Вводимая дата не может быть больше текущей');
      
      $this->reg_date = $date;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
    return true;
  }
  
  public function get_reg_date() {
    return $this->reg_date;
  }
  
  public function set_base_receiving($br = 0) {
    try {
      if (empty($br)) {
        $this->base_receiving = null;
        $this->base_receiving_string = null;
        return true;
      }
      
      if (!is_numeric($br))
        throw new Exception('<b>Основание приема</b>: значение должно быть числовым.');
      
      $this->db_connect();
      $query = 'SELECT `id`, `name` FROM `spr_base_receiving_weapons` WHERE `id` = '.$br;
      
      if (!$result = $this->db->query($query))
        throw new Exception('<b>Set_base_receiving error</b>: '.$this->db->error.' .Query string: '.$query);
      
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Основание приема:</b>: Значение "'.$br.'" вне допустимого диапазона.');
      
      $this->base_receiving = (integer)$row['id'];
      $this->base_receiving_string = $row['name'];
      
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
    return true;
  }
  
  public function get_base_receiving() {
    return $this->base_receiving;
  }
  
  public function get_base_receiving_string() {
    return $this->base_receiving_string;
  }
  
  public function set_purpose_placing($pp = 0) {
    try {
      if (empty($pp)) {
        $this->purpose_placing = null;
        $this->purpose_placing_string = null;
        return true;
      }
      
      if (!is_numeric($pp))
        throw new Exception('<b>Основание приема: значение должно быть числовым.</b>');
      
      $this->db_connect();
      $query = 'SELECT `id`, `name` FROM `spr_purpose_placing` WHERE `id` = '.$pp;
      
      if (!$result = $this->db->query($query))
        throw new Exception('<b>Set_purpose_placing error</b>: '.$this->db->error().' .Query string: '.$query);
      
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Цель помещения:</b>: Значение "'.$pp.'" вне допустимого диапазона.');
      
      $this->purpose_placing = (integer)$row['id'];
      $this->purpose_placing_string = $row['name'];
      
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
    return true;
  }
  
  public function get_purpose_placing() {
    return $this->purpose_placing;
  }
  
  public function get_purpose_placing_string() {
    return $this->purpose_placing_string;
  }
  
  public function set_incoming_number($n = null) {
    $this->incoming_number = (empty($n)) ? null : (string)$n;
  }
  
  public function get_incoming_number() {
    return $this->incoming_number;
  }
  
  public function set_incoming_date($date = null) {
    try {
      if (empty($date)) {
        $this->incoming_date = null;
        return true;
      }
      if (!preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $date))
        throw new Exception('<b>Вх.ДИР</b>: Дату необходимо вводить в формате "'.date('d.m.Y').'"');
        
      if (strtotime($date) > strtotime('now'))
        throw new Exception('<b>Вх.ДИР</b>: Вводимая дата не может быть больше текущей');
      
      $this->incoming_date = $date;
        return true;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
  }
  
  public function get_incoming_date() {
    return $this->incoming_date;
  }
  
  public function set_kusp($kusp) {
    if (is_numeric($kusp)) {
      $this->kusp = new Kusp($kusp);
    } elseif (is_object($kusp)) {
      $this->kusp = $kusp;
    } else {
      return false;
    }
  }
  
  public function unset_kusp() {
    $this->kusp = null;
  }
  
  public function get_kusp() {
    return $this->kusp;
  }
  
  public function set_crime_case($cc) {
    if (is_numeric($cc)) {
      $this->crime_case = new CrimeCase($cc);
    } elseif (is_object($cc)) {
      $this->crime_case = $cc;
    } else {
      return false;
    }
  }
  
  public function unset_crime_case() {
    $this->crime_case = null;
  }
  
  public function get_crime_case() {
    return $this->crime_case;
  }
  
  public function append_weapon($wpn) {
    if (is_object($wpn)) {
      if ($wpn->get_type() == 1) {
        $this->weapons[1][0] = $wpn;
        return 0;
      } else {
        $this->weapons[$wpn->get_type()][] = $wpn;
      }
    } elseif (is_numeric($wpn) and $wpn != 0) {
      $wpn = new Weapon($wpn);
      $this->weapons[$wpn->get_type()][] = $wpn;
    }
    $_k = array_keys($this->weapons[$wpn->get_type()]);
    return $_k[count($_k) - 1];
  }
  
  public function get_weapon($group = null, $n = null) {
    if (empty($this->weapons))
      return array();
    
    if (empty($group)) {
      return $this->weapons;
    }
    
    if (!isset($this->weapons[$group]))
      return array();
    
    if (!empty($group) and is_null($n))
      return $this->weapons[$group];
    
    if (isset($this->weapons[$group][$n]))
      return $this->weapons[$group][$n];
    
    return array();
  }
  
  public function unset_weapon($group, $n = 0) {
    if (!isset($this->weapons[$group][$n]))
      return false;
    unset($this->weapons[$group][$n]);
    if (empty($this->weapons[$group]))
      unset($this->weapons[$group]);
    return true;
  }
  
  public function get_count($group = 0) {
    if (empty($group)) {
      $i = 0;
      foreach ($this->weapons as $n => $g) {
        $i += count($g);
      }
      return $i;
    } else {
      if (isset($this->weapons[$group])) {
        return count($this->weapons[$group]);
      } else {
        return 0;
      }
    }
  }
  
  public function get_weapon_history($group = null, $n = null) {
    $arr = $this->get_weapon($group, $n);
    
    if (empty($arr))
      return array();
    
    if (is_array($arr)) {
      foreach ($arr as $t => $w) {
        if (is_array($w)) {
          foreach ($w as $k => $v) {
            $id[] = $v->get_id();
          }
        }
        if (is_object($w))
          $id[] = $w->get_id();
      }
    }
    
    if (is_object($arr))
      $id[] = $arr->get_id();
    
    $id = (array)$id;
    if (empty($id))
      return array();
    
    $res = $arr = null;
    
    try {
      $this->db_connect();
      $query = '
        SELECT
          wd.`id`,
          DATE_FORMAT(wd.`date`, "%d.%m.%Y") as `date`,
          wd.`number`,
          wd.`decision`,
          (SELECT `name` FROM `spr_decision_in_arms` WHERE `id` = wd.`decision`) as `name`,
          GROUP_CONCAT(
            CONCAT(wd.`weapon`, "-", wd.`quantity`)
            SEPARATOR "|"
          ) as `weapons`,
          IF(wd.`case` IS NOT NULL, CONCAT("Дело №", wd.`case`, ", стр.", wd.`page`, " (ввод: ", wd.`update_date`, " ", wd.`update_time`, ")"), NULL) as `case`
        FROM
          `l_weapons_decision` as wd
        WHERE
          wd.`weapon` IN ('.implode(',', $id).')
        GROUP BY
          wd.`decision`, wd.`date`, wd.`number`
        ORDER BY
          wd.`date`, wd.`number`, wd.`create_time`
      ';
      if (!$result = $this->db->query($query)) {
        if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
          throw new Exception('<b>Get_weapon_history error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
        } else {
          throw new Exception('<center><b>Ошибка выбора истории!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
        }
      }
      while ($row = $result->fetch_assoc()) {
        $arr = explode('|', $row['weapons']);
        $row['weapons'] = null;
        foreach ($arr as $k => $v) {
          $w = explode('-', $v);
          $row['weapons'][$k]['weapon'] = $w[0];
          $row['weapons'][$k]['quantity'] = $w[1];
        }
        $res[] = $row;
      }
      $result->close();
      
      return (array)$res;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      $res = false;
    }
    return $res;
  }
  
  public function set_weapon_quantity($group, $id, $q = 0) {
    $res = true;
    try {
      if (is_null($id)) {
        if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
          throw new Exception('<b>Set_weapon_quantity ошибка</b>: не указан ID записи оружия.');
        } else {
          throw new Exception('<center><b>Ошибка изменения кол-ва единиц оружия №1!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
        }
      }
      
      if (!empty($q) and !is_numeric($q)) {
        if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
          throw new Exception('<b>Set_weapon_quantity ошибка</b>: количественный показатель должен быть в числовом значении.');
        } else {
          throw new Exception('<center><b>Ошибка изменения кол-ва единиц оружия №2!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
        }
      }
      
      if (!isset($this->weapons[$group])) {
        if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
          throw new Exception('<b>Set_weapon_quantity ошибка</b>: не найден элемент массива.');
        } else {
          throw new Exception('<center><b>Ошибка изменения кол-ва единиц оружия №3</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
        }
      }
      
      foreach ($this->weapons[$group] as $n => $wpn) {
        if ($wpn->get_id() != $id)
          continue;
        $wpn->set_quantity_total($q);
      }
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      $res = false;;
    }
    return $res;
  }
  
  public function save($con = null) {
    try {
      if (empty($this->ovd))
        $emptyF[] = '"ОВД"';
      if (empty($this->reg_number))
        $emptyF[] = '"№ квитанции"';
      if (empty($this->reg_date))
        $emptyF[] = '"Дата квитанции"';
      /*
      if (strtotime($this->reg_date) >= strtotime('01.10.2016') and empty($this->kusp))
        $emptyF[] = '"КУСП"';
      if (strtotime($this->reg_date) < strtotime('01.10.2016') and (empty($this->incoming_number) or empty($this->incoming_date)))
        $emptyF[] = '"Регистрационный данные О(Г)ДИР"';
      */
      if (empty($this->kusp) and (empty($this->incoming_number) or empty($this->incoming_date)) and empty($this->crime_case))
        $emptyF[] = '"Регистрационный данные (КУСП, у/д или О(Г)ДИР)"';
      if (empty($this->base_receiving))
        $emptyF[] = '"Основание принятия"';
      if (empty($this->purpose_placing))
        $emptyF[] = '"Цель помещения"';
      
      if (!empty($emptyF)) 
        throw new Exception('<b>Квитанция</b>: не заполнены поля '.implode(', ', $emptyF).'.');
      
      
      if (is_object($this->kusp) and !$this->kusp->save())
        throw new Exception('<b>Квитанция</b>: '.$this->kusp->get_last_error().'.');
      if (is_object($this->crime_case) and !$this->crime_case->save())
        throw new Exception('<b>Квитанция</b>: '.$this->kusp->get_last_error().'.');
     
      if (empty($con)) {
        $this->db_connect();
      } else {
        $this->db = $con;
      }
      $isNew = true;
      $old_wpns = $new_wpns = array();
      $this->db->autocommit(false);
      
      if (empty($this->id)) {
        $query = '
          INSERT INTO
            `l_weapons_account` (`ovd`, `reg_number`, `reg_date`, `base_receiving`, `purpose_placing`, 
                                 `kusp`, `crime_case`, `incoming_number`, `incoming_date`,
                                 `create_date`, `create_time`, `active_id`, `update_date`, `update_time`, `update_active_id`)
          VALUES(
                 '.$this->ovd.', '.$this->reg_number.', "'.date('Y-m-d', strtotime($this->reg_date)).'", '.$this->base_receiving.', '.$this->purpose_placing.',
                 '.((is_object($this->kusp)) ? $this->kusp->get_id() : 'NULL').',
                 '.((is_object($this->crime_case)) ? $this->crime_case->get_id() : 'NULL').',
                 '.((!empty($this->incoming_number)) ? '"'.mysql_real_escape_string($this->incoming_number).'"' : 'NULL').',
                 '.((!empty($this->incoming_date)) ? '"'.date('Y-m-d', strtotime($this->incoming_date)).'"' : 'NULL').',
                 CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
        ';
        if (!$this->db->query($query)) {
          if ($this->db->errno == 1062) {
            throw new Exception('В БД уже существует запись с таким ОВД, рег.номером и датой.');
          } else {
            if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
              throw new Exception('<b>WeaponAccount save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
            } else {
              throw new Exception('<center><b>Ошибка сохранения объекта "Квитанция" №1!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
            }
          }
        }

        $this->id = $this->db->insert_id;
      } else {
        $isNew = false;
      }
      if (empty($this->id)) {
        $isNew = false;
        $query = '
          SELECT
            wa.`id`
          FROM
            `l_weapons_account` as wa
          WHERE
            wa.`ovd` = '.$this->ovd.'
            AND wa.`reg_number` = '.$this->reg_number.'
            AND wa.`reg_date` = "'.date('Y-m-d', strtotime($this->reg_date)).'"
        ';
        
        if (!$result = $this->db->query($query)) {
          if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
            throw new Exception('<b>WeaponAccount save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
          } else {
            throw new Exception('<center><b>Ошибка сохранения объекта "Квитанция" №2!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
          }
        }
        
        $row = $result->fetch_assoc();
        $result->close();

        if (empty($row))
          throw new Exception('<b>WeaponAccount save error:</b>: неизвестная ошибка, обратитесь к администратору.');
        
        $this->id = $row['id'];
      }
      if ($isNew === false) {
        $query = '
          SELECT
            w.`id`
          FROM
            `l_weapons` as w
          WHERE
            w.`weapons_account` = '.$this->id.' AND
            w.`deleted` = 0
        ';
        if (!$result = $this->db->query($query)) {
          if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
            throw new Exception('<b>WeaponAccount save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
          } else {
            throw new Exception('<center><b>Ошибка сохранения объекта "Оружие" №3!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
          }
          return false;
        }
        while ($row = $result->fetch_assoc()) {
          $old_wpns[] = $row['id'];
        }        
        
        $query = '
          UPDATE
            `l_weapons_account`
          SET
            `ovd` = '.$this->ovd.',
            `reg_number` = '.$this->reg_number.',
            `reg_date` = "'.date('Y-m-d', strtotime($this->reg_date)).'",
            `base_receiving` = '.$this->base_receiving.',
            `purpose_placing` = '.$this->purpose_placing.',
            `kusp` = '.((is_object($this->kusp)) ? $this->kusp->get_id() : 'NULL').',
            `crime_case` = '.((is_object($this->crime_case)) ? $this->crime_case->get_id() : 'NULL').',
            `incoming_number` = '.((!empty($this->incoming_number)) ? '"'.mysql_real_escape_string($this->incoming_number).'"' : 'NULL').',
            `incoming_date` = '.((!empty($this->incoming_date)) ? '"'.date('Y-m-d', strtotime($this->incoming_date)).'"' : 'NULL').',
            `update_date` = CURRENT_DATE,
            `update_time` = CURRENT_TIME,
            `update_active_id` = '.$this->active_id.'
          WHERE
            `id` = '.$this->id.'
        ';
        if (!$this->db->query($query)) {
          if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
            throw new Exception('<b>WeaponAccount save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
          } else {
            throw new Exception('<center><b>Ошибка сохранения объекта "Квитанция" ("Оружие") №4!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
          }
        }
      }
      
      foreach ($this->get_weapon() as $type => $arr) {
        foreach ($arr as $n => $wpn) {
          if (is_null($wpn->get_id())) {
            $wpn->set_account($this->get_id());
            
            if (!$wpn->save($this->db)) {
              if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
                throw new Exception('<b>WeaponAccount save error</b>: '.$wpn->get_last_error());
              } else {
                throw new Exception('<center><b>Ошибка сохранения объекта "Квитанция" ("Оружие") №5 - '.$wpn->get_last_error().'!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
              }
            }
          }
          $new_wpns[] = $wpn->get_id();
        }
      }
      $on_del = array_diff($old_wpns, $new_wpns);
      if (!empty($on_del)) {
        $query = '
          UPDATE
            `l_weapons`
          SET
            `deleted` = 1,
            `update_date` = CURRENT_DATE,
            `update_time` = CURRENT_TIME,
            `update_active_id` = '.$this->active_id.'
          WHERE
          `id` IN ('.implode(', ', $on_del).')
        ';
        if (!$this->db->query($query)) {
          if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
            throw new Exception('<b>WeaponAccount save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
          } else {
            throw new Exception('<center><b>Ошибка сохранения объекта "Квитанция" ("Оружие") №6!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
          }
        }
      }
      return true;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
  }
}
?>
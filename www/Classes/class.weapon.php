<?php
class Weapon extends Site_DB {
  private $id;
  private $account;
  private $group;
  private $group_string;
  private $type;
  private $type_string;
  private $sort;
  private $sort_string;
  private $model;
  private $model_string;
  private $unknown_model;
  private $caliber;
  private $add_attributes;
  private $add_attributes_string;
  private $manufacture_year;
  private $series;
  private $number;
  private $barrel_series;
  private $barrel_number;
  private $fore_end_serial;
  private $fore_end_number;
  private $shoe_serial;
  private $shoe_number;
  private $quantity_incoming;
  private $quantity_total;
  private $storage;
  private $note;
  private $photos_path;
  private $last_decision;
  private $history;
  private $error;
  
  public function __construct($f = 0) {
    $this->on_construct($f);
  }
  
  private function on_construct($i) {
    $row = null;
    if (is_numeric($i) and $i > 0) {
      $this->db_connect();
      $query = '
        SELECT
          w.`id`, w.`weapons_account` as `account`,
          w.`weapon_group` as `group`, wg.`name` as `group_string`,
          IF(w.`weapon_type` = 2, 2, NULL) as `type`,
          IF(w.`weapon_type` = 2, wt.`name`, NULL) as `type_string`,
          w.`weapon_type` as `type`, wt.`name` as `type_string`,
          w.`weapon_sort` as `sort`,
          IF(ws.`name` = wsg.`name` OR ws.`group` IS NULL, ws.`name`, CONCAT(wsg.`name`, " - ", ws.`name`)) as `sort_string`,
          w.`model`,
          CONCAT(
            IF(wm.`sort` IS NULL, "", CONCAT(LOWER(wm.`sort`), ": " )),
            wm.`model`,
            IF(wm.`barrels` IS NULL, "", CONCAT("; стволов: ", wm.`barrels`)),
            CASE
              WHEN wm.`barrels` IS NOT NULL THEN
                CONCAT( " (", 
                  CONCAT(
                    IF(wm.`barrel_1_caliber` IS NOT NULL, wm.`barrel_1_caliber`, ""),
                    IF(wm.`barrel_1_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_1_cart_length`), "")
                  ),
                  CONCAT(
                    IF(wm.`barrel_2_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_2_caliber`), ""),
                    IF(wm.`barrel_2_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_2_cart_length`), "")
                  ),
                  CONCAT(
                    IF(wm.`barrel_3_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_3_caliber`), ""),
                    IF(wm.`barrel_3_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_3_cart_length`), "")
                  ),
                  CONCAT(
                    IF(wm.`barrel_4_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_4_caliber`), ""),
                    IF(wm.`barrel_4_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_4_cart_length`), "")
                  ),
                ")")
              ELSE
                ""
            END
          ) as `model_string`,
          w.`caliber`,
          IF(w.`unknown_model` = 0, "false", "true") as `unknown_model`,
          w.`add_attributes`, m.`marking` as `add_attributes_string`,
          w.`manufacture_year`,
          w.`series`, w.`number`,
          w.`barrel_series`, w.`barrel_number`,
          w.`fore-end_serial` as `fore_end_serial`, w.`fore-end_number` as `fore_end_number`,
          w.`shoe_serial`, w.`shoe_number`,
          w.`quantity_incoming`, w.`quantity_total`,
          w.`storage`, w.`note`, w.`last_decision`
        FROM
          `l_weapons` as w
        LEFT JOIN
          `spr_weapon_groups` as wg ON
            wg.`id` = w.`weapon_group`
        LEFT JOIN
          `spr_weapon_types` as wt ON
            wt.`id` = w.`weapon_type`
        LEFT JOIN
          `spr_weapon_sorts` as ws ON
            ws.`id` = w.`weapon_sort`
          LEFT JOIN
            `spr_weapon_sorts` as wsg ON
              wsg.`id` = ws.`group`
        LEFT JOIN
          `spr_weapon_models` as wm ON
            wm.`id` = w.`model`
        LEFT JOIN
          `spr_marking` as m ON
            m.`id` = w.`add_attributes`
        WHERE
          w.`deleted` = 0 AND
          w.`id` = '.$i;
          
      if (!$result = $this->db->query($query)) {
        $this->set_last_error('<b>Weapon constructor error</b>: '.$this->db->error.' .Query string: '.$query);
        return false;
      }
      $row = $result->fetch_assoc();
      $result->close();
    }
    
    if (!empty($row) > 0) {
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
    } else {
      foreach (get_class_vars('Weapon') as $ch => $v) {
        $this->$ch = null;
      }
      $this->unknown_model = true;
    }
  }

  public function set_connect($c) {
    $this->db = $c;
  }
  
  public function full_data() {
    if (!is_null($this->account) and is_numeric($this->account)) {
      $this->account = new WeaponAccount($this->account);
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
  
  public function set_account($a) {
    if (is_numeric($a)) {
      $this->account = (integer)$a;
    } else {
      $this->account = $a;
    }
  }
  
  public function set_group($wg = null) {
    $res = true;
    try {
      if (empty($wg)) {
        $this->group = null;
        $this->group_string = null;
        return true;
      }
      
      if (!is_numeric($wg))
        throw new Exception('<b>Группа</b>: значение должно быть числовым.');
        
      $this->db_connect();
      $query = 'SELECT `id`, `name` FROM `spr_weapon_groups` WHERE `id` = '.$wg;
      
      if (!$result = $this->db->query($query))
        throw new Exception('<b>Set_weapon_group error (1)</b>: '.$this->db->error.' .Query string: '.$query);
        
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Группа:</b>: значение "'.$wg.'" вне допустимого диапазона.');
      
      $this->group = (integer)$row['id'];
      $this->group_string = (string)$row['name'];
      $this->set_type(2);
      
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      $res = false;
    }
    return $res;
  }
  
  public function get_group() {
    return $this->group;
  }
  
  public function get_group_string() {
    return $this->group_string;
  }
  
  public function unset_group() {
    $this->group = null;
    $this->group_string = null;
  }
  
  public function set_type($wt = null) {
    try {
      if (empty($wt)) {
        $this->type = null;
        $this->type_string = null;
        return true;
      }
      if (!is_numeric($wt))
        throw new Exception('<b>Тип</b>: значение должно быть числовым.');
        
      $this->db_connect();
      $query = 'SELECT `id`, `name` FROM `spr_weapon_types` WHERE `id` = '.$wt;
      
      if (!$result = $this->db->query($query))
        throw new Exception('<b>Set_weapon_type error</b>: '.$this->db->error.' .Query string: '.$query);
      
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Тип</b>: значение "'.$wt.'" вне допустимого диапазона.');
      
      $this->type = (integer)$row['id'];
      $this->type_string = (string)$row['name'];
      if ($this->type != 2)
        $this->unset_group();
      
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
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
  
  public function unset_type() {
    $this->type = null;
    $this->type_string = null;
  }
  
  public function set_sort($s = null) {
    try {
      if (empty($s)) {
        $this->sort = null;
        $this->sort_string = null;
        return true;
      }
      if (!is_numeric($s))
        throw new Exception('<b>Вид оружия</b>: значение должно быть числовым.');
      
      $this->db_connect();
      $query = '
        SELECT 
          ws.`id`,
          IF(ws.`name` = wsg.`name`, ws.`name`, CONCAT(IF(wsg.`name` IS NOT NULL, CONCAT(wsg.`name`, " - "), ""), ws.`name`)) as `name`
        FROM 
          `spr_weapon_sorts` as ws 
        LEFT JOIN 
          `spr_weapon_sorts` as wsg ON 
            ws.`group` = wsg.`id`
        WHERE 
          ws.`id` = '.$s;
          
      if (!$result = $this->db->query($query))
        throw new Exception('<b>Set_weapon_sort error</b>: '.$this->db->error.' .Query string: '.$query);
      
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Вид оружия</b>: значение "'.$s.'" вне допустимого диапазона.');
      
      $this->sort = (integer)$row['id'];
      $this->sort_string = (string)$row['name'];
      
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
    return true;
  }
  
  public function get_sort() {
    return $this->sort;
  }
  
  public function get_sort_string() {
    return $this->sort_string;
  }
  
  public function set_model($m = null) {
    try {
      if (empty($m)) {
        $this->set_unknown_model();
        return true;
      }
      if (!is_numeric($m))
        throw new Exception('<b>Модель</b>: значение должно быть числовым.');
      
      $this->db_connect();
      $query = '
        SELECT
          wm.`id`,
          CONCAT(
            IF(wm.`sort` IS NULL, "", CONCAT(LOWER(wm.`sort`), ": " )),
            wm.`model`,
            IF(wm.`barrels` IS NULL, "", CONCAT("; стволов: ", wm.`barrels`)),
            CASE
              WHEN wm.`barrels` IS NOT NULL THEN
                CONCAT( " (", 
                  CONCAT(
                    IF(wm.`barrel_1_caliber` IS NOT NULL, wm.`barrel_1_caliber`, ""),
                    IF(wm.`barrel_1_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_1_cart_length`), "")
                  ),
                  CONCAT(
                    IF(wm.`barrel_2_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_2_caliber`), ""),
                    IF(wm.`barrel_2_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_2_cart_length`), "")
                  ),
                  CONCAT(
                    IF(wm.`barrel_3_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_3_caliber`), ""),
                    IF(wm.`barrel_3_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_3_cart_length`), "")
                  ),
                  CONCAT(
                    IF(wm.`barrel_4_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_4_caliber`), ""),
                    IF(wm.`barrel_4_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_4_cart_length`), "")
                  ),
                ")")
              ELSE
                ""
            END
          ) as `name`
        FROM
          `spr_weapon_models` as wm
        WHERE
          wm.`id` = '.$m;
          
      if (!$result = $this->db->query($query))
        throw new Exception('<b>Set_weapon_model error</b>: '.$this->db->error.' .Query string: '.$query);
        
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Тип</b>: значение "'.$m.'" вне допустимого диапазона.');
      
      $this->model = (integer)$row['id'];
      $this->model_string = (string)$row['name'];
      $this->unknown_model = false;
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
    return true;
  }
  
  public function unset_model() {
    $this->model = null;
    $this->model_string = null;
  }
  
  public function get_model() {
    return $this->model;
  }
  
  public function get_model_string() {
    return $this->model_string;
  }
  
  public function set_unknown_model() {
    $this->unknown_model = true;
    $this->unset_model();
  }
  
  public function is_model() {
    return ($this->unknown_model) ? false : true;
  }
  
  public function set_caliber($c = null) {
    if (empty($c)) {
      $this->caliber = null;
      return true;
    }
    if (!is_numeric($c)) {
      $this->set_last_error('<b>Калибр</b>: значение должно быть числовым.');
      return false;
    }
    $c = str_replace(',', '.', $c);
    $this->caliber = (double)$c;
    return true;
  }
  
  public function get_caliber() {
    return $this->caliber;
  }
  
  public function set_add_attributes($a = null) {
    try {
      if (empty($a)) {
        $this->add_attributes = null;
        $this->add_attributes_string = null;
        return true;
      }
      
      if (!is_numeric($a))
        throw new Exception('<b>Доп.характеристики</b>: значение должно быть числовым.');
        
      $this->db_connect();
      $query = 'SELECT `id`, `marking` FROM `spr_marking` WHERE `id` = '.$a;
      
      if (!$result = $this->db->query($query))
        throw new Exception('<b>Set_add_attributes error</b>: '.$this->db->error.' .Query string: '.$query);
       
      $row = $result->fetch_assoc();
      $result->close();
      
      if (empty($row))
        throw new Exception('<b>Доп.характеристики</b>: значение "'.$a.'" вне допустимого диапазона.');
      
      $this->add_attributes = (integer)$row['id'];
      $this->add_attributes_string = (string)$row['marking'];
      
    } catch (Exception $exc) {
      $this->set_last_error($exc->getMessage());
      return false;
    }
    return true;
  }
  
  public function get_add_attributes() {
    return $this->add_attributes;
  }
  
  public function get_add_attributes_string() {
    return $this->add_attributes_string;
  }
  
  public function set_manufacture_year($y = null) {
    if (empty($y)) {
      $this->manufacture_year = null;
      return true;
    }
    if (!is_numeric($y)) {
      $this->set_last_error('<b>Год выпуска</b>: Значение должно быть числовым.');
      return false;
    }
    if ($y > date('Y')) {
      $this->set_last_error('<b>Год выпуска</b>: Значение не может быть больше текущего года.');
      return false;
    }
    if ($y < 1900) {
      $this->set_last_error('<b>Год выпуска</b>: Значение '.$y.' слишком мало.');
      return false;
    }
    $this->manufacture_year = (integer)$y;
    return true;
  }
  
  public function get_manufacture_year() {
    return $this->manufacture_year;
  }
  
  public function set_series($s = null) {
    $this->series = (empty($s)) ? null : $s;
  }
  
  public function get_series() {
    return $this->series;
  }
  
  public function set_number($n = null) {
    $this->number = (empty($n)) ? null : $n;
  }
  
  public function get_number() {
    return $this->number;
  }
  
  public function set_barrel_series($n = null) {
    $this->barrel_series = (empty($n)) ? null : $n;
  }
  
  public function get_barrel_series() {
    return $this->barrel_series;
  }
  
  public function set_barrel_number($n = null) {
    $this->barrel_number = (empty($n)) ? null : $n;
  }
  
  public function get_barrel_number() {
    return $this->barrel_number;
  }
  
  public function set_fore_end_serial($n = null) {
    $this->fore_end_serial = (empty($n)) ? null : $n;
  }
  
  public function get_fore_end_serial() {
    return $this->fore_end_serial;
  }
  
  public function set_fore_end_number($n = null) {
    $this->fore_end_number = (empty($n)) ? null : $n;
  }
  
  public function get_fore_end_number() {
    return $this->fore_end_number;
  }
  
  public function set_shoe_serial($n = null) {
    $this->shoe_serial = (empty($n)) ? null : $n;
  }
  
  public function get_shoe_serial() {
    return $this->shoe_serial;
  }
  
  public function set_shoe_number($n = null) {
    $this->shoe_number = (empty($n)) ? null : $n;
  }
  
  public function get_shoe_number() {
    return $this->shoe_number;
  }
  
  public function set_quantity_incoming($q = 1) {
    if (empty($q)) {
      $this->quantity_incoming = null;
      return true;
    }
    
    $q = preg_replace('/[,\.]+/ui', '.', $q);
    
    if (!is_numeric($q)) {
      $this->set_last_error('<b>Количество</b>: значение должно быть числовым.');
      return false;
    }
    if ($q < 1) {
      $this->set_last_error('<b>Количество</b>: значение должно быть положительным числом.');
      return false;
    }
    $this->quantity_incoming = $q;
    return true;
  }
  
  public function get_quantity_incoming() {
    return ((!is_null($this->quantity_incoming)) ? get_var_in_data_type($this->quantity_incoming) : null);
  }
  
  public function set_quantity_total($q = 1) {
    $q = preg_replace('/[,\.]+/ui', '.', $q);
  
    if (!is_numeric($q)) {
      $this->set_last_error('<b>Остаток</b>: значение должно быть числовым.');
      return false;
    }
    if ($q < 0) {
      $this->set_last_error('<b>Количество</b>: значение должно быть положительным числом ('.$q.').');
      return false;
    }
    $this->quantity_total = $q;
    return true;
  }

  public function get_quantity_total() {
    return ((!is_null($this->quantity_total)) ? get_var_in_data_type($this->quantity_total) : null);;
  }

  public function set_storage($s = null) {
    $this->storage = (empty($s)) ? null : $s;
  }

  public function get_storage() {
    return $this->storage;
  }
  
  public function set_note($n = null) {
    $this->note = (empty($n)) ? null : $n;
  }
  
  public function get_note() {
    return $this->note;
  }
  
  public function set_last_decision($d = null) {
    if (empty($d)) {
      $this->last_decision = null;
      return true;
    }
    if (!is_numeric($d)) {
      $this->set_last_error('<b>Последнее решение</b>: значение должно быть числовым.');
      return false;
    }
    $this->last_decision = $d;
  }
  
  public function get_last_decision() {
    return $this->last_decision;
  }
  
  public function save($con = null) {
    try {
      if (empty($this->account))
        $emptyF[] = '"Квитанция"';
      if (empty($this->type))
        $emptyF[] = '"Тип оружия"';
      if (empty($this->sort))
        $emptyF[] = '"Тип оружия"';
      if (empty($this->quantity_incoming))
        $emptyF[] = '"Количество"';
      if (empty($this->storage))
        $emptyF[] = '"Хранилище"';
      
      if (!empty($emptyF))
        throw new Exception('<b>Оружие</b>: не заполнены поля '.implode(', ', $emptyF).'.');
      
      if (empty($con)) {
        $this->db_connect();
      } else {
        $this->db = $con;
      }
      
      $isNew = true;
      
      $this->db->autocommit(false);
      
      
        
      if (empty($this->id)) {
        $query = '
          INSERT INTO
            `l_weapons`(`weapons_account`, `weapon_group`, `weapon_type`, `weapon_sort`, `model`, `caliber`, `unknown_model`, `add_attributes`,
                        `manufacture_year`, `series`, `number`, `barrel_series`, `barrel_number`, `fore-end_serial`, `fore-end_number`,
                        `shoe_serial`, `shoe_number`, `quantity_incoming`, `quantity_total`, `storage`, `note`, `last_decision`,
                        `create_date`, `create_time`, `active_id`, `update_date`, `update_time`, `update_active_id`)
          VALUES (
                  '.$this->account.',
                  '.((is_null($this->group)) ? 'NULL' : $this->group).',
                  '.$this->type.', '.$this->sort.', 
                  '.((!empty($this->model)) ? $this->model : 'NULL').', 
                  '.((!empty($this->caliber)) ? $this->caliber : 'NULL').', 
                  '.(integer)$this->unknown_model.', 
                  '.((!empty($this->add_attributes)) ? $this->add_attributes : 'NULL').', 
                  '.((!empty($this->manufacture_year)) ? $this->manufacture_year : 'NULL').', 
                  '.((!empty($this->series)) ? '"'.$this->db->real_escape_string($this->series).'"' : 'NULL').', 
                  '.((!empty($this->number)) ? '"'.$this->db->real_escape_string($this->number).'"' : 'NULL').', 
                  '.((!empty($this->barrel_series)) ? '"'.$this->db->real_escape_string($this->barrel_series).'"' : 'NULL').', 
                  '.((!empty($this->barrel_number)) ? '"'.$this->db->real_escape_string($this->barrel_number).'"' : 'NULL').', 
                  '.((!empty($this->fore_end_serial)) ? '"'.$this->db->real_escape_string($this->fore_end_serial).'"' : 'NULL').', 
                  '.((!empty($this->fore_end_number)) ? '"'.$this->db->real_escape_string($this->fore_end_number).'"' : 'NULL').', 
                  '.((!empty($this->shoe_serial)) ? '"'.$this->db->real_escape_string($this->shoe_serial).'"' : 'NULL').', 
                  '.((!empty($this->shoe_number)) ? '"'.$this->db->real_escape_string($this->shoe_number).'"' : 'NULL').', 
                  '.((!empty($this->quantity_incoming)) ? $this->quantity_incoming : 1).', 
                  '.((!is_null($this->quantity_total)) ? $this->quantity_total : ((!empty($this->quantity_incoming)) ? $this->quantity_incoming : 1)).', 
                  "'.$this->db->real_escape_string($this->storage).'", 
                  '.((!empty($this->note)) ? '"'.$this->db->real_escape_string($this->note).'"' : 'NULL').',
                  '.((!empty($this->last_decision)) ? $this->last_decision : 'NULL').',
                  CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
        ';
        
        if (!$this->db->query($query)) {
          if ($this->db->errno == 1062) {
            throw new Exception('В БД уже существует запись о таком оружии.');
          } else {
            if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
              throw new Exception('<b>Weapon save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
            } else {
              throw new Exception('<center><b>Ошибка сохранения объекта "Оружие"!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
            }
          }
        }
        
        $this->id = $this->db->insert_id;
        
        if (empty($this->id)) {
          $isNew = false;
          $query = '
            SELECT `id` FROM `l_weapons`
            WHERE
              `weapon_type` = '.$this->type.' AND
              `series` = "'.$this->db->real_escape_string($this->series).'" AND
              `number` = "'.$this->db->real_escape_string($this->number).'" AND
              `manufacture_year` = '.$this->manufacture_year.' AND
              `weapons_account` = '.$this->account.'
          ';
          if (!$result = $this->db->query($query)) {
            if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
              throw new Exception('<b>WeaponAccount save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
            } else {
              throw new Exception('<center><b>Ошибка сохранения объекта "Оружие"!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
            }
            return false;
          }
          $row = $result->fetch_assoc();
          $result->close();
          $this->id = $row['id'];
        }
      } else {
        $isNew = false;
      }
      
      if ($isNew === false) {
        $query = '
          UPDATE
            `l_weapons`
          SET
            `weapons_account` = '.$this->account.',
            `weapon_group` = '.((is_null($this->group)) ? 'NULL' : $this->group).',
            `weapon_type` = '.$this->type.',
            `weapon_sort` = '.$this->sort.',
            `model` = '.((!empty($this->model)) ? $this->model : 'NULL').',
            `caliber` = '.((!empty($this->caliber)) ? $this->caliber : 'NULL').',
            `unknown_model` = '.(integer)$this->unknown_model.',
            `add_attributes` = '.((!empty($this->add_attributes)) ? $this->add_attributes : 'NULL').',
            `manufacture_year` = '.((!empty($this->manufacture_year)) ? $this->manufacture_year : 'NULL').',
            `series` = '.((!empty($this->series)) ? '"'.$this->db->real_escape_string($this->series).'"' : 'NULL').',
            `number` = '.((!empty($this->number)) ? '"'.$this->db->real_escape_string($this->number).'"' : 'NULL').',
            `barrel_series` = '.((!empty($this->barrel_series)) ? '"'.$this->db->real_escape_string($this->barrel_series).'"' : 'NULL').',
            `barrel_number` = '.((!empty($this->barrel_number)) ? '"'.$this->db->real_escape_string($this->barrel_number).'"' : 'NULL').',
            `fore-end_serial` = '.((!empty($this->fore_end_serial)) ? '"'.$this->db->real_escape_string($this->fore_end_serial).'"' : 'NULL').',
            `fore-end_number` = '.((!empty($this->fore_end_number)) ? '"'.$this->db->real_escape_string($this->fore_end_number).'"' : 'NULL').', 
            `shoe_serial` = '.((!empty($this->shoe_serial)) ? '"'.$this->db->real_escape_string($this->shoe_serial).'"' : 'NULL').', 
            `shoe_number` = '.((!empty($this->shoe_number)) ? '"'.$this->db->real_escape_string($this->shoe_number).'"' : 'NULL').',
            `quantity_incoming` = '.((!empty($this->quantity_incoming)) ? $this->quantity_incoming : 1).',
            `quantity_total` = '.((!is_null($this->quantity_total)) ? $this->quantity_total : ((!empty($this->quantity_incoming)) ? $this->quantity_incoming : 1)).',
            `storage` = "'.$this->db->real_escape_string($this->storage).'",
            `note` = '.((!empty($this->note)) ? '"'.$this->db->real_escape_string($this->note).'"' : 'NULL').',
            `last_decision` = '.((!empty($this->last_decision)) ? $this->last_decision : 'NULL').',
            `deleted` = 0,
            `update_date` = CURRENT_DATE,
            `update_time` = CURRENT_TIME,
            `update_active_id` = '.$this->active_id.'
          WHERE
            `id` = '.$this->id.'
        ';
        if (!$result = $this->db->query($query)) {
          if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
            throw new Exception('<b>WeaponAccount save error ('.$this->db->errno.')</b>: '.$this->db->error.' .Query string: '.$query);
          } else {
            throw new Exception('<center><b>Ошибка сохранения объекта "Оружие"!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
          }
          return false;
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
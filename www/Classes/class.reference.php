<?php
class Reference extends Site_DB {
  private $id;
  private $ovd;
  private $ovd_string;
  private $crime_case;
  private $create_date;
  private $create_time;
  private $deleted;
  private $kusp;
  private $files;
  private $files_count;
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
          r.`id`, 
          r.`ovd`, ovd.`ovd` as `ovd_string`,
          r.`crime_case`, r.`create_date`, r.`create_time`,
          IF(r.`deleted` = 0, "false", "true") as `deleted`
        FROM
          `l_references` as r
        JOIN
          `spr_ovd` as ovd ON
            ovd.`id_ovd` = r.`ovd`
        WHERE
          r.`id` = '.$id;
      $result = mysql_query($query) or die('<b>Reference constructor error</b>: '.mysql_error().'.Query string: <pre>'.$query.'</pre>');
      $row = mysql_fetch_assoc($result);
    }
    if ($row) {
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
      $query = ' SELECT `kusp` FROM `l_reference_kusp` WHERE `reference` = '.$id.' AND `deleted` = 0';
      if ($result = mysql_query($query)) {
        while ($row = mysql_fetch_assoc($result)) {
          $this->kusp[] = $row['kusp'];
        }
      }
      $query = ' SELECT `id` FROM `l_files` WHERE `reference` = '.$id;
      if ($result = mysql_query($query)) {
        $cnt = 0;
        while ($row = mysql_fetch_assoc($result)) {
          $this->files[0][] = $row['id'];
          $cnt++;
        }
        $this->files_count = $cnt;
      }
    } else {
      foreach (get_class_vars('Reference') as $ch => $v) {
        $this->$ch = null;
      }
      $this->files_count = 0;
    }
  }
  
  public function full_data() {
    if (!empty($this->crime_case) and !is_object($this->crime_case)) {
      $this->crime_case = new CrimeCase($this->crime_case);
    }
    
    if (count($this->files) > 0) {
      $cnt = 0;
      foreach ($this->files as $type => $files) {
        foreach ($files as $n => $file) {
          $this->files[$type][$n] = new ElFile($file);
          $cnt++;
        }
      }
      $this->files_count = $cnt;
    }
    
    if (count($this->kusp) > 0) {
      foreach ($this->kusp as $n => $kusp) {
        $this->kusp[$n] = new Kusp($kusp);
      }
    }
  }
  
  private function set_last_error($error) {
    $this->error = $error;
  }
  
  public function get_last_error() {
    return $this->error;
  }
  
  public function set_id($id) {
    $this->on_construct(0);
    $this->id = $id;
  }
  
  public function get_id() {
    return $this->id;
  }
  
  public function set_ovd($ovd) {
    if (!empty($ovd)) {
      require_once(KERNEL.'spr_ovd.php');
      if (!is_numeric($ovd) or !isset($spr_ovd[$ovd]))
        die('<b>OVD code is incorrect.</b>');
      
      $this->ovd = $ovd;
      $this->ovd_string = $spr_ovd[$ovd];
    }
  }
  
  public function get_ovd() {
    return $this->ovd;
  }
  
  public function get_ovd_string()  {
    return $this->ovd_string;
  }
  
  public function get_kusp_count() {
    return count($this->kusp);
  }
  
  public function get_create_date() {
    if (empty($this->create_date)) {
      return date('d.m.Y');
    } else {
      return $this->create_date;
    }
  }
  
  public function get_kusp_array() {
    $ret = null;
    if (!empty($this->kusp)) {
      foreach ($this->kusp as $n => $kusp) {
        $ret[$n] = $kusp->get_kusp_array();
      }
    }
    return (array)$ret;
  }
  
  public function get_kusp_list() {
    $ret = null;
    if (!empty($this->kusp)) {
      foreach ($this->kusp as $kusp) {
        $ret[] = $kusp;
      }
    }
    return (array)$ret;
  }
  
  public function add_kusp($obj) {
    if (!is_object($obj)) {
      $this->set_last_error('<b>KUSP must be an object.</b>');
      return false;
    }

    if ($this->get_kusp_count() > 0) {
      foreach ($this->kusp as $kusp) {
        if ($kusp->get_hash() == $obj->get_hash()) {
          $this->set_last_error('Связь с таким КУСП уже установлена!');
          return false;
        }
      }
    }
    $this->kusp[] = $obj;
    return true;
  }
  
  public function del_kusp($kusp) {
    if (!is_null($this->kusp)) {
      unset($this->kusp[$kusp]);
    }
    if (empty($this->kusp))
      $this->kusp = null;
  }
  
  public function set_crime_case($cc) {
    if (!is_object($cc)) {
      $this->set_last_error('<b>Crime case must be an object.</b>');
      return false;
    } else {
      $this->crime_case = $cc;
      return true;
    }
  }
  
  public function get_files_array($withSession = false) {
    $ret = null;
    if (count($this->files) > 0) {
      foreach (array(0) as $type) { // типы файлов по аналогии с ориентировкой, но здесь только один тип - справка
        foreach ($this->files[$type] as $n => $file) {
          $ret[$type][$n] = pathinfo($this->files[$type][$n]->get_path());
          $ret[$type][$n]['path'] = $this->files[$type][$n]->get_path();
          $ret[$type][$n]['size'] = filesize($this->files[$type][$n]->get_path());
          $ret[$type][$n]['indexed'] = $this->files[$type][$n]->is_indexed();
        }
      }
    }
    if ($withSession and !empty($_SESSION['reference']['files'])) {
      foreach (array_keys($_SESSION['reference']['files']) as $type) {
        foreach ($_SESSION['reference']['files'][$type] as $n => $file) {
          $ret[$type][$n] = $file;
        }
      }
    }
    return ($ret) ? $ret : false;
  }
  
  public function get_files_list() {
    if (count($this->files) > 0) {
      $ret = null;
      foreach (array(0) as $type) {
        foreach ($this->files[$type] as $n => $file) {
          $ret[] = $this->files[$type][$n];
        }
      }
      return $ret;
    }
    return array();
  }
  
  public function get_crime_case() {
    if (!empty($this->crime_case)) {
      return $this->crime_case;
    } else {
      return false;
    }
  }
  
  public function get_crime_case_id() {
    if (is_object($this->crime_case))
      return $this->crime_case->get_id();
  }
  
  public function get_crime_case_number() {
    if (is_object($this->crime_case))
      return $this->crime_case->get_number();
  }
  
  public function get_crime_case_date() {
    if (is_object($this->crime_case))
      return $this->crime_case->get_date();
  }
  
  public function get_crime_case_ovd() {
    if (is_object($this->crime_case))
      return $this->crime_case->get_ovd();
  }
  
  public function save_crime_case() {
    if (!empty($this->crime_case) and is_object($this->crime_case)) {
      $this->crime_case->save();
    } else {
      return false;
    }
  }
  
  public function save() {
    $this->db_connect();
    $query = '
      INSERT INTO
        `l_references`(`ovd`, `crime_case`, `deleted`,
                       `create_date`, `create_time`, `active_id`, `update_date`, `update_time`, `update_active_id`)
      VALUES
                      ('.$this->get_ovd().',
                       '.((is_object($this->crime_case)) ? $this->crime_case->get_id() : 'null').', 0,
                        CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
    ';
    if (!mysql_query($query)) {
      $this->set_last_error('<b>Reference save error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
      return false;
    }
    $this->id = mysql_insert_id();
    return true;
  }
  
  public function update() {
    if (is_null($this->get_id())) {
      $this->set_last_error('<b>Reference update error: ID is empty.</b>');
      return false;
    }

    $old_kusp = array();
    $this->db_connect();
    $query = mysql_query('
      SELECT
        ok.`kusp`
      FROM
        `l_reference_kusp` as ok
      WHERE
        ok.`reference` = '.$this->get_id().' AND
        ok.`deleted` = 0
    ');
    while($result = mysql_fetch_assoc($query)) {
      $old_kusp[] = $result['kusp'];
    }
    if (!empty($this->kusp) and is_object($kusp)) {
      $new_kusp = $add_str = array();
      foreach($this->kusp as $n => $kusp) {
        if (is_object($kusp)) {
          $new_kusp[] = $kusp->get_id();
        }
      }
      $on_del = array_diff($old_kusp, $new_kusp);
      $on_add = array_diff($new_kusp, $old_kusp);
      
      if (count($on_add) > 0) {
        foreach($on_add as $id => $kusp) {
          $add_str[] = '('.$kusp.', '.$this->get_id().', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')';
        }
        $query = '
          INSERT INTO
            `l_reference_kusp`(`kusp`, `reference`, `create_date`, `create_time`, `active_id`, `update_date`,`update_time`, update_active_id)
          VALUES
            '.implode(', ', $add_str).'
          ON DUPLICATE KEY UPDATE
            `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$this->active_id;
        if (!mysql_query($query)) {
          $this->set_last_error('<b>Reference update error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
          return false;
        }
      }
      if (count($on_del) > 0) {
        $query = '
          UPDATE
            `l_reference_kusp` as ok
          SET
            ok.`deleted` = 1,
            ok.`update_date` = current_date,
            ok.`update_time` = current_time,
            ok.`update_active_id` = '.$this->active_id.'
          WHERE
            ok.`reference` = '.$this->get_id().' AND
            ok.`kusp` IN ('.implode(', ', $on_del).')
        ';
        if (!mysql_query($query)) {
          $this->set_last_error('<b>Reference update error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
          return false;
        }
      }
    }
    if (is_object($this->crime_case)) {
      $cc = $this->get_crime_case_id();
    } elseif (is_numeric($this->crime_case)) {
      $cc = $this->crime_case;
    } else {
      $cc = 'null';
    }
    $query = '
      UPDATE
        `l_references`
      SET
        `ovd` = '.$this->get_ovd().',
        `crime_case` = '.$cc.', 
        `deleted` = 0,
        `update_date` = CURRENT_DATE, 
        `update_time` = CURRENT_TIME, 
        `update_active_id` = '.$this->active_id.'
      WHERE
        `id` = '.$this->id.'
    ';
    if (!mysql_query($query)) {
      $this->set_last_error('<b>Reference update error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
      return false;
    }
    return true;
  }
}
?>
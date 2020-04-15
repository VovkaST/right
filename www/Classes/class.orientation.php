<?php
class Orientation extends Site_DB {
  private $id;
  private $year;
  private $number;
  private $wonumber;
  private $date;
  private $ovd;
  public $ovd_string;
  private $uk;
  public $uk_string;
  private $marking;
  public $marking_string;
  private $crime_case;
  private $recall;
  private $kusp;
  private $files;
  private $files_count;
  private $deleted;
  private $error;
  
  public function __construct($ornt = 0) {
    $this->on_construct($ornt);
  }
  
  private function on_construct($i) {
    $row = null;
    if ($i != 0 and is_numeric($i)) {
      $this->db_connect();
      
      $query = '
        SELECT
          o.`id`, o.`year`, o.`number`, o.`wonumber`, 
          DATE_FORMAT(o.`date`, "%d.%m.%Y") as `date`, 
          o.`ovd`, ovd.`ovd` as `ovd_string`,
          o.`uk`, uk.`st` as `uk_string`,
          o.`marking`,
          ot.`type` as `marking_string`,
          o.`crime_case`,
          DATE_FORMAT(o.`recall`, "%d.%m.%Y") as `recall`, o.`deleted`
        FROM
          `l_orientations` as o
        JOIN
          `spr_ovd` as ovd ON
            ovd.`id_ovd` = o.`ovd`
        LEFT JOIN
          `spr_uk` as uk ON
            uk.`id_uk` = o.`uk`
        LEFT JOIN
          `spr_orientation_types` as ot ON
            ot.`id` = o.`marking`
        WHERE
          o.`id` = '.$i;
          
      if (!$result = mysql_query($query))
        $this->set_last_error('<b>Orientation constructor error</b>: '.mysql_error().' .Query string: <pre>'.$query.'</pre>');
      $row = mysql_fetch_assoc($result);
      $this->error = null;
      $this->files_count = 0;
    }
    if ($row) {
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
      $query = ' SELECT `id`, `type` FROM `l_files` WHERE `orientation` = '.$i;
      if ($result = mysql_query($query)) {
        $cnt = 0;
        while ($row = mysql_fetch_assoc($result)) {
          $this->files[$row['type']][] = $row['id'];
          $cnt++;
        }
        $this->files_count = $cnt;
      }
      $query = ' SELECT `kusp` FROM `l_orient_kusp` WHERE `orientation` = '.$i.' AND `deleted` = 0';
      if ($result = mysql_query($query)) {
        while ($row = mysql_fetch_assoc($result)) {
          $this->kusp[] = $row['kusp'];
        }
      }
    } else {
      foreach (get_class_vars('Orientation') as $ch => $v) {
        $this->$ch = null;
      }
      $this->wonumber = 0;
    }
  }
  
  public function full_data() {
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
    
    if (!empty($this->crime_case)) {
      $this->crime_case = new CrimeCase($this->crime_case);
    }
  }
  
  public function set_id($id) {
    $this->on_construct(0);
    $this->id = $id;
  }
  
  public function get_id() {
    return $this->id;
  }
  
  public function set_date($date) {
    $this->date = date('d.m.Y', strtotime($date));
    $this->year = date('Y', strtotime($this->date));
  }
  
  public function get_date() {
    return $this->date;
  }
  
  public function get_year() {
    return $this->year;
  }
  
  public function set_recall($date) {
    $this->recall = date('d.m.Y', strtotime($date));
  }
  
  public function get_recall() {
    return $this->recall;
  }
  
  public function set_ovd($ovd) {
    if (!empty($ovd)) {
      require(KERNEL.'spr_ovd.php');
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
  
  public function set_uk($uk) {
    require(KERNEL.'spr_uk.php');
    if (!is_numeric($uk) or !isset($spr_uk[$uk]))
      die('<b>OVD code is incorrect.</b>');
    
    $this->marking = null;
    $this->marking_string = null;
    $this->uk = $uk;
    $this->uk_string = $spr_uk[$uk];
  }
  
  public function get_uk() {
    return $this->uk;
  }
  
  public function get_uk_string() {
    return $this->uk_string;
  }
  
  public function set_marking($marking) {
    if (!is_numeric($marking))
      die('<b>Marking code is incorrect.</b>');
    $this->marking = $marking;
    $this->uk = null;
    $this->uk_string = null;
  }
  
  public function get_marking() {
    return $this->marking;
  }
  
  public function get_marking_string() {
    return $this->marking_string;
  }
  
  public function set_wonumber() {
    $this->wonumber = 1;
    $this->number = null;
  }
  
  public function get_wonumber() {
    return $this->wonumber;
  }
  
  public function set_number($number) {
    if (!is_numeric($number))
      die('<b>Number value must be a numeric.</b>');
    $this->wonumber = null;
    $this->number = $number;
  }
  
  public function get_number() {
    return $this->number;
  }
  
  public function set_crime_case($cc) {
    if (!is_object($cc))
      die('<b>Crime case must be an object.</b>');
    $this->crime_case = $cc;
  }
  
  public function unset_crime_case() {
    $this->crime_case = null;
  }
  
  public function get_crime_case() {
    if (!empty($this->crime_case)) {
      return $this->crime_case;
    } else {
      return false;
    }
  }
  
  public function save_crime_case() {
    if (!empty($this->crime_case) and is_object($this->crime_case)) {
      $this->crime_case->save();
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
  
  public function get_crime_case_ovd_string() {
    if (is_object($this->crime_case))
      return $this->crime_case->get_ovd_string();
  }
  
  private function set_last_error($error) {
    $this->error = $error;
  }
  
  public function get_last_error() {
    return $this->error;
  }
  
  public function get_files_array($t = 0, $withSession = false) {
    $ret = null;
    if (count($this->files) > 0) {
      $keys = (!$t) ? array_keys($this->files) : array($t);
      foreach ($keys as $type) {
        if (!isset($this->files[$type])) continue;
        foreach ($this->files[$type] as $n => $file) {
          $ret[$type][$n] = pathinfo($this->files[$type][$n]->get_path());
          $ret[$type][$n]['path'] = $this->files[$type][$n]->get_path();
          $ret[$type][$n]['size'] = filesize($this->files[$type][$n]->get_path());
          $ret[$type][$n]['indexed'] = $this->files[$type][$n]->is_indexed();
        }
      }
    }
    if ($withSession and !empty($_SESSION['orientation']['files'])) {
      foreach (array_keys($_SESSION['orientation']['files']) as $type) {
        foreach ($_SESSION['orientation']['files'][$type] as $n => $file) {
          $ret[$type][$n] = $file;
        }
      }
    }
    return ($ret) ? $ret : false;
  }
  
  public function get_files_list($t = 0) {
    if (count($this->files) > 0) {
      $keys = (!$t) ? array_keys($this->files) : array($t);
      $ret = null;
      foreach ($keys as $type) {
        if (!isset($this->files[$type])) continue;
        foreach ($this->files[$type] as $n => $file) {
          $ret[] = $this->files[$type][$n];
        }
      }
      return $ret;
    }
    return array();
  }
  
  public function get_kusp_count() {
    return count($this->kusp);
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
    if (!is_object($obj))
      die('<b>KUSP must be an object.</b>');

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
  
  public function get_path($real = false) {
    $p[] = ($real) ? 'f:/Site_storage' : '';
    $p[] = 'Orientations';
    $p[] = $this->year;
    $p[] = date('m', strtotime($this->date));
    $p[] = $this->id;
    $p[] = null;
    return implode('/', $p);
  }
  
  public function is_deleted() {
    return ($this->deleted) ? true : false;
  }
  
  public function save() {
    $this->db_connect();
    if (!empty($this->number)) {
      $query = '
        SELECT
          o.`id`
        FROM
          `l_orientations` as o
        WHERE
          o.`year` = '.$this->year.' AND
          o.`number` = '.$this->number.' AND
          o.`deleted` = 0
      ';
      if (!$result = mysql_query($query)) {
        $this->set_last_error('<b>Orientation save error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
        return false;
      }
      $row = mysql_fetch_assoc($result);
      if (!empty($row['id'])) {
        $this->set_last_error('<b>Запись с таким с таким годом и номером уже существует.</b> <a href="ornt_view.php?id='.$row['id'].'">Перейти &rarr;</a>');
        return false;
      }
    }
    $query = '
      INSERT INTO
        `l_orientations`(`year`, `number`, `wonumber`, `date`,
                         `ovd`, `uk`, `marking`, `crime_case`, `recall`,
                         `create_date`, `create_time`, `active_id`, `update_date`, `update_time`, `update_active_id`)
      VALUES
                        ('.$this->year.', '
                          .((!empty($this->number)) ? $this->number : 'null').', '
                          .round($this->get_wonumber()).', '
                          .((!empty($this->date)) ? '"'.date('Y-m-d', strtotime($this->date)).'"' : 'null').', '
                          .$this->ovd.', '
                          .((!empty($this->uk)) ? $this->uk : 'null').', '
                          .((!empty($this->marking)) ? $this->marking : 'null').', '
                          .((is_object($this->crime_case)) ? $this->crime_case->get_id() : 'null').', '
                          .((!empty($this->recall)) ? '"'.date('Y-m-d', strtotime($this->recall)).'"' : 'null').',
                          CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
    ';
    if (!mysql_query($query)) {
      $this->set_last_error('<b>Orientation save error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
      return false;
    }
    $this->id = mysql_insert_id();
    return true;
  }
  
  public function update() {
    if (is_null($this->get_id())) {
      $this->set_last_error('<b>Orientation update error: ID is empty.</b>');
      return false;
    }

    $old_kusp = array();
    $this->db_connect();
    $query = mysql_query('
      SELECT
        ok.`kusp`
      FROM
        `l_orient_kusp` as ok
      WHERE
        ok.`orientation` = '.$this->get_id().' AND
        ok.`deleted` = 0
    ');
    while($result = mysql_fetch_assoc($query)) {
      $old_kusp[] = $result['kusp'];
    }
    if (!empty($this->kusp)) {
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
            `l_orient_kusp`(`kusp`, `orientation`, `create_date`, `create_time`, `active_id`, `update_date`,`update_time`, update_active_id)
          VALUES
            '.implode(', ', $add_str).'
          ON DUPLICATE KEY UPDATE
            `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$this->active_id;
        if (!mysql_query($query)) {
          $this->set_last_error('<b>Orientation update error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
          return false;
        }
      }
      if (count($on_del) > 0) {
        $query = '
          UPDATE
            `l_orient_kusp` as ok
          SET
            ok.`deleted` = 1,
            ok.`update_date` = current_date,
            ok.`update_time` = current_time,
            ok.`update_active_id` = '.$this->active_id.'
          WHERE
            ok.`orientation` = '.$this->get_id().' AND
            ok.`kusp` IN ('.implode(', ', $on_del).')
        ';
        if (!mysql_query($query)) {
          $this->set_last_error('<b>Orientation update error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
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
        `l_orientations`
      SET
        `year` = '.$this->year.', 
        `number` = '.((!empty($this->number)) ? $this->number : 'null').', 
        `wonumber` = '.round($this->get_wonumber()).', 
        `date` = '.((!empty($this->date)) ? '"'.date('Y-m-d', strtotime($this->date)).'"' : 'null').',
        `ovd` = '.$this->ovd.', 
        `uk` = '.((!empty($this->uk)) ? $this->uk : 'null').', 
        `marking` = '.((!empty($this->marking)) ? $this->marking : 'null').', 
        `crime_case` = '.$cc.', 
        `recall` = '.((!empty($this->recall)) ? '"'.date('Y-m-d', strtotime($this->recall)).'"' : 'null').',
        `update_date` = CURRENT_DATE, 
        `update_time` = CURRENT_TIME, 
        `update_active_id` = '.$this->active_id.'
      WHERE
        `id` = '.$this->id.'
    ';
    if (!mysql_query($query)) {
      $this->set_last_error('<b>Orientation update error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
      return false;
    }
    return true;
  }

}
?>
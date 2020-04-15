<?php
class Decision {
  var $data = null;
  var $kusp = null;
  private $file = null;
  
  function db_connect() {
    require_once(KERNEL.'connection.php');
  }
  
  function set_type($type) {
    switch ($type) {
      case 1:
        $this->data = array(
          'id' => null,
          'type' => 1,
          'reg' => null,
          'date' => null,
          'status' => null,
          'ovd' => null,
          'service' => null,
          'upk' => null,
          'emp_s' => null,
          'emp_n' => null,
          'emp_fn' => null,
          'missed' => null,
          'anonymous' => null,
          'declarer_employeer' => null,
          'file_original' => null,
          'file_final' => null,
          'deleted' => null
        );
        $this->kusp = array('list' => array());
        break;
      case 2:
        $this->data = array(
          'id' => null,
          'type' => 1,
          'reg' => null,
          'date' => null,
          'ovd' => null,
          'service' => null,
          'emp_s' => null,
          'emp_n' => null,
          'emp_fn' => null,
          'file_original' => null,
          'file_final' => null,
          'deleted' => null
        );
        break;
    }
  }
  
  function set_id($id) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        d.`id`, d.`reg`, d.`type`, d.`date`, d.`status`, d.`ovd`, d.`service`, d.`upk`,
        d.`emp_s`, d.`emp_n`, d.`emp_fn`,
        d.`missed`, d.`anonymous`, d.`declarer_employeer`, d.`file_original`, d.`file_final`,
        d.`deleted`
      FROM
        `l_decisions` as d
      WHERE
        d.`id` = '.$id
    );
    $result = mysql_fetch_assoc($query);
    if ($result['id'] > 0 && $result['deleted'] == 0) {
      $this->data = $result;
      $this->related_faces();
      $this->related_organisations();
      $this->related_kusp();
      if ($this->data['missed'] == 0) {
        $this->related_uk();
      }
    } else {
      return false;
    }
  }
  
  public static function correct_file_type($file) {
    $tmp = pathinfo($file);
    if (in_array(mb_strtolower($tmp['extension'], 'UTF-8'), array('doc', 'docx', 'rtf'))) {
      return true;
    } else {
      return false;
    }
  }
  
  function restore_from_session() {
    $this->data = array_merge($this->data, $_SESSION['decision']['data']);
    if (!empty($_SESSION['decision']['uk'])) $this->uk = $_SESSION['decision']['uk'];
    if (!empty($_SESSION['decision']['kusp'])) $this->kusp = $_SESSION['decision']['kusp'];
    if (!empty($_SESSION['decision']['faces'])) $this->faces = $_SESSION['decision']['faces'];
    if (!empty($_SESSION['decision']['organisations'])) $this->organisations = $_SESSION['decision']['organisations'];
  }
  
  function save_to_session() {
    $m_dirs = array(1 => "01_январь", "02_Февраль", "03_Март", "04_Апрель", "05_Май", "06_Июнь", "07_Июль", "08_Август", "09_Сентябрь", "10_Октябрь", "11_Ноябрь", "12_Декабрь");
    
    if (!empty($_SESSION['decision'])) unset($_SESSION['decision']);
    
    if (empty($_SESSION['dir_session'])) {
      $_SESSION['dir_session'] = session_save_path()."_tmp_".session_id().'\\'; // временный каталог сессии
      if (!is_dir($_SESSION['dir_session'])) {
        mkdir($_SESSION['dir_session']); // создаем его
      }
    }
    
    $this->db_connect();
    $query = mysql_query(' SELECT o.`name_dir_otk` FROM `spr_ovd` as o WHERE o.`id_ovd` = '.$this->data['ovd'] );
    $result = mysql_fetch_assoc($query);
    $save_dir = 'd:/www.sites/files/Отказные/'.date('Y', strtotime($this->data['date'])).'год/'.$result['name_dir_otk'].'/'.$m_dirs[date('m', strtotime($this->data['date'])) + 0].'/';
    $save_dir = mb_convert_encoding($save_dir, 'Windows-1251', 'UTF-8');
    if (is_file($save_dir.mb_convert_encoding($this->data['file_final'], 'Windows-1251', 'UTF-8'))) {
      copy($save_dir.mb_convert_encoding($this->data['file_final'], 'Windows-1251', 'UTF-8'), $_SESSION['dir_session'].mb_convert_encoding($this->data['file_original'], 'Windows-1251', 'UTF-8'));
    } else {
      $this->data['file_final'] = $this->data['file_original'] = null;
    }
    $_SESSION['decision']['data'] = $_SESSION['decision']['old_data'] = $this->data;
    if (!empty($this->uk)) $_SESSION['decision']['uk'] = $this->uk;
    if (!empty($this->kusp)) $_SESSION['decision']['kusp'] = $this->kusp;
    if (!empty($this->faces)) $_SESSION['decision']['faces'] = $this->faces;
    if (!empty($this->organisations)) $_SESSION['decision']['organisations'] = $this->organisations;
  }
  
  function file_name() {
    $this->file = pathinfo($this->data['file_original']);
    foreach($this->kusp['list'] as $i) {
      $list[] = $i['kusp'];
    }
    if (!empty($list)) {
      sort($list);
      if (empty($this->data['reg'])) {
        $name = 'КУСП_'.date('Y', strtotime($this->data['date'])).'_'.implode('_', $list).'_(_reg_number_).'.$this->file['extension'];
      } else {
        $name = 'КУСП_'.date('Y', strtotime($this->data['date'])).'_'.implode('_', $list).'_('.$this->data['reg'].').'.$this->file['extension'];
      }
      return $name;
    } else {
      return false;
    }
  }
  
  function append_face($array) {
    switch(count($array)) {
      case 4:
        $req = array('rel', 'surname', 'name', 'borth');
        $add = array('id' => 0, 'fath_name' => '-');
        break;
      case 5:
        $req = array('rel', 'surname', 'name', 'fath_name', 'borth');
        $add = array('id' => 0);
        break;
      default:
        die('There are no enough parameters...');
        break;
    }
    if (in_array(count($array), array(4, 5))) {
      $dif = array_diff_key(array_flip($req), $array);
      if (count($dif) > 0) die('Wrong parameter (function: "append_face", param.: '.implode(', ', array_keys($dif)).')...');
      foreach($array as $f => $v) {
        $array[$f] = mb_convert_case($v, MB_CASE_UPPER, 'UTF-8');
      }
      $array['borth'] = date('Y-m-d', strtotime($array['borth']));
      $rel = $array['rel'];
      unset($array['rel']);
      $this->faces['list'][$rel][] = array_merge($add, $array);
    }
  }
  
  function save_faces() {
    $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db_connect();
    foreach($this->faces['list'] as $type => $item) {
      foreach($item as $n => $face) {
        if (empty($face['id'])) {
          foreach($face as $f => $v) {
            $this->faces['list'][$type][$n][$f] = mysql_real_escape_string($v);
          }
          mysql_query('
            INSERT ignore INTO
              `o_lico`(`surname`, `name`, `fath_name`, `borth`, `create_date`, `create_time`, `active_id`)
            VALUES
              (trim("'.$face['surname'].'"), trim("'.$face['name'].'"), trim("'.$face['fath_name'].'"), "'.$face['borth'].'",
              current_date, current_time, '.$active_id.')
            
          ');
          $id = mysql_insert_id();
          if ($id == 0) {
            $query = mysql_query('
              SELECT
                `id`
              FROM
                `o_lico`
              WHERE
                `surname` = "'.$face['surname'].'" AND
                `name` = "'.$face['name'].'" AND
                `fath_name` = "'.$face['fath_name'].'" AND
                `borth` = "'.$face['borth'].'"
            ');
            $result = mysql_fetch_assoc($query);
            $id = $result['id'];
          }
          $this->faces['list'][$type][$n]['id'] = $id;
        }
      }
    }
  }
  
  function related_faces() {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        rel.`type`, l.`id`, l.`surname`, l.`name`, l.`fath_name`, l.`borth`
      FROM
        `l_dec_lico` as rel
      JOIN
        `o_lico` as l ON
          l.`id` = rel.`face`
      WHERE
        rel.`decision` = '.$this->data['id'].' AND
        rel.`deleted` = 0
    ');
    while($face = mysql_fetch_assoc($query)) {
      $type = $face['type'];
      unset($face['type']);
      $this->faces['list'][$type][] = $face;
    }
  }
  
  function related_organisations() {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        rel.`type`, o.`id`, o.`title`
      FROM
        `l_dec_org` as rel
      JOIN
        `o_organisations` as o ON
          o.`id` = rel.`organisation`
      WHERE
        rel.`decision` = '.$this->data['id'].' AND
        rel.`deleted` = 0
    ');
    while($face = mysql_fetch_assoc($query)) {
      $type = $face['type'];
      unset($face['type']);
      $this->organisations['list'][$type][] = $face;
    }
  }
  
  function append_organisation($array) {
    switch(count($array)) {
      case 2:
        $req = array('rel', 'title');
        $add = array('id' => 0);
        break;
      default:
        die('There are no enough parameters...');
        break;
    }
    $dif = array_diff_key(array_flip($req), $array);
    if (count($dif) > 0) die('Wrong parameter (function: "append_organisation", param.: '.implode(', ', array_keys($dif)).')...');
    foreach($array as $f => $v) {
      $array[$f] = mb_convert_case($v, MB_CASE_UPPER, 'UTF-8');
    }
    $rel = $array['rel'];
    unset($array['rel']);
    $this->organisations['list'][$rel][] = array_merge($add, $array);
  }
  
  function save_organisations() {
    $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db_connect();
    foreach($this->organisations['list'] as $type => $item) {
      foreach($item as $n => $org) {
        if (empty($org['id'])) {
          foreach($org as $f => $v) {
            $this->organisations['list'][$type][$n][$f] = mysql_real_escape_string($v);
          }
          mysql_query('
            INSERT INTO
              `o_organisations`(`title`, `create_date`, `create_time`, `active_id`)
            VALUES
              ("'.$org['title'].'", current_date, current_time, '.$active_id.')
          ');
          $this->organisations['list'][$type][$n]['id'] = mysql_insert_id();
        }
      }
    }
  }
  
  function related_kusp() {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        k.`id`, k.`ovd`, k.`kusp`, k.`date`
      FROM
        `l_dec_kusp` as rel
      JOIN
        `l_kusp` as k ON
          k.`id` = rel.`kusp`
      WHERE
        rel.`decision` = '.$this->data['id'].' AND
        rel.`deleted` = 0
      ORDER BY
        k.`ovd`, k.`date`, k.`kusp`
    ') or die(mysql_error());
    while($kusp = mysql_fetch_assoc($query)) {
      $this->kusp['list'][] = $kusp;
    }
  }
  
  function add_kusp($array) {
    $req = array('kusp', 'date', 'ovd');
    if (count($array) == 3) {
      $dif = array_diff_key(array_flip($req), $array);
      if (count($dif) > 0) die('Too much parameters...');
      $this->kusp['list'][] = array_merge(array('id' => 0), $array);
    }
    if (!empty($this->data['file_original'])) {
      $this->file_name();
    }
  }
  
  function save_kusp() {
    $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db_connect();
    foreach($this->kusp['list'] as $n => $kusp) {
      $ek = null;
      if (empty($kusp['id'])) {
        $query = mysql_query('
          SELECT
            ek.`id`
          FROM
            `ek_kusp` as ek
          WHERE
            ek.`reg_number` = '.$kusp['kusp'].' AND
            DATE_FORMAT(ek.`reg_date`, "%Y") = "'.date("Y", strtotime($kusp['date'])).'" AND
            ek.`ovd` = '.$kusp['ovd'].'
          LIMIT 1
        ');
        $result = mysql_fetch_assoc($query);
        $ek = $result['id'];
        mysql_query('
          INSERT INTO
            `l_kusp`(`kusp`, `date`, `ovd`, `ek`, `create_date`, `create_time`, `active_id`)
          VALUES
            ('.$kusp['kusp'].', "'.date("Y-m-d", strtotime($kusp['date'])).'", '.$kusp['ovd'].', '.((!empty($ek)) ? $ek : 'NULL').',
            current_date, current_time, '.$active_id.')
          ON DUPLICATE KEY UPDATE
            `kusp` = "'.$kusp['kusp'].'"
        ');
        $id = mysql_insert_id();
        if ($id == 0) {
          $query = mysql_query('
            SELECT
              `id`
            FROM
              `l_kusp`
            WHERE
              `kusp` = '.$kusp['kusp'].' AND
              `date` = "'.date("Y-m-d", strtotime($kusp['date'])).'" AND
              `ovd` = '.$kusp['ovd'].'
          ');
          $result = mysql_fetch_assoc($query);
          $id = $result['id'];
        }
        $this->kusp['list'][$n]['id'] = $id;
      }
    }
  }
  
  function save_uk() {
    $this->db_connect();
    foreach($this->uk['list'] as $uk) {
      mysql_query('
        INSERT INTO
          `l_dec_uk`(`uk`, `decision`)
        VALUES
          ('.$uk.', '.$this->data['id'].')
        ON DUPLICATE KEY UPDATE
          `deleted` = 0
      ');
    }
  }
  
  function related_uk() {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        uk.`id_uk` as `uk`
      FROM
        `l_dec_uk` as rel
      JOIN
        `spr_uk` as uk ON
          uk.`id_uk` = rel.`uk`
      WHERE
        rel.`decision` = '.$this->data['id'].' AND
        rel.`deleted` = 0
      ORDER BY
        uk.`st`
    ');
    while($uk = mysql_fetch_assoc($query)) {
      $this->uk['list'][] = $uk['uk'];
    }
  }
  
  function add_uk($uk) {
    if (is_numeric($uk)) {
      $this->uk['list'][] = $uk;
    }
  }
  function add_uk_by_string($uk) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        uk.`id_uk`
      FROM
        `spr_uk` as uk
      WHERE
        uk.`st` = "'.mysql_real_escape_string($uk).'"
      LIMIT 1
    ');
    if (mysql_num_rows($query)) {
      $result = mysql_fetch_assoc($query);
      $this->uk['list'][] = $result['id_uk'];
    }
  }
  
  function add_service_by_string($s) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        s.`id_slujba`
      FROM
        `spr_slujba` as s
      WHERE
        s.`slujba` LIKE "'.mysql_real_escape_string($s).'"
      LIMIT 1
    ');
    $result = mysql_fetch_assoc($query);
    $this->data['service'] = $result['id_slujba'];
  }
  
  function save_decision() {
    $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db_connect();
    mysql_query('INSERT INTO `l_decisions`(`type`, `create_date`, `create_time`, `active_id`) VALUES ('.$this->data['type'].', CURRENT_DATE, CURRENT_TIME, '.$active_id.')');
    $this->data['id'] = mysql_insert_id();
  }
  
  function registration() {
    $old_data = $old_faces = $old_orgs = $old_uk = $old_kusp = $add_str = array();
    $m_dirs = array(1 => "01_январь", "02_Февраль", "03_Март", "04_Апрель", "05_Май", "06_Июнь", "07_Июль", "08_Август", "09_Сентябрь", "10_Октябрь", "11_Ноябрь", "12_Декабрь");
    $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db_connect();
    if (!empty($this->data['id'])) {
      $old_data = $_SESSION['decision']['old_data'];
      $query = mysql_query(' SELECT o.`name_dir_otk` FROM `spr_ovd` as o WHERE o.`id_ovd` = '.$old_data['ovd'] );
      $result = mysql_fetch_assoc($query);
      $old_data['dir'] = 'd:/www.sites/files/Отказные/'.date('Y', strtotime($old_data['date'])).'год/'.$result['name_dir_otk'].'/'.$m_dirs[date('m', strtotime($old_data['date'])) + 0].'/';
      $old_data['dir'] = mb_convert_encoding($old_data['dir'], 'Windows-1251', 'UTF-8');
      
      $query = mysql_query('
        SELECT
          dl.`face`, dl.`type`
        FROM
          `l_dec_lico` as dl
        WHERE
          dl.`decision` = '.$this->data['id'].' AND
          dl.`deleted` = 0
      ');
      while($result = mysql_fetch_assoc($query)) {
        $old_faces[$result['face']] = $result['type'];
      }
      $query = mysql_query('
        SELECT
          do.`organisation`, do.`type`
        FROM
          `l_dec_org` as do
        WHERE
          do.`decision` = '.$this->data['id'].' AND
          do.`deleted` = 0
      ');
      while($result = mysql_fetch_assoc($query)) {
        $old_orgs[$result['organisation']] = $result['type'];
      }
      $query = mysql_query('
        SELECT
          du.`uk`
        FROM
          `l_dec_uk` as du
        WHERE
          du.`decision` = '.$this->data['id'].' AND
          du.`deleted` = 0
      ');
      while($result = mysql_fetch_assoc($query)) {
        $old_uk[] = $result['uk'];
      }
      $query = mysql_query('
        SELECT
          dk.`kusp`
        FROM
          `l_dec_kusp` as dk
        WHERE
          dk.`decision` = '.$this->data['id'].' AND
          dk.`deleted` = 0
      ');
      while($result = mysql_fetch_assoc($query)) {
        $old_kusp[] = $result['kusp'];
      }
    } else {
      $this->save_decision();
    }
    
    if (isset($this->faces['list'])) {
      $this->save_faces();
      $new_faces = $add_str = array();
      foreach($this->faces['list'] as $type => $item) {
        foreach($item as $n => $face) {
          $new_faces[$face['id']] = $type;
        }
      }
      $on_del = array_diff_assoc($old_faces, $new_faces);
      $on_add = array_diff_assoc($new_faces, $old_faces);
      if (count($on_add) > 0) {
        foreach($on_add as $id => $rel) {
          $add_str[] = '('.$rel.', '.$id.', '.$this->data['id'].', CURRENT_DATE, CURRENT_TIME, '.$active_id.')';
        }
        mysql_query('
          INSERT INTO
            `l_dec_lico`(`type`, `face`, `decision`, `create_date`, `create_time`, `active_id`)
          VALUES
            '.implode(', ', $add_str).'
          ON DUPLICATE KEY UPDATE
            `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$active_id.'
        ');
      }
      if (count($on_del) > 0) {
        foreach($on_del as $id => $rel) {
          $del_str[] = '(dl.`face` = '.$id.' AND dl.`type` = '.$rel.')';
        }
        mysql_query('
          UPDATE
            `l_dec_lico` as dl
          SET
            dl.`deleted` = 1
          WHERE
            dl.`decision` = '.$this->data['id'].' AND
            (
             '.implode(' OR ', $del_str).'
            )
        ');
      }
    }
    
    if (isset($this->organisations['list'])) {
      $this->save_organisations();
      $new_orgs = $add_str = array();
      foreach($this->organisations['list'] as $type => $item) {
        foreach($item as $n => $org) {
          $new_orgs[$org['id']] = $type;
        }
      }
      $on_del = array_diff_assoc($old_orgs, $new_orgs);
      $on_add = array_diff_assoc($new_orgs, $old_orgs);
      if (count($on_add) > 0) {
        foreach($on_add as $id => $rel) {
          $add_str[] = '('.$rel.', '.$id.', '.$this->data['id'].', CURRENT_DATE, CURRENT_TIME, '.$active_id.')';
        }
        mysql_query('
          INSERT INTO
            `l_dec_org`(`type`, `organisation`, `decision`, `create_date`, `create_time`, `active_id`)
          VALUES
            '.implode(', ', $add_str).'
          ON DUPLICATE KEY UPDATE
            `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$active_id.'
        ');
      }
      if (count($on_del) > 0) {
        foreach($on_del as $id => $rel) {
          $del_str[] = '(do.`organisation` = '.$id.' AND do.`type` = '.$rel.')';
        }
        mysql_query('
          UPDATE
            `l_dec_org` as do
          SET
            do.`deleted` = 1, do.`update_date` = CURRENT_DATE, do.`update_time` = CURRENT_TIME, do.`update_active_id` = '.$active_id.'
          WHERE
            do.`decision` = '.$this->data['id'].' AND
            (
             '.implode(' OR ', $del_str).'
            )
        ');
      }
    }
    
    if (isset($this->kusp['list'])) {
      $add_str = array();
      $this->save_kusp();
      foreach($this->kusp['list'] as $n => $item) {
        $new_kusp[] = $item['id'];
      }
      $on_del = array_diff($old_kusp, $new_kusp);
      $on_add = array_diff($new_kusp, $old_kusp);
      
      if (count($on_add) > 0) {
        foreach($on_add as $id => $kusp) {
          $add_str[] = '('.$kusp.', '.$this->data['id'].', CURRENT_DATE, CURRENT_TIME, '.$active_id.')';
        }
        mysql_query('
          INSERT INTO
            `l_dec_kusp`(`kusp`, `decision`, `create_date`, `create_time`, `active_id`)
          VALUES
            '.implode(', ', $add_str).'
          ON DUPLICATE KEY UPDATE
            `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$active_id.'
        ');
      }
      if (count($on_del) > 0) {
        mysql_query('
          UPDATE
            `l_dec_kusp` as dk
          SET
            dk.`deleted` = 1,
            dk.`update_date` = current_date,
            dk.`update_time` = current_time,
            dk.`update_active_id` = '.$active_id.'
          WHERE
            dk.`decision` = '.$this->data['id'].' AND
            dk.`kusp` IN ('.implode(', ', $on_del).')
        ');
      }
    }
    
    if (isset($this->uk['list'])) {
      $add_str = array();
      $this->save_uk();
      $on_del = array_diff($old_uk, array_values($this->uk['list']));
      $on_add = array_diff_assoc(array_values($this->uk['list']), $old_uk);
      if (count($on_add) > 0) {
        foreach($on_add as $id => $uk) {
          $add_str[] = '('.$uk.', '.$this->data['id'].', CURRENT_DATE, CURRENT_TIME, '.$active_id.')';
        }
        mysql_query('
          INSERT INTO
            `l_dec_uk`(`uk`, `decision`, `create_date`, `create_time`, `active_id`)
          VALUES
            '.implode(', ', $add_str).'
          ON DUPLICATE KEY UPDATE
            `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$active_id.'
        ');
      }
      if (count($on_del) > 0) {
        mysql_query('
          UPDATE
            `l_dec_uk` as du
          SET
            du.`deleted` = 1,
            du.`update_date` = current_date,
            du.`update_time` = current_time,
            du.`update_active_id` = '.$active_id.'
          WHERE
            du.`decision` = '.$this->data['id'].' AND
            du.`uk` IN ('.implode(', ', $on_del).')
        ');
      }
    }
    
    $this->db_connect();
    if (empty($this->data['reg'])) {
      $y = date('Y', strtotime($this->data['date']));
      if ($y >= 2017) {
        mysql_query('set @a := 1');
        mysql_query('set @dif := 0');
        $reg_q = mysql_query('
          SELECT
            IF(
               COUNT(`reg`) = 0, 
               (SELECT IF(MAX(`reg`) IS NOT NULL, MAX(`reg`), 0) FROM `l_decisions` WHERE 
                 `date` BETWEEN STR_TO_DATE("'.$y.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$y.'-12-31", "%Y-%m-%d")), 
               `miss`) as `reg`
          FROM
            (
              SELECT
                `reg`,
                IF(`reg` > @a, @a - 1, NULL) as `miss`,
                @a := `reg` + 1
              FROM
                `l_decisions`
              WHERE
                `reg` IS NOT NULL AND
                `date` BETWEEN STR_TO_DATE("'.$y.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$y.'-12-31", "%Y-%m-%d")
              ORDER BY
                `reg`
            ) as `cntr`
          WHERE
            `miss` IS NOT NULL
          LIMIT 1
        ');
      } else {
        $reg_q = mysql_query('
          SELECT
            IF(MAX(d.`reg`) IS NULL, 0, MAX(d.`reg`)) as `reg`
          FROM
            `l_decisions` as d
          WHERE
            d.`type` = '.$this->data['type'].' AND
            YEAR(d.`date`) = '.$y.'
        ');
      }
      $reg = mysql_fetch_assoc($reg_q);
      $this->data['reg'] = ++$reg['reg'];
    }
    
    $this->data['file_final'] = $this->file_name();
    
    $add_str = array();
    foreach($this->data as $k => $v) {
      if (in_array($k, array('id', 'type', 'deleted'))) continue;
      $add_str[] = '`'.$k.'` = '.((empty($v)) ? 'NULL' : '"'.$v.'"').'';
    }
    mysql_query('
      UPDATE
        `l_decisions`
      SET
        '.implode(', ', $add_str).'
      WHERE
        `id` = '.$this->data['id']
    );
    
    
    $query = mysql_query(' SELECT o.`name_dir_otk` FROM `spr_ovd` as o WHERE o.`id_ovd` = '.$this->data['ovd'] );
    $result = mysql_fetch_assoc($query);
    $save_dir = 'd:/www.sites/files/Отказные/'.date('Y', strtotime($this->data['date'])).'год/'.$result['name_dir_otk'].'/'.$m_dirs[date('m', strtotime($this->data['date'])) + 0].'/';
    $save_dir = mb_convert_encoding($save_dir, 'Windows-1251', 'UTF-8');
    
  
    if (!empty($old_data['file_final'])) {
      $old_data['file_final'] = mb_convert_encoding($old_data['file_final'], 'Windows-1251', 'UTF-8');
      
      if (($old_data['dir'] != $save_dir) or ($old_data['file_final'] != $this->data['file_final'])) {
        rename($old_data['dir'].$old_data['file_final'], $old_data['dir'].$old_data['file_final'].'_renamed');
      }
    }
    
    if (!is_dir($save_dir)) {
      mkdir($save_dir, 0777, true);
    }
    
    $file_original = mb_convert_encoding($_SESSION['decision']['data']['file_original'], 'Windows-1251', 'UTF-8');
    
    if (copy($_SESSION['dir_session'].$file_original, $save_dir.mb_convert_encoding($this->data['file_final'], 'Windows-1251', 'UTF-8'))) {
      unlink($_SESSION['dir_session'].$file_original);
      return true;
    } else {
      return false;
    }
    
  }
  
}
?>
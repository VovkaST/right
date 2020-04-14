<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

class Decision {
  var $data = array('id' => null, 'reg' => null, 'date' => null, 'status' => null, 'ovd' => null, 'service' => null, 'emp_s' => null, 'emp_n' => null, 'emp_fn' => null, 'missed' => null, 'anonymous' => null, 'declarer_employeer' => null, 'file_original' => null, 'file_final' => null, 'deleted' => null);
  var $kusp = array('list' => array());
  
  function db_connect() {
    require_once(KERNEL.'connection.php');
  }
  
  function set_id($id) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        d.`id`, d.`reg`, d.`date`, d.`status`, d.`ovd`, d.`service`, 
        d.`emp_s`, d.`emp_n`, d.`emp_fn`,
        d.`missed`, d.`anonymous`, d.`declarer_employeer`, d.`file_original`, d.`file_final`,
        d.`deleted`
      FROM
        `l_decisions` as d
      WHERE
        d.`id` = '.$id);
    $result = mysql_fetch_assoc($query);
    if ($result['id'] > 0 && $result['deleted'] == 0) {
      $this->data = $result;
      $this->related_faces();
      $this->related_kusp();
      if ($this->data['missed'] == 0) {
        $this->related_uk();
      }
      $this->file_name();
    }
  }
  
  function file_name() {
    foreach($this->kusp['list'] as $i) {
      $list[] = $i['kusp'];
    }
    sort($list);
    $this->data['file_final'] = 'КУСП_'.date('Y', strtotime($this->data['date'])).'_'.implode('_', $list);
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
        if ($face['id'] == 0 || !isset($face['id'])) {
          foreach($face as $f => $v) {
            $this->faces['list'][$type][$n][$f] = mysql_real_escape_string($v);
          }
          mysql_query('
            INSERT INTO
              `o_lico`(`surname`, `name`, `fath_name`, `borth`, `create_date`, `create_time`, `active_id`)
            VALUES
              ("'.$face['surname'].'", "'.$face['name'].'", "'.$face['fath_name'].'", "'.$face['borth'].'",
              current_date, current_time, '.$active_id.')
            ON DUPLICATE KEY UPDATE
              `surname` = "'.$face['surname'].'"
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
    ');
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
    $this->file_name();
  }
  
  function save_kusp() {
    $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db_connect();
    foreach($this->kusp['list'] as $n => $kusp) {
      if ($kusp['id'] == 0 || !isset($kusp['id'])) {
        mysql_query('
          INSERT INTO
            `l_kusp`(`kusp`, `date`, `ovd`, `create_date`, `create_time`, `active_id`)
          VALUES
            ('.$kusp['kusp'].', "'.date("Y-m-d", strtotime($kusp['date'])).'", '.$kusp['ovd'].',
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
  
  function save_decision() {
    $this->db_connect();
    mysql_query('INSERT INTO `l_decisions`(`create_date`, `create_time`, `active_id`) VALUES (CURRENT_DATE, CURRENT_TIME, 0)');
    $this->data['id'] = mysql_insert_id();
  }
  
  function registration() {
    $old_faces = $old_uk = array();
    $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    if (!empty($this->data['id'])) {
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
    } else {
      $this->save_decision();
    }
    if (isset($this->faces['list'])) {
      $this->save_faces();
      $new_faces = array();
      foreach($this->faces['list'] as $type => $item) {
        foreach($item as $n => $face) {
          $new_faces[$face['id']] = $type;
        }
      }
      $on_del = array_diff_assoc($old_faces, $new_faces);
      $on_add = array_diff_assoc($new_faces, $old_faces);
      if (count($on_add) > 0) {
        foreach($on_add as $id => $rel) {
          $add_str[] = '('.$rel.', '.$id.', '.$this->data['id'].')';
        }
        mysql_query('
          INSERT INTO
            `l_dec_lico`(`type`, `face`, `decision`)
          VALUES
            '.implode(', ', $add_str).'
          ON DUPLICATE KEY UPDATE
            `deleted` = 0
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
    if (isset($this->uk['list'])) {
      $this->save_uk();
      $on_del = array_diff($old_uk, array_values($this->uk['list']));
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
    $reg = mysql_query('
      SELECT 
        @i := MAX(`reg`) 
      FROM 
        `l_decisions` 
      WHERE 
        YEAR(`date`) = YEAR('.$this->data['date'].')
    ');
    $this->data['id'] = $reg;
  }
  
}

?>
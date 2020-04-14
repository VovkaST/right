<?php
class Organisation {
  var $org = array(
    'id' => 0,
    'title' => '',
    'group' => '',
    'type' => ''
  );
  var $address;
  var $locality = 0;
  var $req_addr = true;
  
  function db_connect() {
    require_once(KERNEL.'connection.php');
  }
  
  function set_id($id) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        o.`id`, o.`title`, ot.`owner` as `group`, o.`type`, rel.`from_obj` as `address`
      FROM
        `o_organisations` as o
      LEFT JOIN
        `spr_org_types` as ot ON
          ot.`id` = o.`type`
      LEFT JOIN
        `l_relatives` as rel ON
          rel.`to_obj` = o.`id` AND
          rel.`to_obj_type` = 11 AND
          rel.`from_obj_type` = 3
      WHERE
        o.`id` = '.$id
    );
    $result = mysql_fetch_assoc($query);
    if ($result['address']) $this->related_address($result['address']);
    unset($result['address']);
    $this->org = $result;
    $this->req_addr();
  }
  
  function related_address($addr) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        a.`id`, a.`region`, a.`district`, a.`city`, a.`locality`, a.`street`, a.`house`, a.`house_lit`, a.`flat`, a.`flat_lit`
      FROM
        `o_address` as a
      WHERE
        a.`id` = '.$addr
    );
    $this->address['code'] = mysql_fetch_assoc($query);
    $query = mysql_query('
      SELECT
        a.`id`, 
        reg.`code` as `region`, dist.`code` as `district`, 
        city.`code` as `city`, loc.`code` as `locality`, str.`code` as `street`, 
        a.`house`, a.`house_lit`, a.`flat`, a.`flat_lit`
      FROM
        `o_address` as a
      JOIN
        `spr_region` as reg ON
          reg.`id` = a.`region`
      LEFT JOIN
        `spr_district` as dist ON
          dist.`id` = a.`district`
      LEFT JOIN
        `spr_city` as city ON
          city.`id` = a.`city`
      LEFT JOIN
        `spr_locality` as loc ON
          loc.`id` = a.`locality`
      LEFT JOIN
        `spr_street` as str ON
          str.`id` = a.`street`
      WHERE
        a.`id` = '.$addr
    );
    $this->address['kladr'] = mysql_fetch_assoc($query);
    $query = mysql_query('
      SELECT
        a.`id`,
        CONCAT(RTRIM(reg.`name`), " ", RTRIM(sreg.`scname`)) as `region`,
        CONCAT(RTRIM(dist.`name`), " ", RTRIM(sdist.`scname`)) as `district`,
        CONCAT(RTRIM(city.`name`), " ", RTRIM(scity.`scname`)) as `city`,
        CONCAT(RTRIM(loc.`name`), " ", RTRIM(sloc.`scname`)) as `locality`,
        CONCAT(RTRIM(str.`name`), " ", RTRIM(sstr.`scname`)) as `street`,
        a.`house`, a.`house_lit`, a.`flat`, a.`flat_lit`
      FROM
        `o_address` as a
      JOIN
        `spr_region` as reg ON
          reg.`id` = a.`region`
        JOIN
          `spr_socr` sreg ON
            sreg.`id` = reg.`socr` AND
            sreg.`level` = 1
      LEFT JOIN
        `spr_district` as dist ON
          dist.`id` = a.`district`
        LEFT JOIN
          `spr_socr` sdist ON
            sdist.`id` = dist.`socr` AND
            sdist.`level` = 2
      LEFT JOIN
        `spr_city` as city ON
          city.`id` = a.`city`
        LEFT JOIN
          `spr_socr` scity ON
            scity.`id` = city.`socr` AND
            scity.`level` = 3
      LEFT JOIN
        `spr_locality` as loc ON
          loc.`id` = a.`locality`
        LEFT JOIN
          `spr_socr` sloc ON
            sloc.`id` = loc.`socr` AND
            sloc.`level` = 4
      LEFT JOIN
        `spr_street` as str ON
          str.`id` = a.`street`
        LEFT JOIN
          `spr_socr` sstr ON
            sstr.`id` = str.`socr` AND
            sstr.`level` = 5
      WHERE
        a.`id` = '.$addr
    );
    $this->address['str'] = mysql_fetch_assoc($query);
  }
  
  function set_title($title) {
    $this->org['title'] = $title;
  }
  
  function set_type($type) {
    $this->db_connect();
    $query = mysql_query('SELECT t.`owner` FROM `spr_org_types` as t WHERE t.`id` = '.$type);
    $result = mysql_fetch_assoc($query);
    $this->org['type'] = $type;
    $this->org['group'] = $result['owner'];
    $this->req_addr();
  }
  
  function set_address($code) {
    $this->db_connect();
    switch(strlen($code)) {
      case 17:
        $query = mysql_query('
          SELECT
            r.`id` as `code_region`, r.`code` as `kladr_region`, r.`name` as `str_region`,
            d.`id` as `code_district`, d.`code` as `kladr_district`, d.`name` as `str_district`,
            c.`id` as `code_city`, c.`code` as `kladr_city`, c.`name` as `str_city`, 
            l.`id` as `code_locality`, l.`code` as `kladr_locality`, l.`name` as `str_locality`, 
            s.`id` as `code_street`, s.`code` as `kladr_street`, s.`name` as `str_street`
          FROM
            `spr_street` as s
          LEFT JOIN
            `spr_locality` as l ON
              l.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 11), "%") AND
              l.`code` LIKE "%00"
          LEFT JOIN
            `spr_city` as c ON
              c.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 8), "%") AND
              c.`code` LIKE "%00"
          LEFT JOIN
            `spr_district` as d ON
              d.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 5), "%") AND
              d.`code` LIKE "%00"
          LEFT JOIN
            `spr_region` as r ON
              r.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 2), "%") AND
              r.`code` LIKE "%00"
          WHERE
            s.`code` = "'.$code.'"
        ');
        break;
      case 13:
      case 14:
        $code = substr($code, 0, 13);
        if (substr($code, 8, 3) == '000') {
          $query = mysql_query('
            SELECT
              r.`id` as `code_code_region`, r.`code` as `kladr_region`, r.`name` as `str_region`,
              d.`id` as `code_district`, d.`code` as `kladr_district`, d.`name` as `str_district`,
              c.`id` as `code_city`, c.`code` as `kladr_city`, c.`name` as `str_city`
            FROM
              `spr_city` as c
            LEFT JOIN
              `spr_district` as d ON
                d.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 5), "%") AND
                d.`code` LIKE "%00"
            LEFT JOIN
              `spr_region` as r ON
                r.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 2), "%") AND
                r.`code` LIKE "%00"
            WHERE
              c.`code` = "'.$code.'"
          ');
        } else {
          $query = mysql_query('
            SELECT
              r.`id` as `code_region`, r.`code` as `kladr_region`, r.`name` as `str_region`,
              d.`id` as `code_district`, d.`code` as `kladr_district`, d.`name` as `str_district`,
              c.`id` as `code_city`, c.`code` as `kladr_city`, c.`name` as `str_city`, 
              l.`id` as `code_locality`, l.`code` as `kladr_locality`, l.`name` as `str_locality`
            FROM
              `spr_locality` as l
            LEFT JOIN
              `spr_city` as c ON
                c.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 8), "%") AND
                c.`code` LIKE "%00"
            LEFT JOIN
              `spr_district` as d ON
                d.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 5), "%") AND
                d.`code` LIKE "%00"
            LEFT JOIN
              `spr_region` as r ON
                r.`code` LIKE CONCAT(SUBSTRING("'.$code.'", 1, 2), "%") AND
                r.`code` LIKE "%00"
            WHERE
              l.`code` = "'.$code.'"
          ');
        }
        break;
    }
    $result = mysql_fetch_assoc($query);
    $this->address = array();
    foreach($result as $f => $v) {
      $this->address[substr($f, 0, strpos($f, '_'))][substr($f, strpos($f, '_')+1)] = $v;
    }
    (!empty($this->address['kladr']['locality'])) ? $this->set_locality($this->address['kladr']['locality']) : $this->set_locality($this->address['kladr']['city']);
  }

  function set_locality($loc) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        lp.`id`
      FROM
        `locality_passport` as `lp`
      WHERE
        lp.`locality` LIKE "'.$loc.'%"
    ');
    $result = mysql_fetch_assoc($query);
    $this->locality = $result['id'];
  }
  
  function req_addr() {
    if (in_array($this->org['group'], array(1, 11, 27, 31, 38))) {
      if ($this->org['type'] != 40) $this->req_addr = false;
    }
  }

  function more_related_objects($id) {
    $this->db_connect();
    $query = mysql_query('
      SELECT
        COUNT(r.`id`) as cnt
      FROM
        `l_relatives` as r
      WHERE
        (r.`from_obj` = '.$id.' AND r.`from_obj_type` = 3) OR
        (r.`to_obj` = '.$id.' AND r.`to_obj_type` = 3)
    ');
    $result = mysql_fetch_assoc($query);
    return ($result['cnt'] < 2) ? false : true;
  }
  
  function save_org() {
    $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    $this->db_connect();
    $query = mysql_query('DESCRIBE `o_organisations`');
    while ($result = mysql_fetch_assoc($query)) {
      if ($result['Field'] != 'id') $fields[] = strtolower($result['Field']);
    }
    
    
    if (!isset($this->org['id']) || ($this->org['id'] == 0)) { // если организация еще не в БД
      mysql_query('
        INSERT INTO
          `o_organisations`(`title`, `type`, `create_date`, `create_time`, `active_id`)
        VALUES
          ("'.mysql_real_escape_string($this->org['title']).'", '.$this->org['type'].', current_date, current_time, '.$activity_id.')
      ');
      $this->org['id'] = mysql_insert_id(); // вводим ее и получаем ее ID
    } else {
      foreach($this->org as $f => $v) {
        if (in_array($f, $fields)) {
          $str[] = '`'.$f.'` = "'.mysql_real_escape_string($v).'"';
        }
      }
      mysql_query('
        UPDATE
          `o_organisations`
        SET
          '.implode(', ', $str).',
          `update_date` = current_date,
          `update_time` = current_time,
          `update_active_id` = '.$activity_id.'
        WHERE
          `id` = '.$this->org['id']
      );  // иначе просто обновляем
    }
    
    mysql_query('
      INSERT INTO
        `l_pass_org_relative`(`locality_passport`, `organisation`, `create_date`, `create_time`, `active_id`)
      VALUES
        ('.$this->locality.', '.$this->org['id'].', current_date, current_time, '.$activity_id.')
      ON DUPLICATE KEY UPDATE
        `locality_passport` = '.$this->locality
    );
    
    if (is_array($this->address['code']) && isset($this->address['code']['street']) && !isset($this->address['code']['id'])) {
      $query = mysql_query('
        SELECT
          rel.`from_obj` as `address`
        FROM
          `l_relatives` as rel
        WHERE
          rel.`to_obj` = '.$this->org['id'].' AND
          rel.`to_obj_type` = 11 AND
          rel.`from_obj_type` = 3'
      );
      $old_addr = mysql_fetch_assoc($query); // получаем адрес из БД
      
      $str_q = $str_ins = array();
      foreach($this->address['code'] as $f => $v) {
        if ($f != 'id') {
          $str_q[] = (($v == '') ? '`'.$f.'` = 0 ' : '`'.$f.'` = "'.mysql_real_escape_string($v).'"');
          $str_ins[] = (($v == '') ? '"0"' : '"'.mysql_real_escape_string($v).'"');
        }
      }
      
      mysql_query('
        INSERT
          `o_address`(`region`, `district`, `city`, `locality`, `street`, `house`, `house_lit`, `flat`, `flat_lit`, `create_date`, `create_time`, `active_id`)
        VALUES
          ('.implode(', ', $str_ins).', current_date, current_time, '.$activity_id.')
        ON DUPLICATE KEY UPDATE
          `region` = "'.$this->address['code']['region'].'"
      '); // добавляем запись
      foreach($this->address as $t => $a) {
        $this->address[$t]['id'] = mysql_insert_id();  // получаем ее ID
      }
      
      if ($this->address['code']['id'] == 0) {  // если новая запись не была создана
        $query = mysql_query('SELECT `id` FROM `o_address` WHERE '.implode(' AND ', $str_q));  // ищем адрес в БД
        $result = mysql_fetch_assoc($query);
        foreach($this->address as $t => $a) {
          $this->address[$t]['id'] = $result['id'];  // получаем ее ID
        }
      }
      
      mysql_query('
        INSERT INTO
          `l_relatives`(`from_obj`, `from_obj_type`, `to_obj`, `to_obj_type`, `type`, `ais`, `create_date`, `create_time`, `active_id`)
        VALUES
          ('.$this->address['code']['id'].', 3, '.$this->org['id'].', 11, 72, 3, current_date, current_time, '.$activity_id.')
      ');  // добавляем связь
      
      
      mysql_query('
        DELETE FROM 
          `l_relatives` 
        WHERE 
          `from_obj` = '.$old_addr['address'].' AND
          `from_obj_type` = 3 AND
          `to_obj` = '.$this->org['id'].' AND
          `to_obj_type` = 11
      ');  //  разрываем старую связь
    }
    
  }
}
?>
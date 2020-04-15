<?php
class ElFile extends Site_DB {
  private $id;
  private $link;
  private $type;
  private $type_string;
  private $FilePath;
  private $FileContent;
  private $orientation;
  private $reference;
  private $indexed;
  private $create_date;
  private $mail_history;
  
  public function __construct($f = 0) {
    $this->on_construct($f);
  }
  
  private function on_construct($i) {
    $row = null;
    if ($i) {
      $this->db_connect();
      $i = mysql_real_escape_string($i);
      $query = '
        SELECT
          f.`id`, f.`link`, f.`type`,
          CASE
            WHEN f.`orientation` IS NOT NULL THEN (SELECT `type` FROM `spr_orientation` WHERE `id` = f.`type`)
            WHEN f.`reference` IS NOT NULL THEN "Обзорная справка по преступлению"
            WHEN f.`type` = 6 THEN "Обвинительное заключение/акт"
          END as `type_string`, 
          f.`FilePath`, f.`orientation`, f.`reference`,
          IF(fc.`FileContent` IS NULL, "false", "true") as `indexed`,
          DATE_FORMAT(f.`create_date`, "%d.%m.%Y") as `create_date`,
          f.`mail_history`
        FROM
          `l_files` as f
        LEFT JOIN
          `l_files_content` as fc ON
            fc.`file` = f.`id`
        WHERE ';
      if (is_numeric($i)) {
        $query .= 'f.`id` = '.$i;
      } else {
        $query .= 'f.`link` = "'.$i.'"';
      }
      $result = mysql_query($query) or die('<b>File constructor error</b>: '.mysql_error().' .Query string: '.$query);
      $row = mysql_fetch_assoc($result);
    }
    if ($row) {          // если есть файл с заданным id
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
    } else {
      foreach (get_class_vars('ElFile') as $ch => $v) {
        $this->$ch = null;
      }
      $this->indexed = false;
    }
  }
  
  public function get_content() {
    if ($this->id) {
      $this->db_connect();
      $query = '
        SELECT
          f.`FileContent`
        FROM
          `l_files_content` as f
        WHERE
          f.`file` = '.$this->id;
      $result = mysql_query($query) or die('<b>Error</b>: '.mysql_error().' .Query string: '.$query);
      $row = mysql_fetch_assoc($result);
      return $row['FileContent'];
    }
    return false;
  }
  
  public function full_data() {
    if (!empty($this->orientation) and is_numeric($this->orientation))
      $this->orientation = new Orientation($this->orientation);
  }
  
  public function is_indexed() {
    return ($this->indexed) ? true : false;
  }
  
  private function is_file_available($file) {
    if (is_file(mb_convert_encoding($file, 'Windows-1251', 'UTF-8'))) {
      return true;
    } else {
      return false;
    }
  }
  
  public function set_id($id) {
    $this->id = $id;
  }
  
  public function get_id() {
    return $this->id;
  }
  
  public function get_link() {
    return $this->link;
  }
  
  public function get_type() {
    return $this->type;
  }
  
  public function get_type_string() {
    return $this->type_string;
  }
  
  public function get_path() {
    return $this->FilePath;
  }
  
  public function get_section() {
    if (!empty($this->orientation)) {
      return 'orientation';
    } elseif (!empty($this->reference)) {
      return 'reference';
    } else {
      return false;
    }
  }
  
  public function get_file_name() {
    if (!empty($this->FilePath)) {
      return pathinfo($this->FilePath, PATHINFO_BASENAME);
    }
  }
  
  public function get_create_date() {
    return $this->create_date;
  }
  
  public function set_type($type) {
    $this->type = $type;
  }
  
  public function set_file($file) {
    if (!$this->is_file_available($file)) {
      die('<b>File "'.$file.'" is not available.</b>');
    } else {
      $this->on_construct(0);
      $file = str_replace('\\', '/', $file);
      $this->FilePath = $file;
      return true;
    }
  }
  
  public function get_mail_history() {
    if (empty($this->mail_history))
      return array();
    if (is_array($this->mail_history)) {
      return $this->mail_history;
    } else {
      return explode(',', $this->mail_history);
    }
  }
  
  public function set_history($new) {
    if (!is_array($new))
      $new = (array)$new;
    $h = array_merge($this->get_mail_history(), $new);
    $h = array_unique($h);
    if (empty($h))
      return false;
    $query = '
      UPDATE
        `l_files`
      SET
        `mail_history` = "'.implode(',', $h).'"
      WHERE
        `id` = '.$this->get_id();
    $this->db_connect();
    if (!mysql_query($query))
      die('<b>Save history error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
    $this->mail_history = array_merge($h, array());
    return true;
  }
  
  public function set_content($content) {
    $content = preg_replace("/\xC2\xA0/ui", ' ', $content);
    $content = preg_replace(array('/[_\t\a\b«»“”"\'`~]/ui', '/ {2,}/ui', '/(\r\n ??){2,}/ui', '/\.{2,}/ui', '/ё/ui'), array(' ', ' ', "\r\n", '.', 'е'), $content);
    $content = preg_replace(array("/\s+?/ui", '/\s{2,}/ui'), ' ', $content);
    $content = trim($content);
    
    $this->FileContent = $content;
  }
  
  
  public function move($file, $target, $newName = null) {
    if (empty($newName))
      $newName = pathinfo($file, PATHINFO_BASENAME);
    
    if (!$this->is_file_available($file))
      die('<b>File "'.$file.'" is not available.</b>');
      
    if (!is_dir($target))
      mkdir($target, 0777, true);
    
    $target = str_replace('\\', '/', $target);                  // заменяем левые слешы на правые
    if (!preg_match('/^(.*)(\\\|\/)$/s', $target))
      $target .= '/';                                           // проверяем, чтобы последний символ был слэшем      
    
    $from = mb_convert_encoding($file, 'Windows-1251', 'UTF-8');
    $to = mb_convert_encoding($target.$newName, 'Windows-1251', 'UTF-8');
    
    if (copy($from, $to)) {
      unlink($from);
      return true;
    } else {
      return false;
    }
  }
  
  public function save($type, $parent_table = null, $parent_table_id = null) {
    if (!is_numeric($type))
      die('<b>File type must be a numeric.</b>');
    if (!empty($parent_table_id) and !is_numeric($parent_table_id))
      die('<b>Record ID from parent`s table must be a numeric.</b>');
    switch ($parent_table) {
      case 'orientation': $parent = array($parent_table_id, 'null'); break;
      case 'reference':   $parent = array('null', $parent_table_id); break;
      default:            $parent = array('null', 'null');           break;
    }

    $this->db_connect();
    $query = '
      INSERT INTO
        `l_files`(`type`, `orientation`, `reference`,
                  `create_date`, `create_time`, `active_id`, `update_date`, `update_time`, `update_active_id`)
      VALUES
                 ('.$type.', '.implode(', ', $parent).', 
                  CURRENT_DATE, CURRENT_TIME, '.$this->active_id.', CURRENT_DATE, CURRENT_TIME, '.$this->active_id.')
    ';
    if (!mysql_query($query))
      die('<b>Save file error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
    
    $this->set_id(mysql_insert_id());
    
    
    $query = '
      UPDATE
        `l_files`
      SET
        `link` = "'.(md5($this->get_id() + 16)).'"
      WHERE
        `id` = '.$this->get_id();
    if (!mysql_query($query))
      die('<b>Save file error: '.mysql_error().". \nQuery:\n<pre>".$query.'</pre></b>');
    
  }
  
}
?>
<?php
class Event extends Site_DB {
  private $id;
  private $event_code;
  private $ovd;
  private $ovd_string;
  private $reason;
  private $reg_date;
  private $reg_time;
  private $reg_number;
  private $reg_number_area;
  private $ticket_number;
  private $reg_emp;
  private $declarer_person;
  private $declarer_tel;
  private $declarer_address;
  private $declarer_org;
  private $story;
  private $note;
  private $crime_date;
  private $marking;
  private $first_decision;
  private $last_decision;
  private $relatives;
  
  public function __construct($event = 0) {
    $this->on_construct($event);
  }
  
  private function on_construct($i) {
    $row = null;
    if ($i != 0 and is_numeric($i)) {
      $this->db_connect();
      $query = '
        SELECT
          k.`id`, k.`event_code`, k.`ovd`, ovd.`ovd` as `ovd_string`, k.`reason`,
          DATE_FORMAT(k.`reg_date`, "%d.%m.%Y") as `reg_date`,
          TIME_FORMAT(k.`reg_time`, "%H:%i") as `reg_time`, k.`reg_number`, k.`reg_number_area`, k.`ticket_number`, k.`reg_emp`,
          k.`declarer_person`, k.`declarer_tel`, k.`declarer_address`, k.`declarer_org`,
          k.`story`, k.`note`,
          DATE_FORMAT(k.`crime_date`, "%d.%m.%Y") as `crime_date`,
          k.`marking`, k.`first_decision`, k.`last_decision`
        FROM
          `ek_kusp` as k
        JOIN
          `spr_ovd` as ovd ON
            ovd.`id_ovd` = k.`ovd`
        WHERE
          k.`id` = '.$i;
      $result = mysql_query($query) or die('<b>Event_decision constructor error</b>: '.mysql_error().'.Query string: <pre>'.$query.'</pre>');
      $row = mysql_fetch_assoc($result);
    }
    if ($row) {
      foreach ($row as $field => $value) {
        $this->$field = get_var_in_data_type($value);
      }
    } else {
      foreach(get_class_vars('Event') as $ch => $v) {
        $this->$ch = null;
      }
    }
  }
  
  public function get_id() {
    return $this->id;
  }
  
  public function get_ovd() {
    return $this->ovd;
  }
  
  public function get_ovd_string() {
    return $this->ovd_string;
  }
  
  public function get_reason() {
    return $this->reason;
  }
  
  public function get_reg_number() {
    return $this->reg_number;
  }
  
  public function get_reg_date() {
    return $this->reg_date;
  }
  
  public function get_reg_time() {
    return $this->reg_time;
  }
  
  public function get_declarer() {
    if (empty($this->declarer_person)) {
      return $this->declarer_org;
    } else {
      return $this->declarer_person;
    }
  }
  
  public function get_declarer_tel() {
    return $this->declarer_tel;
  }
  
  public function get_declarer_address() {
    return $this->declarer_address;
  }
  
  public function get_reg_emp() {
    return $this->reg_emp;
  }
  
  public function get_ticket_number() {
    return $this->ticket_number;
  }
  
  public function get_marking() {
    return $this->marking;
  }
  
  public function get_story() {
    return $this->story;
  }
  
  public function full_data() {
    if (!empty($this->first_decision) and is_numeric($this->first_decision))
      $this->first_decision = new Event_decision($this->first_decision);
      
    if (!empty($this->last_decision) and is_numeric($this->last_decision))
      $this->last_decision = new Event_decision($this->last_decision);
  }
  
  public function get_history() {
    $ret = array();
    $query = 'SELECT dh.`id` FROM `ek_dec_history` as dh WHERE dh.`kusp` = '.$this->id.' ORDER BY dh.`dec_date` DESC';
    $this->db_connect();
    $result = mysql_query($query) or die('<b>Event history error</b>: '.mysql_error().'.Query string: <pre>'.$query.'</pre>');
    while ($row = mysql_fetch_assoc($result)) {
      $ret[] = new Event_decision($row['id']);
    }
    return $ret;
  }

  public function set_kusp($number, $date, $ovd) {
    try {
      if (!is_numeric($number))
        throw new Exception('Error: Regisration number is not valid Integer value!');
      $date = date('d.m.Y', strtotime($date));
      if (!is_numeric($number))
        throw new Exception('Error: OVD is not valid Integer value!');
    } catch (Exception $E) {
      die('<b>Set kusp error</b>: '.$E->getMessage());
    }
    $this->on_construct(0);
    $this->reg_number = $number;
    $this->reg_date = $date;
    $this->ovd = $ovd;
  }
  
  public function get_relatives() {
    $this->db_connect();
    mysql_query('SET @kusp = '.$this->id);
    $query = '
      (
        SELECT SQL_NO_CACHE
          CONCAT("Ориентировка ", 
            IF(o.`number` > 0, CONCAT("№ ", o.`number`), "б/н"),
            " от ",
            DATE_FORMAT(o.`date`, "%d.%m.%Y")
          ) as `str`,
          CONCAT(
            "<a href=\"/wonc/ornt_view.php?id=", o.`id`, "\">Ориентировка ", IF(o.`number` > 0, CONCAT("№ ", o.`number`), "б/н"),
            " от ",
            DATE_FORMAT(o.`date`, "%d.%m.%Y"), "</a>"
          ) as `link`,
          o.`date`
        FROM
          `l_orient_kusp` as ok
        JOIN
          `l_kusp` as k ON
            k.`id` = ok.`kusp` AND
            k.`ek` = @kusp AND
            ok.`deleted` = 0
        JOIN
          `l_orientations` as o ON
            o.`id` = ok.`orientation` AND
            o.`deleted` = 0
      )

      UNION

      (
        SELECT
          CONCAT("Обзорная справка от ",
            DATE_FORMAT(r.`create_date`, "%d.%m.%Y")
          ) as `str`,
          CONCAT("<a href=\"/wonc/ref_view.php?id=", r.`id`, "\">Обзорная справка от ",
            DATE_FORMAT(r.`create_date`, "%d.%m.%Y"), "</a>"
          ) as `link`,
          r.`create_date`
        FROM
          `l_reference_kusp` as rk
        JOIN
          `l_kusp` as k ON
            k.`id` = rk.`kusp` AND
            k.`ek` = @kusp AND
            rk.`deleted` = 0
        JOIN
          `l_references` as r ON
            r.`id` = rk.`reference`
      )
      
      UNION
      
      (
        SELECT
          CONCAT("Постановление об отказе в ВУД от ",
            DATE_FORMAT(d.`date`, "%d.%m.%Y")
          ) as `str`,
          CONCAT("<a href=\"/decisions/search.php?k_ovd=", k.`ovd`,"&k_date=", DATE_FORMAT(k.`date`, "%d.%m.%Y"), "&k_kusp=", k.`kusp`, "&obj=1\">Постановление об отказе в ВУД от ",
            DATE_FORMAT(d.`date`, "%d.%m.%Y"), "</a>"
          ) as `link`,
          d.`date`
        FROM
          `l_dec_kusp` as dk
        JOIN
          `l_kusp` as k ON
            k.`id` = dk.`kusp` AND
            k.`ek` = @kusp
        JOIN
          `l_decisions` as d ON
            d.`id` = dk.`decision`
        WHERE
          dk.`deleted` = 0
      )
      
      ORDER BY
        `date` DESC
    ';
    $result = mysql_query($query) or die('<b>Event relatives error</b>: '.mysql_error().'.Query string: <pre>'.$query.'</pre>');
    
    while ($row = mysql_fetch_assoc($result)) {
      $this->relatives[] = $row;
    }
    return $this->relatives;
  }

}
?>
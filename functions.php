<?php

function client_MAC_address($ip) {
  $output = exec("arp -a ".$ip); //получаем строку из ARP таблицы
  $output = trim($output);
  $output = preg_replace('/ +/', ' ', $output);
  if ($output=="No ARP Entries Found") { return "Not found in ARP tables"; };
  $parts = explode(' ', $output);
  unset($parts[(count($parts) - 1)], $parts[0]);
  return implode(' ', $parts);
}

//преобразование даты
function convertDate($date) {
	if (substr_count($date, ".")) {
		$date_new = explode(".", $date);
		return $date_new[2]."-".$date_new[1]."-".$date_new[0];
	} else {
		$date_new = explode("-", $date);
		return $date_new[2].".".$date_new[1].".".$date_new[0];
	}
}

function check_date_format($date) {
  // если формат "ГГГГ-ММ-ДД", то преобразуем в "ДД.ММ.ГГГГ"
  if (preg_match("|^[1-2]\d{3}-[0-1]\d\-[0-3]\d$|", $date)) {
    $date = date('d.m.Y', strtotime($date));
  }
  // если формат не "ДД.ММ.ГГГГ", то удаляем элемент
  if (!preg_match("|^[0-3]\d[\.\/][0-1]\d[\.\/][1-2]\d{3}$|", $date))
    return false;
  else
    return preg_replace('|/|', '.', $date);
}

function check_datetime_format($dt) {
  $dt = trim($dt);
  $dt = preg_replace(array('/\s{2,}/', '/[\\/]/'), array(' ', '.'), $dt);
  if (preg_match_all('/([1-2]\d{3}-[0-1]\d\-[0-3]\d|[0-3]\d[\.\/][0-1]\d[\.\/][1-2]\d{3})\s+(\d\d:\d\d:\d\d)([\.\d]*){0,1}/', $dt, $dt)) {
    $dt = strtotime($dt[1][0].' '.$dt[2][0]);
    return date('d.m.Y H:i:s', $dt);
  }
  return false;
}

// ******** склонение слов ******** //
function case_of_word($num, $word) {
  $vocabulary = array(
    'день' => array('день', 'дня', 'дней'),
    'год' => array('год', 'года', 'лет'),
    'месяц' => array('месяц', 'месяца', 'месяцев'),
    'ответ' => array('ответ', 'ответа', 'ответов'),
    'вид' => array('вид', 'вида', 'видов'),
    'организация' => array('организация', 'организации', 'организаций')
  );
  if ($num >= 10 and $num <= 20) {
    $index = 2;
  } else {
    switch ($num%10) {
      case 1:
        $index = 0;
        break;
      case 2:
      case 3:
      case 4:
        $index = 1;
        break;
      default:
        $index = 2;
        break;
    }
  }
  if (!empty($vocabulary[$word])) {
    return $vocabulary[$word][$index];
  } else {
    return 'Unknown word!';
  }
}
// ^^^^^^^^ склонение слов ^^^^^^^^ //

function save_button($name = '', $status = true) {
  $disable = $res = '';
  if ($status) {
    $res .= '<a href="#" class="save_str">';
    $res .= '<img src="'.IMG.'plus.png"/>';
  } else {
    $res .= '<a class="save_str" status="disabled">';
    $res .= '<img src="'.IMG.'plus_disabled.png"/>';
  }
  $res .= '<span class="save_str_txt">'.$name.'</span>';
  $res .= '</a>';
  return $res;
}

function file_input($name, $disabled = false) {
  $ret = '<div class="uploading_file_block">';
  $ret .= '<div class="file_input">';
  $ret .= '<span class="file_input_value">'.(($disabled) ? '&nbsp;' : 'Выберите файл...').'</span>';
  $ret .= '<input type="file" class="files_input" name="'.$name.'"'.(($disabled) ? ' disabled' : '').'/>';
  $ret .= '<div class="file_overview">Обзор...</div>';
  $ret .= '</div>';
  $ret .= '<div class="file_upload"'.(($disabled) ? ' status="disabled"' : '').'>Загрузить...</div>';
  $ret .= '<iframe name="frame" style="display: none"></iframe>';
  $ret .= '</div>';
  return $ret;
}

function ovd($login) {
  $ovd = (int)substr($login, 1, 2);
  require_once('connection.php');
  $ovd_query = mysql_query('
    SELECT
      `id_ovd` as `ovd_id`, `ovd_full`
    FROM
      `spr_ovd`
    WHERE
      `ibd_code` = "'.$ovd.'"
  ') or die(mysql_error());
  $ovd = mysql_fetch_array($ovd_query);
  return (empty($ovd['ovd_id'])) ? array('ovd_id' => null, 'ovd_full' => null, 0 => null, 1 => null) : $ovd;
}

function getOvdName($id, $ibd = false) {
  require ('connection.php');
  $query = '
    SELECT
      '.(($ibd) ? 'ibd_code' : 'id_ovd').', IF(`OVD_SODCH` IS NOT NULL, `OVD_SODCH`, `ovd_full`) as ovd_full, ovd, ibd_code
    FROM
      spr_ovd
    WHERE
      '.(($ibd) ? 'ibd_code = '.$id : 'id_ovd = '.$id).'
      
  ';
  $ovd_query = mysql_query($query) or die(mysql_error().': <pre>'.$query.'</pre>');
  
  while ($ovd = mysql_fetch_array($ovd_query)) {
    return array(
      0 => $ovd["ovd"], 
      'ovd' => $ovd["ovd"],
      1 => $ovd["ovd_full"], 
      'ovd_full' => $ovd["ovd_full"],
      2 => $id, 
      'id' => $id,
      3 => $ovd["ibd_code"],
      'ibd_code' => $ovd["ibd_code"]
    );
  }
}

function new_session($user) {
  if (is_array($user)) {
    foreach($user as $k =>$v) {
      $_SESSION['user'][$k] = get_var_in_data_type($v);
    }
  } else {
    require('connection.php');
    $query = mysql_query('
      SELECT
        u.`id`, u.`user`,
        IF(u.`password` IS NULL, 0, 1) as `pass`,
        CONCAT(
          IF(u.`emp_name` IS NOT NULL, CONCAT(SUBSTRING(u.`emp_name`, 1, 1), "."), NULL),
          IF(u.`emp_f_name` IS NOT NULL, CONCAT(SUBSTRING(u.`emp_f_name`, 1, 1), "."), NULL),
          u.`emp_surname`
        ) as `employeer`,
        u.`emp_surname`, u.`emp_name`, u.`emp_f_name`,
        ovd.`id_ovd` as `ovd_id`, ovd.`ovd`, u.`ibd_login`, u.`weapon`,
        u.`active`, u.`checked`, u.`admin`, u.`references`, u.`ornt_reconcil`, u.`ornt_create`
      FROM
        `users` as u
      LEFT JOIN
        `spr_ovd` as ovd ON
          ovd.`id_ovd` = u.`ovd`
      WHERE
        u.`ibd_login` = "'.$user.'"
      LIMIT 1
    ');
    $result = mysql_fetch_assoc($query);
    if (empty($result['id'])) {
      $ovd = ovd($user);
      mysql_query('
        INSERT INTO
          `users`(`user`, `ibd_login`, `ovd`, `reg_date`, `reg_time`)
        VALUES
          ("'.$user.'", "'.$user.'", '.$ovd['ovd_id'].', current_date, current_time)
      ');
      $_SESSION['user'] = array(
        'id' => mysql_insert_id(),
        'user' => $user,
        'ibd_login' => $user,
      );
      foreach(array_merge($_SESSION['user'], $ovd) as $k =>$v) {
        $_SESSION['user'][$k] = get_var_in_data_type($v);
      }
    } else {
      foreach($result as $k =>$v) {
        $_SESSION['user'][$k] = get_var_in_data_type($v);
      }
    }
  }
  $_SESSION['dir_session'] = session_save_path()."_tmp_".session_id().'\\'; // временный каталог сессии
  if (!is_dir($_SESSION['dir_session'])) {
    mkdir($_SESSION['dir_session']); // создаем его
  }
  $_SESSION['activity_id'] = new_activity($_SESSION['user']['user']);// записываем в БД
  $_SESSION['last_activity_time'] = time();
}

function new_activity($user) {
  require('connection_log.php');
	mysql_query('
		INSERT INTO
			activity(session_id, ip, user, browser,
			enter_date, enter_time, last_active_date, last_active_time)
		VALUES
			("'.session_id().'", "'.$_SERVER['REMOTE_ADDR'].'", "'.$user.'", "'.CLIENT_BROWSER.'",
			current_date, current_time, current_date, current_time)
	') or die(mysql_error());
  return mysql_insert_id();
}


function activity($sess_id) {
  if(!is_dir($_SESSION['dir_session'])) {
    mkdir($_SESSION['dir_session']);
  }
  require('connection_log.php');
	return mysql_query('
		UPDATE
			activity
		SET
			last_active_date = current_date,
			last_active_time = current_time
		WHERE
			id = "'.$sess_id.'"
	');
}


function query_log($i = null, $par) {
  $activity_id = ((empty($_SESSION['activity_id'])) ? 0 : $_SESSION['activity_id']);
  if (is_array($par)) {
    $woempty = array();
    foreach ($par as $key => $value) {
      if (strlen($value) != 0) {
        $woempty[] = $key."=".$value;
      }
    }
    $query_str = implode("&", $woempty);
  } else {
    $query_str = $par;
  }
  $referer = (!empty($_SERVER["HTTP_REFERER"])) ? basename($_SERVER["HTTP_REFERER"]) : null;
  $script_file = basename($_SERVER["SCRIPT_FILENAME"]);
  require('connection_log.php');
  mysql_query('
    INSERT INTO
      search(activity_id, referer, script_file, query, date, time)
    VALUES
      ("'.$activity_id.'", "'.$referer.'", "'.$script_file.'", "'.$query_str.'", current_date, current_time)
  ');
  mysql_close($db_log);
}


function dir_del($dir) {
  $arr = glob($dir.'*', GLOB_MARK);
  foreach($arr as $item) {
    if(is_dir($item)) {
      dir_del($item);
    } else {
      unlink($item);
    }
  }
  if (is_dir($dir)) {
    rmdir($dir);
  }
}

function exit_session() {
  $sess_id = session_id();
  if (isset($_SESSION['dir_session']) and is_dir($_SESSION['dir_session'])) {
    dir_del($_SESSION["dir_session"]); // удаляем временный каталог
  }
  @session_destroy(); // уничтожаем сессию
  if (isset($_COOKIE['sess_id'])) {
    setcookie("sess_id", $sess_id, 1, "/"); // уничтожаем куки
  }
  if (isset($_COOKIE['PHPSESSID'])) {
    setcookie("PHPSESSID", $sess_id, 1, "/"); // уничтожаем куки
  }
  if (isset($_COOKIE['none_auth'])) {
    setcookie("none_auth", 1, 1, "/"); // уничтожаем куки
  }
  if (isset($_SESSION["user"])) {
    unset($_SESSION["user"]);
  }
}

//******** поиск по многомерному массиву с дополнительным параметром ********//
//проверяет наличие значения (массива значений) в заданном многомерном массиве
//если все значения присутствуют, возвращает true, если хотя бы одного нет - false
//если задан третий параметр true, то возвращает ключ совпавшего подмассива
//регистр целевого массива должен быть верхним
function in_multiarray($what, $where, $iter_num = 0) {
  $size = count($where)-1;
  if (!is_array($what)) {
    $what = array($what);
  }
  $count = count($what);
  $n = 0;
  while ($n <= $size) {
    $match = 0;
    foreach($what as $value) {
      if(@in_array(mb_convert_case($value, MB_CASE_UPPER, "UTF-8"), $where[$n])) {
        $match++;
      }
    }
    if ($count == $match) {
      if ($iter_num == true) {
        return $n;
      } else {
        return true;
      }
    }
    $n++;
  }
  return false;
}
//******** поиск по многмерному массиву с дополнительным параметром ********//

//******** сортировка массива по ключу
function sort_arrays_by_key($arr1, $arr2, $field) {
  $diff = array_diff_key($arr1, $arr2); // ищем расхождение ключей массивов
  if (count($diff) == 0) {
    $arr = array($arr1, $arr2);
    if ($arr1[$field] == $arr2[$field]) {
      $keys = array_keys($arr1);
      unset($keys[$field]);
      $field = $keys[0];
    }
    $arrTMP = array($arr1[$field], $arr2[$field]);
    asort($arrTMP, SORT_NUMERIC);
    $a = array();
    foreach ($arrTMP as $k => $v) {
      $a[] = $k;
    }
    return array($arr[$a[0]], $arr[$a[1]]);
  } else {
    return false; // отрицаем, если массивы разные
  }

}
//******** сортировка массива по ключу


//******** поиск лица в БД ********//
//если есть, возвращает его id, иначе 0
function search_men($f, $i, $o, $dr) {
  $str = array();
  if ($f != '') $str['surname'] = mb_convert_case($f, MB_CASE_UPPER, "UTF-8");
  if ($i != '') $str['name'] = mb_convert_case($i, MB_CASE_UPPER, "UTF-8");
  if ($o != '') $str['fath_name'] = mb_convert_case($o, MB_CASE_UPPER, "UTF-8");
  if ($dr != '') $str['borth'] = mb_convert_case($dr, MB_CASE_UPPER, "UTF-8");
  require('connection.php');
  $query = mysql_query('
    SELECT
      id, surname, name, fath_name, borth
    FROM
      lico
    WHERE
      surname = "'.mb_convert_case($f, MB_CASE_UPPER, "UTF-8").'" AND
      name = "'.mb_convert_case($i, MB_CASE_UPPER, "UTF-8").'" AND
      fath_name = "'.mb_convert_case($o, MB_CASE_UPPER, "UTF-8").'" AND
      borth = "'.date("Y-m-d", strtotime($dr)).'"
  ');
  if (mysql_num_rows($query)) {
    while ($result = mysql_fetch_array($query)) {
      return $result["id"];
    }
  } else {
    return 0;
  }
}
//******** поиск лица в БД ********//

//******** проверка папки на существование на FTP ********//
function ftp_is_dir($ftp_stream, $directory) {
  $is_dir = false;
  $cur_dir = ftp_pwd($ftp_stream);
  if (@ftp_chdir($ftp_stream, $directory)) {
    $is_dir = true;
    ftp_chdir($ftp_stream, $cur_dir);
  }
  return $is_dir;
}
//^^^^^^^^ проверка папки на существование на FTP ^^^^^^^^//

//******** рекурсивное создание папки на FTP ********//
function ftp_mkdir_full($ftp_stream, $directory) {
  $start_dir = ftp_pwd($ftp_stream);
  $dirArray = explode('/', $directory);
  foreach ($dirArray as $value) {
    if (!ftp_is_dir($ftp_stream, $directory)) {
      @ftp_mkdir($ftp_stream, $value);
      ftp_chdir($ftp_stream, $value);
    }
  }
  ftp_chdir($ftp_stream, $start_dir);
  return true;
}
//^^^^^^^^ рекурсивное создание папки на FTP ^^^^^^^^//

//выбор ОВД с предустановкой
function sel_ovd($name, $ovd = "") {
  require ('connection.php');
  $query = mysql_query('
    SELECT
      id_ovd, ovd
    FROM
      spr_ovd
    WHERE
      visuality = "1"
    ORDER BY
      ovd
  ');
  $option = '<select name="'.$name.'" class="ovd_name" req="true">';
  $option .= '<option value=""></option>';
  while ($result = mysql_fetch_array($query)) {
    if ($result['id_ovd'] == $ovd) {
      $option .= '<option value="'.$result['id_ovd'].'" selected>'.$result['ovd'].'</option>';
    } else {
      $option .= '<option value="'.$result['id_ovd'].'">'.$result['ovd'].'</option>';
    }
  }
  $option .= '</select>';
  return $option;
}


//выбор службы с предустановкой
function sel_slujba($slujba = 0) {
  require ('connection.php');
  $query = mysql_query("
    SELECT
      id_slujba, slujba
    FROM
      spr_slujba
    ORDER BY
      id_slujba
  ");
  $option = '<option value=""></option>';
  while ($result = mysql_fetch_array($query)) {
    if ($result['id_slujba'] == $slujba) {
      $option .= '<option value="'.$result['id_slujba'].'" selected>'.$result['slujba'].'</option>';
    } else {
      $option .= '<option value="'.$result['id_slujba'].'">'.$result['slujba'].'</option>';
    }
  }
  return $option;
}


//формирование списка служб в ссылки
function sel_slujba_link() {
  require ('connection.php');
  $query = mysql_query("
    SELECT
      id_slujba, slujba
    FROM
      spr_slujba
  ");
  $option = '<ul id="service_list">';
  while ($result = mysql_fetch_array($query)) {
    $option .= '<li class="sel_service" id="'.$result['id_slujba'].'">'.$result['slujba'].'</li>';
  }
  $option .= "</ul>";
  return $option;
}

//выбор ст.УПК с предустановкой
function sel_upk($st_upk = 0) {
  require ('connection.php');
  $query = mysql_query("
    SELECT
      st_upk
    FROM
      spr_upk
  ");
  $option = '<option value=""></option>';
  while ($result = mysql_fetch_array($query)) {
    if ($result['st_upk'] == $st_upk) {
      $option .= '<option value="'.$result['st_upk'].'" selected>'.$result['st_upk'].'</option>';
    } else {
      $option .= '<option value="'.$result['st_upk'].'">'.$result['st_upk'].'</option>';
    }
  }
  return $option;
}



//выбор ст.УК с предустановкой
function sel_uk($sel_uk = 0, $name = '') {
  $name = ($name == '') ? 'article_id' : $name;
  require_once('connection.php');
  $query = mysql_query("
    SELECT
      `id_uk`, `st`
    FROM
      `spr_uk`
    WHERE
      `visuality` = 1
    ORDER BY
      `st`
  ");
  echo '<select name="'.$name.'" id="criminal_st" class="crim_article">';
  echo '<option value=""></option>';
  while ($result = mysql_fetch_array($query)) {
    if ($result['id_uk'] == $sel_uk) {
      echo '<option value="'.$result['id_uk'].'" selected>'.$result['st'].'</option>';
    } else {
      echo '<option value="'.$result['id_uk'].'">'.$result['st'].'</option>';
    }
  }
  echo '</select>';
}


//выбор типа связи с предустановкой
function sel_relative($from, $to, $sel_relative = 0) {
  require_once('connection.php');
  $sel = '';
  $ais = (isset($_SESSION['crime']['ais'])) ? $_SESSION['crime']['ais'] : 0;
  $ret = '<select name="rel_type" class="relatives_list" req="true">';
  if (!is_numeric($from)) {
    $t1 = $from;
    $t2 = $to;
    $from = $t2;
    $to = $t1;
  }
  if (is_numeric($to)) {
    $query = mysql_query('
      SELECT
        `id`, `type`
      FROM
        `spr_relatives`
      WHERE
        `from_obj` = '.$from.' AND
        `to_obj` = '.$to.' AND
        (`ais` = '.$ais.' OR `ais` IS NULL)
      ORDER BY
        `type`
    ');
    while ($result = mysql_fetch_assoc($query)) {
      $relatives[] = $result;
    }
    if (mysql_num_rows($query) == 1) {
      $sel = ' selected';
    }
    if (mysql_num_rows($query) > 1) $ret .= '<option value=""></option>';
  } else {
    switch($to) {
      case 'request':
        $relatives = array(
          array('id' => 1, 'type' => 'в запросе'),
          array('id' => 2, 'type' => 'в ответе')
        );
        break;
    }
    $ret .= '<option value=""></option>';
  }
  
    
  foreach($relatives as $k => $v) {
    if ($v['id'] == $sel_relative) {
      $ret .= '<option value="'.$v['id'].'" selected>'.$v['type'].'</option>';
    } else {
      $ret .= '<option value="'.$v['id'].'" '.$sel.'>'.$v['type'].'</option>';
    }
  }
  $ret .= '</select>';
  return $ret;
}


//выбор объектов
function sel_objects($object, $obj_type) {
  $union = '';
  $forms_list = array(
    1 => "form_face",
    2 => "form_event",
    3 => "form_address",
    4 => "form_document",
    5 => "form_device",
    6 => "form_telephone",
    7 => "form_bank_account",
    8 => "form_bank_card",
    9 => "form_e-wallet",
    10 => "form_e-mail",
    11 => "form_detailization",
    12 => "form_messenger",
    13 => "form_nickname",
    14 => "form_drugs",
    15 => "form_requests"
  ); // нумеровал для наглядности
  if (is_numeric($obj_type)) {
    // -------- дополнительные формы для АИС -------- //
    if (in_array($obj_type, array(1, 2, 9))) {
      $union .= '
        UNION
          (SELECT
            "12", "Мессенджер", COUNT(mrel.`messenger`)
          FROM
            `l_messenger_obj_rel` as mrel
          WHERE
            mrel.`object` = '.$object.' AND
            mrel.`object_type` = '.$obj_type.' AND
            mrel.`ais` = '.$_SESSION['crime']['ais'].')
      ';
    }
    if ($obj_type == 1) {
      $union .= '
        UNION
          (SELECT
            "13", "Клички", COUNT(DISTINCT nrel.`id`)
          FROM
            `l_nickname_obj_rel` as nrel
          WHERE
            nrel.`object` = '.$object.')
      ';
    }
    if ($obj_type == 2 && $_SESSION['crime']['ais'] == 2) {
      $union .= '
        UNION
          (SELECT
            "14", "Наркотические средства", COUNT(DISTINCT wd.`id`)
          FROM
            `l_withdrawn_drug` as wd
          WHERE
            wd.`event` = '.$object.')
      ';
    }
    if ($obj_type == 2) {
      $union .= '
        UNION
          (SELECT
            "15", "Запросы/ответы", COUNT(DISTINCT req.`id`)
          FROM
            `l_requests` as req
          WHERE
            req.`event` = '.$object.')
      ';
    } elseif (in_array($obj_type, array(6, 7, 8, 9, 10))) {
      $union .= '
        UNION
          (SELECT
            "15", "Запросы/ответы", COUNT(DISTINCT req.`request`)
          FROM
            `l_request_object` as req
          WHERE
            req.`object` = '.$object.' AND
            req.`object_type` = '.$obj_type.')
      ';
    }
    // -------- дополнительные формы для АИС -------- //
    require_once(KERNEL.'connection.php');
    $query = mysql_query('
      (SELECT
        o.`id`,
        o.`object`,
        COUNT(DISTINCT rel.`id`) as `cnt`
      FROM
        `spr_objects` as o
      LEFT JOIN
        `l_relatives` as rel ON
          rel.`ais` = '.$_SESSION['crime']['ais'].' AND
          ((rel.`from_obj` = '.$object.' AND
           rel.`from_obj_type` = '.$obj_type.' AND
           rel.`to_obj_type` = o.`id`)
            OR
          (rel.`to_obj` = '.$object.' AND
           rel.`to_obj_type` = '.$obj_type.' AND
           rel.`from_obj_type` = o.`id`))
      WHERE
        o.`id` <> 11
      GROUP BY
        o.`id`
      ORDER BY
        o.`id`)'.$union
    );
    $canceled = array(2, 3); // отключенные объекты
    switch ($obj_type) {
      case 1: $canceled = array(); break;
      case 2: $canceled = array(2); break;
      case 3: $canceled = array(3, 4, 5, 6, 7, 8, 9, 10); break;
      case 4: $canceled = array(3, 5); break;
      case 5: $canceled = array(3, 4, 7, 8, 9, 10); break;
      case 6: $canceled = array(3); break;
      case 7: $canceled = array(3, 5); break;
      case 8: $canceled = array(3, 5); break;
      case 9: $canceled = array(3, 5); break;
      case 10: $canceled = array(3, 5); break;
    }
    while ($result = mysql_fetch_assoc($query)) {
      if (!in_array($result["id"], $canceled)) {
        $obj[] = $result;
      }
    }
    
    // -------- подключаем дополнительные формы к объектам -------- //
    switch ($obj_type) {
      case 6:
        $obj[] = array('id' => 11, 'object' => 'Детализации, сетевая активность', 'cnt' => 0);
        break;
    }
    // ^^^^^^^^ подключаем дополнительные формы к объектам ^^^^^^^^ //
  } else {
    switch ($obj_type) {
      case 'request':
        $query = mysql_query('
          SELECT
            o.`id`,
            o.`object`,
            COUNT(IF(rel.`object_type` = o.`id`, rel.`object`, NULL)) as `cnt`
          FROM
            `spr_objects` as o
          LEFT JOIN
            `l_request_object` as rel ON
              rel.`object_type` = o.`id` AND
              rel.`request` = '.$object.'
          WHERE
            o.`id` IN (1, 6, 7, 8, 9, 10)
          GROUP BY
            o.`id`
          ORDER BY
            o.`id`
        ');
        while ($result = mysql_fetch_assoc($query)) {
          $obj[] = $result;
        }
        break;
    }
  }
  
  
  
  $colls = 3; // количество столбцов
  $rows = ceil(count($obj)/$colls); // вычисляем количество элементов в столбец
  $i = 0;
  $resp = '';
  for ($c = 1; $c <= $colls; $c++) {
    $resp .= '<ul class="objects_list_coll">';
    for ($r = 1; $r <= $rows; $r++) {
      if ($i < count($obj)) {
        $cnt = ($obj[$i]["cnt"] > 0) ? '['.$obj[$i]["cnt"].'] ' : '[&nbsp;&nbsp;] ';
        $resp .= '<li class="objects_list_item"><a href="#" id="'.$forms_list[$obj[$i]["id"]].'">'.$cnt.$obj[$i]["object"].'</a></li>';
        $i++;
      }
    }
    $resp .= '</ul>';
  }
  return $resp;
}


// выбор типов документов
function sel_documents($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, type
    FROM
      spr_documents
    ORDER BY
      type
  ');
  echo '<select name="type" class="doc_type" req="true">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $type) {
      echo '<option value="'.$result["id"].'" selected>'.$result["type"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["type"].'</option>';
    }
  }
  echo '</select>';
}


// выбор банковских систем
function sel_bank_systems($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, system
    FROM
      spr_bank_systems
  ');
  echo '<select name="system" class="bank_system">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $type) {
      echo '<option value="'.$result["id"].'" selected>'.$result["system"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["system"].'</option>';
    }
  }
  echo '</select>';
}

// выбор типов банковских карт
function sel_card_variants($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, variant
    FROM
      spr_card_variants
  ');
  echo '<select name="variant" class="card_variant">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $type) {
      echo '<option value="'.$result["id"].'" selected>'.$result["variant"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["variant"].'</option>';
    }
  }
  echo '</select>';
}


//выбор текущего объекта
function current_object($id) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, object
    FROM
      spr_objects
    WHERE
      id = "'.$id.'"
    LIMIT 1
  ');
  while ($result = mysql_fetch_assoc($query)) {
    echo $result["object"];
  }
}


//формирование списка платежных интернет систем
function payment_systems($sys = '') {
  require_once('connection.php');
  $query = mysql_query("
    SELECT
      id, system
    FROM
      spr_payment
    ORDER BY
      system
  ");
  echo '<select name="payment_system">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $sys) {
      echo '<option value="'.$result["id"].'" selected>'.$result["system"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["system"].'</option>';
    }
  }
  echo '</select>';
}


//формирование списка способов совершения
function marking($type, $ais, $category, $sel = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      `id`, `marking`
    FROM
      `spr_marking`
    WHERE
      (`obj_type` = '.$type.' OR `obj_type` IS NULL) AND
      (`ais` = '.$ais.' OR `ais` IS NULL) AND
      `category` = '.$category.'
    ORDER BY
      `marking`
  ');
  echo '<select name="marking_id" req="true">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $sel) {
      echo '<option value="'.$result["id"].'" selected>'.$result["marking"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["marking"].'</option>';
    }
  }
  echo '</select>';
}

// окраски объекта
function markings($type, $obj = 0){
  $cur_obj_marks = array();
  require_once(KERNEL.'connection.php');
  //mysql_query('SET group_concat_max_len = 10000'); если не влазят все понятия окраски
  $query = mysql_query('
    SELECT
      mc.`category`,
      GROUP_CONCAT(
          m.`id`, "=>", m.`marking`
        SEPARATOR
          "|"
      ) as `markings`
    FROM
      `spr_marking` as m
    JOIN
      `spr_marking_categories` as mc ON
        mc.`id` = m.`category`
    WHERE
      m.`ais` = '.$_SESSION['crime']['ais'].'
    GROUP BY
      m.`category`
  ');
  while ($result = mysql_fetch_array($query)) {
    $categories[$result['category']] = $result['markings'];
  }
  if ($obj > 0) {
    mysql_free_result($query);
    $query = mysql_query('
      SELECT
        om.`marking`  
      FROM
        `l_object_marking` as om
      WHERE
        om.`obj_type` = '.$type.' AND
        om.`object` = '.$obj
    );
    while ($result = mysql_fetch_array($query)) {
      $cur_obj_marks[] = $result['marking'];
    }
  }

  mysql_free_result($query);
  $m = 0;
  foreach($categories as $category => $markings) {
    $markStr = explode('|', $markings); // имеем: 12=>Закладка|13=>Находка|14=>Из рук в руки
    foreach($markStr as $str) {  // имеем: 12=>Закладка
      $marking = explode('=>', $str);
      $marks[$m][$category][$marking[0]] = $marking[1];
    }
    $m++;
  }
  $ret = '<div class="markings_list">';
  $ret .= '<div class="list_header">Окраска</div>';
  $colls = 2;
  $m = 0;
  $rows = ceil(count($marks)/$colls); // вычисляем количество строк
  for ($i = 1; $i <= $rows; $i++) {
    $ret .= '<div class="markings_section_row">';
    for ($c = 1; $c <= $colls; $c++) {
      if ($m == count($marks)) {
        $ret .= '<div class="markings_section">';
        $ret .= '</div>';
        break;
      }
      $ret .= '<div class="markings_section">';
        $ret .= '<h4>'.key($marks[$m]).'</h4>';
        $ret .= '<ul class="marking_list">';
        foreach($marks[$m][key($marks[$m])] as $id => $mark) {
          $checked = (in_array($id, $cur_obj_marks)) ? ' checked' : '';
          $ret .= '<li><input type="hidden" name="marking['.$id.']" value="0"/><label><input type="checkbox" name="marking['.$id.']" value="1"'.$checked.'/>'.$mark.'</label></li>';
        }
        $ret .= '</ul>';
      $ret .= '</div>';
      $m++;
    }
    $ret .= '</div>';
  }
  $ret .= '<div class="markings_button_block"><button type="button" class="markings_close">Закрыть</button></div>';
  $ret .= '</div>';
  return $ret;
}

//выбор способа совершения
function marking_type($id) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, marking
    FROM
      spr_marking
    WHERE
      id = "'.$id.'"
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["marking"];
  } else {
    return false;
  }
}

//формирование списка типов телефонов
function tel_types($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, type
    FROM
      spr_tel_types
  ');
  echo '<select name="type" class="tel_type">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $type) {
      echo '<option value="'.$result["id"].'" selected>'.$result["type"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["type"].'</option>';
    }
  }
  echo '</select>';
}

//формирование списка сотовых операторов
function mob_operators($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      `id`, `operator`
    FROM
      `l_operators`
    GROUP BY
      `operator`
    ORDER BY
      `operator`
  ');
  echo '<select name="operator" class="mob_operators">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $type) {
      echo '<option value="'.$result["id"].'" selected>'.$result["operator"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["operator"].'</option>';
    }
  }
  echo '</select>';
}

//выбор региона сотового оператора
function tel_region($range) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      r.`region`
    FROM 
      `l_operators_ranges` as r 
    WHERE 
      r.`id` = '.$_GET['region']
  );
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["region"];
  } else {
    return false;
  }
}

//список месяцев
function months_list($val = '') {
  echo '<select name="month" class="months_list">';
  echo '<option></option>';
  for ($i = 1; $i <= 12; $i++) {
    ($val == $i) ? $sel = ' selected' : $sel ='';
    if ($i < 10) {
      echo '<option value="0'.$i.'"'.$sel.'>0'.$i.'</option>';
    } else {
      echo '<option value="'.$i.'"'.$sel.'>'.$i.'</option>';
    }
  }
  echo '</select>';
}


//список диапазона годов
function years_list($begin, $end, $sel = '') {
  echo '<select name="year" class="years_list">';
  echo '<option></option>';
  for($i = (date('Y') - $begin); $i <= (date('Y') + $end); $i++) {
    if ($i == $sel) {
      echo '<option value="'.$i.'" selected>'.$i.'</option>';
    } else {
      echo '<option value="'.$i.'">'.$i.'</option>';
    }
  }
  echo '</select>';
}

//формирование списка мессенджеров
function messenger_select($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      `id`, `messenger`
    FROM
      `spr_messenger`
  ');
  echo '<select name="type" class="messenger">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $type) {
      echo '<option value="'.$result["id"].'" selected>'.$result["messenger"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["messenger"].'</option>';
    }
  }
  echo '</select>';
}

//формирование типов запрососв
function request_types_select($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      `id`, `type`
    FROM
      `spr_request_types`
  ');
  echo '<select name="type" class="request_types">';
  echo '<option></option>';
  while ($result = mysql_fetch_assoc($query)) {
    if ($result["id"] == $type) {
      echo '<option value="'.$result["id"].'" selected>'.$result["type"].'</option>';
    } else {
      echo '<option value="'.$result["id"].'">'.$result["type"].'</option>';
    }
  }
  echo '</select>';
}

//формирование списка КУСП
function added_kusp() {
  $array = $_SESSION['refusal']['kusp'];
  $return = '<fieldset id="added_kusp_list"><legend><b>Связанные КУСП:</b></legend>';
  foreach ($array as $key => $value){
    $return .= '<div class="added_kusp_str" id="'.$key.'">';
    $return .= '<form method="POST" class="kusp_delete" id="'.$key.'">';
    $return .= $value["ovd"].", КУСП №".$value["kusp_num"]." от ".$value["kusp_date"];
    $return .= '<label class="del_str"><input type="image" src="#" class="del" /><span class="del_str_but"><strong>&times;</strong>Удалить</span></label>';
    $return .= '</form>';
    $return .= '</div>';
  }
  $return .= '</fieldset>';
  return $return;
}



//формирование списка статей
function added_criminal() {
  $array = $_SESSION['refusal']['uk'];
  $cnt = count($array);
  $return = '<div id="criminal_added">';
  foreach ($array as $key => $value){
    if (--$cnt !== 0) {$split = ",";} else {$split = "";}
    $return .= '<form class="criminal_added_st_form" method="POST" id="'.$key.'">';
    $return .= $value['criminal_st_str'];
    $return .= '<label class="del_str"><input type="image" src="#" class="del" /><span class="del_str_but"><strong>&times;</strong>Удалить</span></label>'.$split;
    $return .= '</form>';
  }
  $return .= '</div>';
  return $return;
}



//формирование списка лиц
function added_faces($array, $class) {
  $return = "";
  foreach ($array as $key => $value) {
    $surname = mb_convert_case($value['surname'], MB_CASE_TITLE, "UTF-8");
    $name = mb_convert_case($value['name'], MB_CASE_TITLE, "UTF-8");
    $fath_name = mb_convert_case($value['fath_name'], MB_CASE_TITLE, "UTF-8");
    $return .= '<form class="'.$class.'" method="POST" id="'.$key.'">';
    $return .= $value['relative'].' - '.$surname.' '.$name.' '.$fath_name.' '.$value['borth'].' г.р.';
    $return .= '<label class="del_str"><input type="image" src="#" class="del"/><span class="del_str_but"><strong>&times;</strong>Удалить</span></label>';
    $return .= '</form>';
  }
  return $return;
}


//формирование списка организаций
function added_organisations($array, $class) {
  $return = '';
  foreach ($array as $key => $value) {
    $return .= '<form class="'.$class.'" method="POST" id="'.$key.'">';
    $return .= 'Организация ('.$value['relative'].') - '.mb_convert_case($value['org_name'], MB_CASE_UPPER, "UTF-8");
    $return .= '<label class="del_str"><input type="image" src="#" class="del" /><span class="del_str_but"><strong>&times;</strong>Удалить</span></label>';
    $return .= '</form>';
  }
  return $return;
}

//-------- формирование имени отказного --------//
function refusal_new_file_name($year_otkaz, $reg) {
  $file_type = explode(".", $_SESSION['refusal']['uploaded_file']);
  $file_type = end($file_type); // расширение
  //******** формируем список КУСП для имени файла ********//
    $kusp_list = '';
    $array = $_SESSION['refusal']['kusp'];
    foreach ($array as $key => $value) {
      $kusp_list .= '_'.$array[$key]['kusp_num'];
    }
  //******** формируем список КУСП для имени файла ********//
  return "КУСП_".$year_otkaz.$kusp_list.'_('.$reg.').'.$file_type; //имя нового файла
}
//^^^^^^^^ формирование имени отказного ^^^^^^^^//

//-------- ввод отказного --------//
function insert_otkaz($bp, $year_otkaz, $anonymous, $declEmp) {
  require('connection.php');
  $error = array();
  $reg_num_query = mysql_query('
    SELECT
      MAX(reg) as id
    FROM
      otkaz as o
    WHERE
      year(o.data_resh) = "'.$year_otkaz.'"
  ');
  while($reg_num = mysql_fetch_array($reg_num_query)) {
    if (empty($reg_num["id"])) {
      $reg = 1;
    } else {
      $reg = $reg_num["id"]+1;
    }
  }
  $file_new_name = refusal_new_file_name($year_otkaz, $reg);
  mysql_query('
  INSERT INTO
    otkaz(reg, id_ovd, id_slujba,
      sotr_f, sotr_i, sotr_o, upk, data_resh,
      status, bp, anonymous, declarer_employer, file_original, file_final,
      create_date, create_time, active_id)
  VALUES("'.$reg.'",
    "'.$_SESSION["refusal"]["ovd"].'",
    "'.$_SESSION['refusal']['slujba'].'",
    "'.mysql_real_escape_string(mb_convert_case($_SESSION['refusal']['surname'], MB_CASE_UPPER, "UTF-8")).'",
    "'.mysql_real_escape_string(mb_convert_case($_SESSION['refusal']['name'], MB_CASE_UPPER, "UTF-8")).'",
    "'.mysql_real_escape_string(mb_convert_case($_SESSION['refusal']['father_name'], MB_CASE_UPPER, "UTF-8")).'",
    "'.$_SESSION['refusal']['upk'].'",
    "'.date("Y-m-d", strtotime($_SESSION['refusal']['otk_date'])).'",
    "'.$_SESSION['refusal']['status'].'",
    "'.$bp.'",
    "'.$anonymous.'",
    "'.$declEmp.'",
    "'.mysql_real_escape_string($_SESSION['refusal']['uploaded_file']).'",
    "'.$file_new_name.'",
    current_date, current_time, "'.$_SESSION['activity_id'].'"
    )
  ') or $error[] = mysql_error(); // добавляем отказной в базу или ошибка
  $id = mysql_insert_id();
  if(!$error) {
    return array($id, $reg, $file_new_name); // id записи, порядковый номер отказного (ежегодная нумерация с 1), новое имя файла
  } else {
    return false;
    //print_r($error);
  }
}
//^^^^^^^^ ввод отказного ^^^^^^^^//

//-------- редактирование отказного --------//
function update_otkaz($id, $reg) {
  require('connection.php');
  $error = "";
  mysql_query('
    UPDATE
      otkaz
    SET
      id_ovd = "'.$_SESSION["refusal"]["ovd"].'",
      id_slujba = "'.$_SESSION['refusal']['slujba'].'",
      sotr_f = "'.mysql_real_escape_string(mb_convert_case($_SESSION['refusal']['surname'], MB_CASE_UPPER, "UTF-8")).'",
      sotr_i = "'.mysql_real_escape_string(mb_convert_case($_SESSION['refusal']['name'], MB_CASE_UPPER, "UTF-8")).'",
      sotr_o = "'.mysql_real_escape_string(mb_convert_case($_SESSION['refusal']['father_name'], MB_CASE_UPPER, "UTF-8")).'",
      upk = "'.$_SESSION['refusal']['upk'].'",
      data_resh = "'.date("Y-m-d", strtotime($_SESSION['refusal']['otk_date'])).'",
      status = "'.$_SESSION['refusal']['status'].'",
      bp = "'.(isset($_SESSION['refusal']['bp']) ? 1 : 0).'",
      anonymous = "'.(isset($_SESSION['refusal']['anonymous']) ? 1 : 0).'",
      declarer_employer = "'.(isset($_SESSION['refusal']['decl_emp']) ? 1 : 0).'",
      file_final = "'.refusal_new_file_name(date("Y", strtotime($_SESSION['refusal']['otk_date'])), $reg).'",
      update_date = current_date, update_time = current_time, update_active_id = "'.$_SESSION['activity_id'].'"
    WHERE
      id = "'.$id.'"
  ') or $error = mysql_error();
  if(!$error) {
    return true;
  } else {
    return $error;
  }
}
//^^^^^^^^ редактирование отказного ^^^^^^^^//


//-------- ввод лица --------//
function add_face($s, $n, $fn, $borth) {
  require_once('connection.php');
  $activity_id = 0;
  if (isset($_SESSION['activity_id'])) {
    $activity_id = $_SESSION['activity_id'];
  }
  $query = '
    INSERT INTO
      lico (surname, name, fath_name, borth, create_date, create_time, active_id)
    VALUES (
      "'.mysql_real_escape_string(mb_convert_case($s, MB_CASE_UPPER, "UTF-8")).'",
      "'.mysql_real_escape_string(mb_convert_case($n, MB_CASE_UPPER, "UTF-8")).'",
      "'.mysql_real_escape_string(mb_convert_case($fn, MB_CASE_UPPER, "UTF-8")).'",
      "'.date('Y-m-d', strtotime($borth)).'",
      current_date, current_time, "'.$activity_id.'")';
  if (mysql_query($query)) {
    return mysql_insert_id();
  } else {
    return 'Face insert error: '.mysql_error();
  }
}
//-------- ввод лица --------//

//-------- ввод связи с лицом --------//
function add_face_rel($type, $refId, $faceId) {
  require_once('connection.php');
  $query = '
    INSERT INTO
      relatives (type, id_otkaz, id_lico, create_date, create_time, active_id)
    VALUES (
      "'.$type.'",
      "'.$refId.'",
      "'.$faceId.'",
      current_date, current_time, "'.$_SESSION['activity_id'].'"
    )';
  if (mysql_query($query)) {
    return mysql_insert_id();
  } else {
    return 'Face relative insert error: '.mysql_error();
  }
}
//-------- ввод связи с лицом --------//

//-------- ввод связи с лицом --------//
function del_face_rel($type, $refId, $faceId) {
  require_once('connection.php');
  $query = '
    DELETE FROM
      relatives
    WHERE
      `type` = "'.$type.'" AND
      `id_otkaz` = "'.$refId.'" AND
      `id_lico` = "'.$faceId.'"';
  if (mysql_query($query)) {
    return true;
  } else {
    return 'Face relative delete error: '.mysql_error();
  }
}
//-------- ввод связи с лицом --------//

//-------- ввод организации --------//
function add_org($org) {
  require_once('connection.php');
  $query = '
    INSERT INTO
      organisations (org_name, create_date, create_time, active_id)
    VALUES ("'.mysql_real_escape_string(mb_convert_case($org, MB_CASE_UPPER, "UTF-8")).'",
      current_date, current_time, "'.$_SESSION['activity_id'].'")
  ';
  if (mysql_query($query)) {
    return mysql_insert_id();
  } else {
    return 'Organisation insert error: '.mysql_error();
  }
}
//-------- ввод организации --------//

//-------- ввод связи с организацией --------//
function add_org_rel($type, $refId, $orgId) {
  require_once('connection.php');
  $query = '
    INSERT INTO
      relatives (type, id_otkaz, id_org, create_date, create_time, active_id)
    VALUES ("'.$type.'", "'.$refId.'", "'.$orgId.'", current_date, current_time, "'.$_SESSION['activity_id'].'")';
  if (mysql_query($query)) {
    return mysql_insert_id();
  } else {
    return 'Organisation relative insert error: '.mysql_error();
  }
}
//-------- ввод связи с организацией --------//

//-------- проверка наличия связи от/к объектов --------//
function check_relation($from_obj, $from_obj_type, $to_obj, $to_obj_type, $type, $ais = 1) {
  require_once('connection.php');
  if ($from_obj_type == $to_obj_type) {
    $query = mysql_query('
      SELECT
        `id`
      FROM
        `l_relatives`
      WHERE
        ((`from_obj` = '.$from_obj.' AND `from_obj_type` = '.$from_obj_type.' AND `to_obj` = '.$to_obj.' AND `to_obj_type` = '.$to_obj_type.') OR
         (`from_obj` = '.$to_obj.' AND `from_obj_type` = '.$to_obj_type.' AND `to_obj` = '.$from_obj.' AND `to_obj_type` = '.$from_obj_type.')
        ) AND
        `type` = '.$type.' AND
        `ais` = '.$ais
    );
  } else {
    $query = mysql_query('
      SELECT
        `id`
      FROM
        `l_relatives`
      WHERE
        `from_obj` = '.$from_obj.' AND 
        `from_obj_type` = '.$from_obj_type.' AND 
        `to_obj` = '.$to_obj.' AND 
        `to_obj_type` = '.$to_obj_type.' AND 
        `type` = '.$type.' AND
        `ais` = '.$ais
    );
  }
  if (mysql_num_rows($query) > 0) {
    return true;
  } else {
    return false;
  }
}
//-------- проверка наличия связи от/к объектов --------//

//-------- ввод связи от/к объектов --------//
function add_relation($from_obj, $from_obj_type, $to_obj, $to_obj_type, $type, $ais = 1) {
  $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
  require_once('connection.php');
  mysql_query('
    INSERT INTO
      l_relatives(`from_obj`, `from_obj_type`, `to_obj`, `to_obj_type`, `type`, `ais`,
        create_date, create_time, active_id)
    VALUES
      ("'.$from_obj.'", "'.$from_obj_type.'", "'.$to_obj.'", "'.$to_obj_type.'", "'.$type.'", "'.$ais.'", current_date, current_time, "'.$activity_id.'")
  ');
}
//-------- ввод связи от/к объектов --------//


//-------- ввод объекта --------//
function add_object($table) {
  $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
  $dated_tables = array(
    'l_messenger_obj_rel',
    'l_nicknames',
    'l_nickname_obj_rel',
    'l_withdrawn_drug'
  );
  if ((substr($table, 0, 2) == 'o_') || in_array($table, $dated_tables)) {
    $query = '
      INSERT INTO
        '.$table.'(create_date, create_time, active_id)
      VALUES (current_date, current_time, "'.$activity_id.'")';
  } else {
    $query = '
      INSERT INTO
        '.$table.'
      VALUES ()';
  }
  require_once('connection.php');
  if (mysql_query($query)) {
    return mysql_insert_id();
  } else {
    return $table.' insert error: '.mysql_error();
  }
}
//-------- ввод объекта --------//

//-------- обновление объекта --------//
function update_object($table, $id, $array){
  $tmp = $array;
  $error = $upd = '';
  $activity_id = 0;
  $items = array();
  $upper = array('surname', 'name', 'fath_name');
  
  require_once('connection.php');
  $query = mysql_query('DESCRIBE `'.$table.'`') or die(mysql_error());
  while ($result = mysql_fetch_assoc($query)) {
    if ($result['Field'] != 'id') $fields[] = strtolower($result['Field']); // создаем массив полей таблицы за исключением `id`
  }

  foreach ($array as $k => $v) {
    if ($k == 'id') {
      unset($tmp['id']);
      continue;
    }
    if (in_array(strtolower($k), $fields)) {
      if ((strpos($k, 'date') !== false) || strpos($k, 'borth') !== false) {
        if ($v != '') $v = date('Y-m-d', strtotime($v));
      }
      if ((strpos($k, 'number') !== false) || (strpos($k, 'telephone') !== false)) {
        if ($v != '') $v = preg_replace('/\D/', '', $v);
      }
      if (in_array($k, $upper)) {
        if ($v != '') $v = mb_convert_case($v, MB_CASE_UPPER, "UTF-8");
      }
      $v == '' ? $v = 'NULL' : $v = '"'.mysql_real_escape_string($v).'"';
      $items[] = '`'.$k.'` = '.$v;
    }
  }
  $query = '
    UPDATE
      '.$table.'
    SET
      '.implode(', ', $items).'
      '.$upd.'
    WHERE
      id = '.$id;
  
  $dated_tables = array(
    'locality_passport'
  );
  
  if ((substr($table, 0, 2) == 'o_') || in_array($table, $dated_tables)) {
    if (isset($_SESSION['activity_id'])) {
      $activity_id = $_SESSION['activity_id'];
    }
    $upd = ', `update_date` = current_date, `update_time` = current_time, `update_active_id` = '.$activity_id;
  }
  mysql_query($query) or $error = 'Ошибка обновления таблицы `'.$table.'` (объект id = '.$id.'): '.mysql_error();
  if ($error != '') return $error;
}
//-------- обновление объекта --------//

//-------- поиск ID записи в таблице --------//
function search_id($table, $par, $comp = '=') {
  if (is_array($par)) { // проверяем массив ли передан в качестве параметров
    foreach($par as $k => $v) {
      if ($comp == '=') {
        $items[] = 'UPPER(`'.$k.'`) = UPPER("'.$v.'")';
      } else {
        $items[] = '`'.$k.'` LIKE "%'.$v.'%"';
      }
      
    }
    require_once('connection.php');
    $query = mysql_query('
      SELECT
        `id`
      FROM
        `'.$table.'`
      WHERE
        '.implode(' AND ', $items).'
      LIMIT 1
    ');
    if (mysql_num_rows($query) > 0) {
      $result = mysql_fetch_assoc($query);
      return $result['id'];
    } else {
      return false;
    }
  } else {
    return false;
  }
}
//-------- поиск ID записи в таблице --------//

//-------- ввод приостановок --------//
function add_suspension($id, $date) {
  require_once('connection.php');
  $activity_id = 0;
  if (isset($_SESSION['activity_id'])) {
    $activity_id = $_SESSION['activity_id'];
  }
  mysql_query('
    INSERT INTO
      l_suspension(`event_id`, `date`, `create_date`, `create_time`, `active_id`)
    VALUES("'.$id.'", "'.date('Y-m-d', strtotime($date)).'", current_date, current_time, "'.$activity_id.'")
  ') or die(mysql_error());
}
//-------- ввод приостановок --------//

//-------- ввод возобновления приостановок --------//
function suspension_resume($id, $date, $date_res) {
  require_once('connection.php');
  mysql_query('
    UPDATE
      l_suspension
    SET
      resume_date = "'.date('Y-m-d', strtotime($date_res)).'"
    WHERE
      event_id = "'.$id.'" AND
      date = "'.date('Y-m-d', strtotime($date)).'"
  ');
}
//-------- ввод возобновления приостановок --------//

//-------- поиск событий --------//
function search_event($kusp, $date, $ovd) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id
    FROM
      o_event
    WHERE
      `kusp_num` = "'.$kusp.'" AND
      `kusp_date` = "'.date('Y-m-d', strtotime($date)).'" AND
      `ovd_id` = "'.$ovd.'"
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск событий --------//

//-------- поиск адреса --------//
function search_address($address) {
  $tmp = $address;
  require_once('connection.php');
  $query = mysql_query('SHOW COLUMNS FROM `o_address`'); // получаем список полей таблицы
  while ($result = mysql_fetch_assoc($query)) {
    $fields[] = $result['Field']; // собираем их в один массив
  }
  mysql_free_result($query);
  foreach ($tmp as $k => $v) {
    if (!in_array($k, $fields)) { // если имя элемента входящего массива не в списке полей таблицы
      unset($tmp[$k]); // удаляем его из массива
    }
  }
  foreach($tmp as $k => $v) { // собираем строку запроса
    if ($k == 'id') {
      unset($tmp['id']);
      continue;
    }
    if ($v == '') {
      $items[] = '`'.$k.'` IS NULL';
    } else {
      $items[] = '`'.$k.'` = "'.$v.'"';
    }
  }
  $query = mysql_query('
    SELECT
      `id`
    FROM
      `o_address`
    WHERE
      '.implode(' AND ', $items).'
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск адреса --------//

//-------- поиск документов --------//
function search_document($serial, $num, $type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id
    FROM
      o_documents
    WHERE
      `type` = "'.$type.'" AND
      `serial` = "'.$serial.'" AND
      `number` = "'.$num.'"
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск документов --------//

//-------- поиск устройств --------//
function search_device($IMEI) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, IMEI
    FROM
      o_mobile_device
    WHERE
      `IMEI` = "'.$IMEI.'"
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск устройств --------//

//-------- поиск телефонных номеров --------//
function search_telephone($num, $eq = 'LIKE') {
  $num = preg_replace('/[^\d]+/', '', $num);
  switch ($eq) {
    case '=':
      $str = '= "'.$num.'"';
      break;
    default:
      $str = 'LIKE "'.$num.'%"';
      break;
  }
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, `number`
    FROM
      o_telephone
    WHERE
      `number` '.$str
  ) or die(mysql_error());
  if (mysql_num_rows($query) > 0) {
    $resp = array();
    while($result = mysql_fetch_assoc($query)) {
      $resp[] = array(
        'id' => $result["id"],
        'number' => $result["number"]
      );
    }
    return $resp;
  } else {
    return false;
  }
}
//-------- поиск телефонных номеров --------//

//-------- определение сотового оператора --------//
function detect_mob_operator($num) {
  $num = str_replace(array('(', ')', '-', '_'), '', $num);
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      o.`id`, o.`operator`, r.`region`,
      r.`id` as operator_range
    FROM
      `l_operators_ranges` as r
    left join
      `l_operators` as o ON
        o.`id` = r.`operator`
    WHERE
      '.$num.' BETWEEN r.`range_from` AND r.`range_to`
    LIMIT 1
  ') or die(mysql_error());
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result;
  } else {
    return false;
  }
}
//-------- определение сотового оператора --------//

//-------- поиск банковских счетов --------//
function search_bank_account($num) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, `number`
    FROM
      o_bank_account
    WHERE
      `number` = "'.$num.'"
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск банковских счетов --------//

//-------- поиск банковской карты --------//
function search_bank_card($num) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, `number`
    FROM
      o_bank_card
    WHERE
      `number` = "'.$num.'"
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск банковской карты --------//

//-------- поиск электронного кошелька --------//
function search_wallet($num) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, `number`
    FROM
      o_wallet
    WHERE
      `number` = "'.$num.'"
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск электронного кошелька --------//

//-------- поиск электронного ящика --------//
function search_mail($name) {
  require_once('connection.php');
  $name = mb_convert_case($name, MB_CASE_UPPER, "UTF-8");
  $query = mysql_query('
    SELECT
      id, `name`
    FROM
      o_mail
    WHERE
      `name` = "'.$name.'"
    LIMIT 1
  ');
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result["id"];
  } else {
    return false;
  }
}
//-------- поиск электронного ящика --------//

//-------- список связанных лиц --------//
function related_faces($obj, $obj_type) {
  require_once('connection.php');
  if (is_numeric($obj_type)) {
    $query = mysql_query('
      SELECT
        l.`id`, l.`surname`, l.`name`, l.`fath_name`,
        DATE_FORMAT(l.`borth`, "%d.%m.%Y") as `borth`,
        sr.`type`
      FROM
        `l_relatives` as r
      LEFT JOIN
        `o_lico` as l ON
          (CASE
            WHEN ('.$obj_type.' > 1) THEN r.`from_obj`
            WHEN ('.$obj_type.' = 1) THEN
              IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
          END) = l.`id`
      LEFT JOIN
        `spr_relatives` as sr ON
          r.`type` = sr.`id`
      WHERE
        r.`ais` = '.$_SESSION['crime']['ais'].' AND 
        ((r.`to_obj` = '.$obj.' AND
         r.`to_obj_type` = '.$obj_type.' AND
         r.`from_obj_type` = 1)
           OR
        (r.`from_obj` = '.$obj.' AND
         r.`from_obj_type` = '.$obj_type.' AND
         r.`to_obj_type` = 1))
      ORDER BY
        sr.`id`, l.`surname`, l.`name`, l.`fath_name`
    ') or die(mysql_error());
  } else {
    switch($obj_type) {
      case 'request':
        $query = mysql_query('
          SELECT
            CASE
              WHEN r.`relation` = 1 THEN "в запросе"
              WHEN r.`relation` = 2 THEN "в ответе"
            END as `type`,
            l.`id`, l.`surname`, l.`name`, l.`fath_name`,
            DATE_FORMAT(l.`borth`, "%d.%m.%Y") as `borth`
          FROM
            `l_request_object` as r
          JOIN
            `o_lico` as l ON
              l.`id` = r.`object` AND
              r.`object_type` = 1
          ORDER BY
            `type`, l.`surname`, l.`name`, l.`fath_name`
        ') or die(mysql_error());
        break;
    }
  }
  
  $ret = '';
  $ret .= '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      ($result["borth"] != '') ? $borth = $result["borth"].' г.р.' : $borth = '';
      $ret .= '<li>'.$result["type"].' &ndash; <a href="face.php?face_id='.$result["id"].'">'.mb_convert_case($result["surname"].' '.$result["name"].' '.$result["fath_name"], MB_CASE_TITLE, "UTF-8").' '.$borth.'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных лиц --------//

//-------- список связанных событий --------//
function related_events($obj, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      e.`id`, o.`ovd`, e.`kusp_num`,
      DATE_FORMAT(e.`kusp_date`, "%d.%m.%Y") as `kusp_date`,
      e.`decision`, e.`decision_number`,
      DATE_FORMAT(s.`date`, "%d.%m.%Y") as `date`,
      sr.`type`
    FROM
      `l_relatives` as r
    LEFT JOIN
      `o_event` as e ON
        (CASE
          WHEN ('.$obj_type.' < 2) THEN r.`to_obj`
          WHEN ('.$obj_type.' > 2) THEN r.`from_obj`
          WHEN ('.$obj_type.' = 2) THEN
            IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
        END) = e.`id`
    LEFT JOIN
      `spr_relatives` as sr ON
        r.`type` = sr.id
    LEFT JOIN
      `spr_ovd` as o ON
        e.`ovd_id` = o.`id_ovd`
    LEFT JOIN
      `l_suspension` as s ON
        e.`id` = s.`event_id` AND
        s.`resume_date` IS NULL
    WHERE
      r.`ais` = '.$_SESSION['crime']['ais'].' AND 
      ((r.`to_obj` = '.$obj.' AND
       r.`to_obj_type` = '.$obj_type.' AND
       r.`from_obj_type` = 2)
         OR
      (r.`from_obj` = '.$obj.' AND
       r.`from_obj_type` = '.$obj_type.' AND
       r.`to_obj_type` = 2))
    GROUP BY
      e.`id`
    ORDER BY
      sr.`id`, e.`ovd_id`, e.`kusp_date`, e.`kusp_num`
  ') or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $str = array();
      if ($result["ovd"] != "") $str[] = $result["ovd"];
      if ($result["kusp_num"] != "") $str[] = ' КУСП № '.$result["kusp_num"].' от '.$result["kusp_date"];
      $decision = ($result['decision'] == 1) ? 'отказ.в ВУД № ' : ' у/д № ';
      if ($result["decision_number"] != "") $str[] = $decision.$result["decision_number"];
      if ($result["date"] != "") $str[] = ' (приостановлено '.$result["date"].')';
      $ret .= '<li>'.$result["type"].' &ndash; <a href="event.php?event_id='.$result["id"].'">'.implode(', ', $str).'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных событий --------//

//-------- список связанных адресов --------//
function related_address($obj, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      sr.`type` as `rel_type`, a.id,
      CONCAT(
        RTRIM(reg.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = reg.`socr` AND `level` = 1)
      ) as `region`,
      CONCAT(
        RTRIM(dist.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = dist.`socr` AND `level` = 2)
      ) as `district`,
      CONCAT(
        RTRIM(city.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = city.`socr` AND `level` = 3)
      ) as `city`,
      CONCAT(
        RTRIM(loc.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = loc.`socr` AND `level` = 4)
      ) as `locality`,
      CONCAT(
        RTRIM(str.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = str.`socr` AND `level` = 5)
      ) as `street`,
      CONCAT("д.", IF(a.`house_lit` IS NOT NULL, CONCAT(a.`house`,"/",a.`house_lit`), a.`house`)) as `house`,
      CONCAT("кв.", IF(a.`flat_lit` IS NOT NULL, CONCAT(a.`flat`,"/",a.`flat_lit`), a.`flat`)) as `flat`
    FROM
      `l_relatives` as r
    LEFT JOIN
      `o_address` as a ON
        (CASE
          WHEN ('.$obj_type.' < 3) THEN r.`to_obj`
          WHEN ('.$obj_type.' > 3) THEN r.`from_obj`
          WHEN ('.$obj_type.' = 3) THEN
            IF(r.to_obj = '.$obj.', r.`from_obj`, r.`to_obj`)
        END) = a.`id`
    LEFT JOIN
      `spr_relatives` as sr ON
        sr.`id` = r.`type`
    LEFT JOIN
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
      r.`ais` = '.$_SESSION['crime']['ais'].' AND 
      ((r.`to_obj` = '.$obj.' AND
       r.`to_obj_type` = '.$obj_type.' AND
       r.`from_obj_type` = 3)
         OR
      (r.`from_obj` = '.$obj.' AND
       r.`from_obj_type` = '.$obj_type.' AND
       r.`to_obj_type` = 3))
    ORDER BY
      sr.`id`, a.`region`, a.`district`, a.`city`, a.`locality`, a.`street`, a.`house`, a.`house_lit`, a.`flat`, a.`flat_lit`
  ') or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $address = array();
      foreach($result as $f => $v) {
        if (in_array($f, array('region', 'district', 'city', 'locality', 'street', 'house', 'flat'))) {
          if ($v != '') $address[] = $v;
        }
      }
      $ret .= '<li>'.$result["rel_type"].' &ndash; <a href="address.php?addr_id='.$result["id"].'">'.implode(', ', $address).'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных адресов --------//

//-------- список связанных документов --------//
function related_documents($obj, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      d.`id`,
      sr.`type` as rel_type,
      sd.`type` as doc_type,
      d.`serial`, d.`number`
    FROM
      `l_relatives` as r
    LEFT JOIN
      `o_documents` as d ON
        (CASE
          WHEN ('.$obj_type.' < 4) THEN r.`to_obj`
          WHEN ('.$obj_type.' > 4) THEN r.`from_obj`
          WHEN ('.$obj_type.' = 4) THEN
            IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
        END) = d.`id`
    LEFT JOIN
      `spr_relatives` as sr ON
        sr.`id` = r.`type`
    LEFT JOIN
      `spr_documents` as sd ON
        sd.`id` = d.`type`
    WHERE
      r.`ais` = '.$_SESSION['crime']['ais'].' AND 
      ((r.`to_obj` = '.$obj.' AND
       r.`to_obj_type` = '.$obj_type.' AND
       r.`from_obj_type` = 4)
         OR
      (r.`from_obj` = '.$obj.' AND
       r.`from_obj_type` = '.$obj_type.' AND
       r.`to_obj_type` = 4))
    ORDER BY
      sr.`id`, sd.`type`, d.`serial`, d.`number`
  ') or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["rel_type"].' &ndash; <a href="document.php?doc_id='.$result["id"].'">'.$result["doc_type"].': '.$result["serial"].'-'.$result["number"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных документов --------//

//-------- список связанных мобильных устройств --------//
function related_mobile_devices($obj, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      m.`id`,
      m.`IMEI`,
      sr.`type` as rel_type
    FROM
      `l_relatives` as r
    LEFT JOIN
      `o_mobile_device` as m ON
        m.`id` = r.`to_obj` OR
        m.`id` = r.`from_obj`
    LEFT JOIN
      `spr_relatives` as sr ON
        sr.`id` = r.`type`
    WHERE
      r.`ais` = '.$_SESSION['crime']['ais'].' AND 
      ((r.`to_obj` = '.$obj.' AND
       r.`to_obj_type` = '.$obj_type.' AND
       r.`from_obj_type` = 5)
         OR
      (r.`from_obj` = '.$obj.' AND
       r.`from_obj_type` = '.$obj_type.' AND
       r.`to_obj_type` = 5))
    ORDER BY
      sr.`id`, m.`IMEI`
  ') or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["rel_type"].' &ndash; <a href="device.php?dev_id='.$result["id"].'">'.$result["IMEI"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных мобильных устройств --------//

//-------- список связанных телефонов --------//
function related_telephones($obj, $obj_type) {
  require_once('connection.php');
  if (is_numeric($obj_type)) {
    $query = mysql_query('
      SELECT
        sr.`type`, t.`id`, t.`number`
      FROM
        `l_relatives` as r
      JOIN
        `o_telephone` as t ON
          t.`id` = (
            CASE
              WHEN ('.$obj_type.' < 6) THEN r.`to_obj`
              WHEN ('.$obj_type.' > 6) THEN r.`from_obj`
              WHEN ('.$obj_type.' = 6) THEN
                IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
            END)
      JOIN
        `spr_relatives` as sr ON
          sr.`id` = r.`type`
      WHERE
        r.`ais` = '.$_SESSION['crime']['ais'].' AND 
        ((r.`to_obj` = '.$obj.' AND
         r.`to_obj_type` = '.$obj_type.' AND
         r.`from_obj_type` = 6)
           OR
        (r.`from_obj` = '.$obj.' AND
         r.`from_obj_type` = '.$obj_type.' AND
         r.`to_obj_type` = 6))
      ORDER BY
        sr.`id`, t.`number`
    ') or die(mysql_error());
  } else {
    switch($obj_type) {
      case 'request':
        $query = mysql_query('
          SELECT
            CASE
              WHEN r.`relation` = 1 THEN "в запросе"
              WHEN r.`relation` = 2 THEN "в ответе"
            END as `type`,
            t.`id`, t.`number`
          FROM
            `l_request_object` as r
          JOIN
            `o_telephone` as t ON
              t.`id` = r.`object` AND
              r.`object_type` = 6
          ORDER BY
            `type`, t.`number`
        ') or die(mysql_error());
        break;
    }
  }
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="telephone.php?tel_id='.$result["id"].'">'.$result["number"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных телефонов --------//

//-------- список связанных IMSI --------//
function related_imsi($tel_id) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      im.`IMSI`, COUNT(im.`telephone`) as `imsi_cnt`
    FROM
      `l_imsi` as im
    WHERE
      im.`IMSI` IN (SELECT `IMSI` FROM `l_imsi` WHERE telephone = '.$tel_id.')
    GROUP BY
      im.`IMSI`
  ');
  $imsi = array();
  if (mysql_num_rows($query)) {
    while($result = mysql_fetch_assoc($query)) {
      $imsi[] = $result;
    }
    $return = 'IMSI: ';
    for($k = 0; $k < count($imsi); $k++) {
      ($k < count($imsi) - 1) ? $sep = ', ' : $sep = '';
      if ($imsi[$k]['imsi_cnt'] > 1) {
        $return .= '<a href="telephones_list.php?imsi='.$imsi[$k]['IMSI'].'">'.$imsi[$k]['IMSI'].'</a>'.$sep;
      } else {
        $return .= $imsi[$k]['IMSI'].$sep;
      }
    }
    return $return;
  }
}
//-------- список связанных IMSI --------//

//-------- количество телефонных соединений --------//
function connection_indicators($tel) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      t.`number`,
      COUNT(DISTINCT d_t.`id`) as `total`,
      COUNT(DISTINCT d_in.`id`) as `incoming`,
      COUNT(DISTINCT d_out.`id`) as `outgoing`
    FROM
      `o_telephone` as t
    LEFT JOIN
      `l_detailizations` as d_t ON
        d_t.`subscriber_1` = t.`id` OR
        d_t.`subscriber_2` = t.`id`
    LEFT JOIN
      `l_detailizations` as d_in ON
        d_in.`subscriber_2` = t.`id`
    LEFT JOIN
      `l_detailizations` as d_out ON
        d_out.`subscriber_1` = t.`id`
    WHERE
      t.`id` = '.$tel.'
    GROUP BY
      t.`number`
  ') or die(mysql_error());
  $indic = mysql_fetch_assoc($query);
  $ret = '<legend>Сетевая активность:</legend>';
  $ret .= '<table width="100%" rules="none" border="0">';
  $ret .= '<tr>';
  $ret .= '<td width="140px">Всего соединений:</td>';
  if ($indic['total']) {
    $ret .= '<td><a href="detailization.php?tel_id='.$tel.'&direction=all">'.$indic['total'].'</a></td>';
  } else {
    $ret .= '<td>'.$indic['total'].'</td>';
  }
  $ret .= '</tr>';
  $ret .= '<tr>';
  $ret .= '<td>Исходящих звонков:</td>';
  if ($indic['outgoing']) {
    $ret .= '<td><a href="detailization.php?tel_id='.$tel.'&direction=outgoing">'.$indic['outgoing'].'</a></td>';
  } else {
    $ret .= '<td>'.$indic['outgoing'].'</td>';
  }
  $ret .= '</tr>';
  $ret .= '<tr>';
  $ret .= '<td>Входящих звонков:</td>';
  if ($indic['incoming']) {
    $ret .= '<td><a href="detailization.php?tel_id='.$tel.'&direction=incoming">'.$indic['incoming'].'</a></td>';
  } else {
    $ret .= '<td>'.$indic['incoming'].'</td>';
  }
  $ret .= '</tr>';
  $ret .= '</table>';
  return $ret;
}
//-------- количество телефонных соединений --------//

//-------- список связанных банковских счетов --------//
function related_bank_accounts($obj, $obj_type) {
  require_once('connection.php');
  if (is_numeric($obj_type)) {
    $query = mysql_query('
      SELECT
        b.`id`, b.`number`, sr.`type`
      FROM
        `l_relatives` as r
      LEFT JOIN
        `spr_relatives` as sr ON
          sr.`id` = r.`type`
      LEFT JOIN
        `o_bank_account` as b ON
          b.`id` = (
            CASE
              WHEN ('.$obj_type.' < 7) THEN r.`to_obj`
              WHEN ('.$obj_type.' > 7) THEN r.`from_obj`
              WHEN ('.$obj_type.' = 7) THEN
                IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
            END
          )
      WHERE
        r.`ais` = '.$_SESSION['crime']['ais'].' AND 
        ((r.`to_obj` = '.$obj.' AND
         r.`to_obj_type` = '.$obj_type.' AND
         r.`from_obj_type` = 7)
           OR
        (r.`from_obj` = '.$obj.' AND
         r.`from_obj_type` = '.$obj_type.' AND
         r.`to_obj_type` = 7))
      ORDER BY
        sr.`id`, b.`number`
    ') or die(mysql_error());
  } else {
    switch($obj_type) {
      case 'request':
        $query = mysql_query('
          SELECT
            CASE
              WHEN r.`relation` = 1 THEN "в запросе"
              WHEN r.`relation` = 2 THEN "в ответе"
            END as `type`,
            b.`id`, b.`number`
          FROM
            `l_request_object` as r
          JOIN
            `o_bank_account` as b ON
              b.`id` = r.`object` AND
              r.`object_type` = 7
          ORDER BY
            `type`, b.`number`
        ') or die(mysql_error());
        break;
    }
  }
  
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="bank_account.php?acc_id='.$result["id"].'">'.$result["number"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных банковских счетов --------//

//-------- список связанных банковских карт --------//
function related_bank_cards($obj, $obj_type) {
  require_once('connection.php');
  if (is_numeric($obj_type)) {
    $query = mysql_query('
      SELECT
        c.`id`, c.`number`, sr.`type`
      FROM
        `l_relatives` as r
      LEFT JOIN
        `o_bank_card` as c ON
          c.`id` = (
            CASE
              WHEN ('.$obj_type.' < 8) THEN r.`to_obj`
              WHEN ('.$obj_type.' > 8) THEN r.`from_obj`
              WHEN ('.$obj_type.' = 8) THEN
                IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
            END
          )
      LEFT JOIN
        `spr_relatives` as sr ON
          sr.`id` = r.`type`
      WHERE
        r.`ais` = '.$_SESSION['crime']['ais'].' AND 
        ((r.`to_obj` = '.$obj.' AND
         r.`to_obj_type` = '.$obj_type.' AND
         r.`from_obj_type` = 8)
           OR
        (r.`from_obj` = '.$obj.' AND
         r.`from_obj_type` = '.$obj_type.' AND
         r.`to_obj_type` = 8))
      ORDER BY
        sr.`id`, c.`number`
    ') or die(mysql_error());
  } else {
    switch($obj_type) {
      case 'request':
        $query = mysql_query('
          SELECT
            CASE
              WHEN r.`relation` = 1 THEN "в запросе"
              WHEN r.`relation` = 2 THEN "в ответе"
            END as `type`,
            c.`id`, c.`number`
          FROM
            `l_request_object` as r
          JOIN
            `o_bank_card` as c ON
              c.`id` = r.`object` AND
              r.`object_type` = 8
          ORDER BY
            `type`, c.`number`
        ') or die(mysql_error());
        break;
    }
  }
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="bank_card.php?card_id='.$result["id"].'">'.$result["number"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных банковских карт --------//

//-------- список связанных электронных кошельков --------//
function related_wallets($obj, $obj_type) {
  require_once('connection.php');
  if (is_numeric($obj_type)) {
    $query = mysql_query('
      SELECT
        w.`id`, w.`number`, sr.`type`
      FROM
        `l_relatives` as r
      LEFT JOIN
        `o_wallet` as w ON
          w.`id` = (
            CASE
              WHEN ('.$obj_type.' < 9) THEN r.`to_obj`
              WHEN ('.$obj_type.' > 9) THEN r.`from_obj`
              WHEN ('.$obj_type.' = 9) THEN
                IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
            END
          )
      LEFT JOIN
        `spr_relatives` as sr ON
          sr.`id` = r.`type`
      WHERE
        r.`ais` = '.$_SESSION['crime']['ais'].' AND 
        ((r.`to_obj` = '.$obj.' AND
         r.`to_obj_type` = '.$obj_type.' AND
         r.`from_obj_type` = 9)
           OR
        (r.`from_obj` = '.$obj.' AND
         r.`from_obj_type` = '.$obj_type.' AND
         r.`to_obj_type` = 9))
      ORDER BY
        sr.`id`, w.`number`
    ') or die(mysql_error());
  } else {
    switch($obj_type) {
      case 'request':
        $query = mysql_query('
          SELECT
            CASE
              WHEN r.`relation` = 1 THEN "в запросе"
              WHEN r.`relation` = 2 THEN "в ответе"
            END as `type`,
            w.`id`, w.`number`
          FROM
            `l_request_object` as r
          JOIN
            `o_wallet` as w ON
              w.`id` = r.`object` AND
              r.`object_type` = 9
          ORDER BY
            `type`, w.`number`
        ') or die(mysql_error());
        break;
    }
  }
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="e-wallet.php?wall_id='.$result["id"].'">'.$result["number"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных электронных кошельков --------//

//-------- список связанных почтовых ящиков --------//
function related_mails($obj, $obj_type) {
  require_once('connection.php');
  if (is_numeric($obj_type)) {
    $query = mysql_query('
      SELECT
        m.`id`, m.`name`, sr.`type`
      FROM
        `l_relatives` as r
      LEFT JOIN
        `o_mail` as m ON
          m.`id` = (
            CASE
              WHEN ('.$obj_type.' < 10) THEN r.`to_obj`
              WHEN ('.$obj_type.' > 10) THEN r.`from_obj`
              WHEN ('.$obj_type.' = 10) THEN
                IF(r.`to_obj` = '.$obj.', r.`from_obj`, r.`to_obj`)
            END
          )
      LEFT JOIN
        `spr_relatives` as sr ON
          sr.`id` = r.`type`
      WHERE
        r.`ais` = '.$_SESSION['crime']['ais'].' AND 
        ((r.`to_obj` = '.$obj.' AND
         r.`to_obj_type` = '.$obj_type.' AND
         r.`from_obj_type` = 10)
           OR
        (r.`from_obj` = '.$obj.' AND
         r.`from_obj_type` = '.$obj_type.' AND
         r.`to_obj_type` = 10))
      ORDER BY
        sr.`id`, m.`name`
    ') or die(mysql_error());
  } else {
    switch($obj_type) {
      case 'request':
        $query = mysql_query('
          SELECT
            CASE
              WHEN r.`relation` = 1 THEN "в запросе"
              WHEN r.`relation` = 2 THEN "в ответе"
            END as `type`,
            m.`id`, m.`name`
          FROM
            `l_request_object` as r
          JOIN
            `o_mail` as m ON
              m.`id` = r.`object` AND
              r.`object_type` = 10
          ORDER BY
            `type`, m.`name`
        ') or die(mysql_error());
        break;
    }
  }
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="e-mail.php?mail_id='.$result["id"].'">'.mb_convert_case($result["name"], MB_CASE_LOWER, "UTF-8").'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных почтовых ящиков --------//

//-------- список связанных мессенджеров --------//
function related_messengers($obj, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      smes.`messenger`, 
      IF(mes.`nick` IS NOT NULL, CONCAT(mes.`account`, " (Ник - ", mes.`nick`,")"), mes.`account`) as `account`
    FROM
      `l_messenger_obj_rel` as rel
    JOIN
      `l_messengers` as mes ON
        mes.`id` = rel.`messenger`
    JOIN
      `spr_messenger` as smes ON
        smes.`id` = mes.`type`
    WHERE
      rel.`object` = '.$obj.' AND
      rel.`object_type` = '.$obj_type
  ) or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["messenger"].' &ndash; <a href="#">'.$result["account"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных мессенджеров --------//

//-------- список кличек объекта --------//
function related_nicknames($obj, $obj_type, $ais = '') {
  if ($ais != '') {
    $ais = ' AND nrel.`ais` = '.$ais;
  }
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      nrel.`id`, n.`nickname`
    FROM
      `l_nickname_obj_rel` as nrel
    LEFT JOIN
      `l_nicknames` as n ON
        n.`id` = nrel.`nickname`
    WHERE
      nrel.`object` = '.$obj.$ais
  ) or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li> &ndash; <a href="#">'.$result["nickname"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список кличек объекта  --------//

//-------- список изъятых наркотиков --------//
function related_drugs($obj) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      sd.`name`, wd.`weight`
    FROM
      `l_withdrawn_drug` as wd
    JOIN
      `spr_drugs` as sd ON
        sd.`id` = wd.`drug`
    WHERE
      wd.`event` = '.$obj
  ) or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li> &ndash; '.$result["name"].' ('.($result["weight"]+0).' гр.)'.'</li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список изъятых наркотиков  --------//

//-------- список связанных запросов --------//
function related_requests($obj, $obj_type) {
  require_once('connection.php');
  switch ($obj_type) {
    case 2:
      $query = mysql_query('
        SELECT
          req.`id`, t.`type`,
          DATE_FORMAT(req.`request_date`, "%d.%m.%Y") as `request_date`,
          req.`request_number`, req.`organisation`,
          CONCAT(
            " от ", DATE_FORMAT(req.`response_date`, "%d.%m.%Y"),
            IF(req.`response_number_out` IS NOT NULL, CONCAT(" № ", req.`response_number_out`), ""),
            IF(req.`response_number_in` IS NOT NULL, CONCAT(" (вх.№", req.`response_number_in`, ")"), "")
          ) as resp
        FROM
          `l_requests` as req
        LEFT JOIN
          `spr_request_types` as t ON
            t.`id` = req.`type`
        WHERE
          req.`event` = '.$obj.'
        ORDER BY
          req.`request_date`, req.`request_number` DESC
      ') or die(mysql_error());
      break;
      
    default:
      $query = mysql_query('
        SELECT
          req.`id`, rt.`type`,
          DATE_FORMAT(req.`request_date`, "%d.%m.%Y") as `request_date`,
          req.`request_number`, req.`organisation`,
          CONCAT(
            " от ", DATE_FORMAT(req.`response_date`, "%d.%m.%Y"),
            IF(req.`response_number_out` IS NOT NULL, CONCAT(" № ", req.`response_number_out`), ""),
            IF(req.`response_number_in` IS NOT NULL, CONCAT(" (вх.№", req.`response_number_in`, ")"), "")
          ) as resp
        FROM
          `l_request_object` as rel
        JOIN
          `l_requests` as req ON
            req.`id` = rel.`request`
        LEFT JOIN
          `spr_request_types` as rt ON
            rt.`id` = req.`type`
        WHERE
          rel.`object` = '.$obj.' AND
          rel.`object_type` = '.$obj_type.'
        ORDER BY
          req.`request_date`, req.`request_number` DESC
      ') or die(mysql_error());
      break;
  }
  
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li> &ndash; <a href="request.php?id='.$result["id"].'">Запрос "'.$result["type"].'" от '.$result['request_date'].' № '.$result['request_number'].' в '.$result['organisation'].((!empty($result['resp'])? '. Ответ '.$result['resp'] : '')).'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных запросов  --------//

//-------- список связанных КУСП  --------//
function related_kusp($res, $section = '') {
  $ret = null;
  if (!empty($res)) {
    require('spr_ovd.php');
    foreach($res as $n => $kusp) {
      $ret .= '<span class="added_relation">'.$spr_ovd[$kusp['ovd']].', КУСП '.$kusp['kusp'].' от '.date('d.m.Y', strtotime($kusp['date'])).' <span class="delete_relation" id="'.$n.'" group="kusp"'.(($section) ? ' section="'.$section.'"' : '').'>&times;</span></span>';
    }
    $ret .= '<div style="clear: left;"></div>';
    return $ret;
  }
}
//-------- список связанных КУСП  --------//

//-------- список связанных статей  --------//
function related_uk() {
  $ret = null;
  require('spr_uk.php');
  foreach($_SESSION['decision']['uk']['list'] as $n => $uk) {
    $ret .= '<span class="added_relation">'.$spr_uk[$uk].'<span class="delete_relation" id="'.$n.'" group="uk">&times;</span></span>';
  }
  $ret .= '<div style="clear: left;"></div>';
  return $ret;
}
//-------- список связанных статей  --------//

//-------- список связанных лиц и организаций  --------//
function related_faces_organisations() {
  $ret = null;
  require('spr_relatives.php');
  if (!empty($_SESSION['decision']['faces'])) {
    foreach($_SESSION['decision']['faces']['list'] as $rel => $faces) {
      foreach($faces as $n => $face) {
        $ret .= '<span class="added_relation">'.$spr_relatives[$rel].' - '.mb_convert_case($face['surname'].' '.$face['name'].' '. $face['fath_name'], MB_CASE_TITLE, 'UTF-8').' '.date('d.m.Y', strtotime($face['borth'])).'<span class="delete_relation" id="'.$rel.'|'.$n.'" group="objects_face">&times;</span></span>';
      }
    }
  }
  
  if (!empty($_SESSION['decision']['organisations'])) {
    foreach($_SESSION['decision']['organisations']['list'] as $rel => $orgs) {
      foreach($orgs as $n => $org) {
        $ret .= '<span class="added_relation">'.$spr_relatives[$rel].' - '.$org['title'].'<span class="delete_relation" id="'.$rel.'|'.$n.'" group="objects_organisation">&times;</span></span>';
      }
    }
  }
  $ret .= '<div style="clear: left;"></div>';
  return $ret;
}
//-------- список связанных лиц и организаций  --------//

//-------- загруженных файлов с информацией о них  --------//
function get_added_files_list_with_info($res, $rel_type = null) {
  if (is_array($res)) {
    $ret = null;
    $ret .= '<div class="added_files_list"><table rules="none" border="0" width="100%" cellpadding="5" cellspacing="0" cols="3"> <tr><th>Файл</th><th width="250px">Прогресс</th><th width="230px">Статус</th></tr>';
    foreach($res as $k => $file) {
      $ret .= '<tr class="item" id="'.$k.'">
                 <td class="file_info_block">
                   <span class="name"><b>Файл:</b> '.$file['basename'].'</span>
                   <span class="size"><b>Размер:</b> '.number_format($file['size'], 0, ',', '&thinsp;').' байт</span>
                 </td>
                 <td class="progress_block">
                   <div class="progress_bar">
                     <div class="progress" style="width: 100%;"></div>
                     <span class="proc">100%</span>
                   </div>
                 </td>
                 <td class="status_block" align="center">
                   <i>Загружен<br />'.((!empty($file['indexed']) and ($file['indexed'])) ? 'Индексирован' : 'В обработке...').'</i>
                   <span class="delete_relation" id="'.$k.'" group="files'.(($rel_type) ? '_'.$rel_type : '').'">&times;</span>
                 </td>
               </tr>';
    }
    $ret .= '</table></div>';
    return $ret;
  }
}
//-------- загруженных файлов с информацией о них  --------//

//-------- количество связей объекта --------//
function relations_count($obj, $type, $ais = '') {
  if ($ais != '') {
    $ais = ' AND rel.`ais` = '.$ais;
  }
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      COUNT(DISTINCT rel.`id`) as cnt
    FROM
      `l_relatives` as rel
    WHERE
      CASE
        WHEN rel.`from_obj_type` = '.$type.' THEN rel.`from_obj`
        WHEN rel.`to_obj_type` = '.$type.' THEN rel.`to_obj`
      END = '.$obj.$ais
  );
  $result = mysql_fetch_assoc($query);
  return $result['cnt'];
}
//-------- количество связей объекта --------//

//-------- определение браузера --------//
function user_browser($agent) {
  preg_match("/(MSIE|rv|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| |:)([0-9.]+)/", $agent, $browser_info);
  $ret = 'unknown';
  if (!empty($browser_info)) {
    list(,$browser,$version) = $browser_info;
    if (preg_match("/Opera ([0-9.]+)/i", $agent, $opera)) return 'Opera '.$opera[1];
    if ($browser == 'MSIE') {
      preg_match("/(Maxthon|Avant Browser|MyIE2)/i", $agent, $ie);
      if ($ie) return $ie[1].' based on IE '.$version;
      return 'IE '.$version;
    }
    if ($browser == 'Firefox') {
      preg_match("/(Flock|Navigator|Epiphany)\/([0-9.]+)/", $agent, $ff);
      if ($ff) {
        return $ff[1].' '.$ff[2];
      } else {
        return $browser.' '.$version;
      };
    }
    if ($browser == 'rv') return 'IE '.$version;
    if ($browser == 'Opera' && $version == '9.80') return 'Opera '.substr($agent,-5);
    if ($browser == 'Version') return 'Safari '.$version;
    if (!$browser && strpos($agent, 'Gecko')) return 'Browser based on Gecko';
    $ret = $browser.' '.$version;
  }
  return $ret;
}
//-------- определение браузера --------//

//-------- определение устаревшего браузера --------//
function not_valid_browser_old($client) {
  foreach (array('unknown', 'IE 6', 'IE 7', 'IE 8') as $client) {
    if (stripos(CLIENT_BROWSER, $client) !== false) {
      return false;
      break;
    }
  }
  return true;
}

function not_valid_browser($client) {
  if (!preg_match('/(gecko)/is', $client)) {
    preg_match("/(MSIE|IE|Opera|Firefox|Chrome|Flock|Navigator|Epiphany|Safari|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| |:)([0-9.]+)/", CLIENT_BROWSER, $browser_info);
    preg_match('/^([0-9]+)(.)/', $browser_info[2], $version);
    switch($browser_info[1]){
      case 'IE': if (in_array($version[1], array(6, 7, 8))) return false; break;
      case 'Opera': if ($version[1] < 13) return false; break;
    }
  }
  return true;
}
//-------- определение устаревшего браузера --------//

//-------- запись лога добавления отказного --------//
function writeSessionString($activity = "NULL") {
  require_once('connection_log.php');
  mysql_query('
    INSERT INTO
      otkaz_create (activity_id, session_file)
    VALUES (
      '.$activity.', "'.mysql_real_escape_string(session_encode()).'"
    )
  ');
  return mysql_insert_id();
}
//-------- запись лога добавления отказного --------//
//-------- запись id отказного в лог добавления отказного --------//
function updateSessionString($id, $otkaz = '', $error = '') {
  require_once('connection_log.php');
  $setStr = array();
  if (!empty($error)) $setStr[] = 'errorMsg = "'.implode(",", $error).'"';
  if (!empty($otkaz)) $setStr[] = 'otkaz_id = "'.$otkaz.'"';
  mysql_query('
    UPDATE
      otkaz_create
    SET
      '.implode(" AND ", $setStr).'
    WHERE
      id = "'.$id.'"
  ');
}
//-------- запись id отказного в лог добавления отказного --------//

//-------- формирует строку лимита в SQL запрос --------//
function limit($kol_rec) {
  if (!empty($_GET["p"])) { // если номер страницы не пустой
    $page = intval($_GET["p"]); // округляем до целого
  } else {
    $page = 1; // если пусто, то на первую страницу
  }
  if ($page > $kol_rec) {
    $page = 1; // если страница больше максимальной, то на первую страницу
    }
  if ($page == 1) { // если страница 1
    $start = 0; // то выбираем записи начиная с 0
  } else {
    $start = REC_ON_PAGE * ($page - 1); // иначе в зависимости от номера страницы
  }
  return array('limit' => 'LIMIT '.$start.', '.REC_ON_PAGE, 'page' => $page); // формируем строку лимита
}
//-------- формирует строку лимита в SQL запрос --------//

//-------- формирует листалку страниц результатов поиска --------//
function listing($query, $par_array, $script, $connection) {
  require_once($connection);
  $kol_rec = mysql_num_rows(mysql_query($query)); // количество записей по запросу
  if (!empty($_GET["p"])) { // если номер страницы не пустой
    $page = intval($_GET["p"]); // округляем до целого
  } else {
    $page = 1; // если пусто, то на первую страницу
  }
  if ($page > $kol_rec) {
    $page = 1; // если страница больше максимальной, то на первую страницу
    }
  $limit = limit($kol_rec);
  $query .= ' '.$limit['limit'];
  $kol_str = ceil($kol_rec/REC_ON_PAGE); // формируем количество страниц
  $link_array = array();
  $resSince = (REC_ON_PAGE * $page) - REC_ON_PAGE + 1;
  $resTo = (REC_ON_PAGE * $page) > $kol_rec ? $kol_rec : REC_ON_PAGE * $page;
  $return = '<span class="result_count">Показано результатов: '.$resSince.' - '.$resTo.' (всего '.$kol_rec.')</span>';
  if ($kol_str > 1) { // если страниц больше одной
    foreach($par_array as $key => $value) {
      if (substr($value, strlen($value)-1, 1) == "%") {
        $value = substr($value, 0, strlen($value)-1);
      };
      if ($key == "p") {
        continue;
      }
      $link_array[] = $key."=".urlencode($value); // записываем параметры в массив
    }
    $link = $script.implode("&", $link_array); // строка поиска для адресной строки
    $return .= '<div class="listing">';
    $return .= '<span style="margin: 0 0 10px 0; display: block; color: gray; font-size: 120%;">Страницы:</span>';
    $return .= '<ul>';
    if ($page > 2) { // если текущая страница №3 и более
      $return .= '<li><a href="'.$link.'&p=1"><span class="sign">&lt;&lt;</span></a></li>'; // печатаем ссылку на первую страницу
    }
    if ($page != 1) { // если текущая страница не №1
      $return .= '<li><a href="'.$link.'&p='.($page-1).'"><span class="sign">&lt;</span></a></li>'; // печатаем ссылку на предыдущую страницу
    }
    $n = 1;
    while ($n <= $kol_str) { // запускаем цикл печати номеров страниц
      if (($n > $page - 3) && $n < $page + 3) {
        if ($n == $page) {
          $return .= '<li><a class="listing pressed" href="'.$link.'&p='.$n.'"><span class="sign">'.$n.'</span></a></li>'; // если текущая страница, выделяем ее
          ++$n;
          continue;
        }
        $return .= '<li><a href="'.$link.'&p='.$n.'"><span class="sign">'.$n.'</span></a></li>';
      }
      ++$n;
    }
    if ($page != $kol_str) { // если страница не последняя
      $return .= '<li><a href="'.$link.'&p='.($page+1).'"><span class="sign">&gt;</span></a></li>'; // печатаем ссылку на следующую страницу
    }
    if ($page < $kol_str-1) { // если страница меньше предпоследней
      $return .= '<li><a href="'.$link.'&p='.$kol_str.'"><span class="sign">&gt;&gt;</span></a></li>'; // печатаем ссылку на последнюю страницу
    }
    $return .= '</ul>';
    $return .= '</div>';
  }
  return $return;
}
//-------- формирует листалку страниц результатов поиска --------//

//формирование пути файла
function source($id) {
	$db = mysql_connect('localhost', 'логин', 'пароль');
  mysql_select_db('obv_otk', $db);
  mysql_query("set names 'utf8'");
	$sql_file_query = mysql_query('
    SELECT
      o.data_resh,
      o.file_final,
      ovd.name_dir_otk
		FROM
			otkaz as o
        JOIN
        	spr_ovd as ovd ON
            ovd.id_ovd = o.id_ovd
		WHERE
			o.id = "'.$id.'"
	') or die("Ошибка SQL: ".mysql_error());
	while ($file_query = mysql_fetch_assoc($sql_file_query)) {
    $file = $file_query["file_final"];
    $date = $file_query["data_resh"];
    $name_dir_otk = $file_query["name_dir_otk"];
	}
  $direction = array(1 => "01_январь", "02_Февраль", "03_Март", "04_Апрель", "05_Май", "06_Июнь", "07_Июль", "08_Август", "09_Сентябрь", "10_Октябрь", "11_Ноябрь", "12_Декабрь");
  $year = date("Y", strtotime($date))."год";
  $month_ind = date("m", strtotime($date))+0;
  $month = $direction[$month_ind];
  $dir = DIR_FILES."/Отказные/".$year."/".$name_dir_otk."/".$month."/".$file;
	return $dir;
}


function document($auth = false, $id = " IS NULL", $color = 246, $display = "block") {
  require ('connection.php');
  if ($auth) {
    $authDoc = '';
  } else {
    $authDoc = ' AND need_auth = 0';
  }
  $query = mysql_query('
    SELECT
      id, doc_num, doc_date, type, directory, file_name, name, parent, need_auth
    FROM
      documents
    WHERE
      parent '.$id.'
    ORDER BY
      doc_date DESC
  ') or die("Ошибка SQL: ".mysql_error());
  if (mysql_num_rows($query)):
    while ($result = mysql_fetch_array($query)):
      $id = $result["id"];
      $type = $result["type"];
      $result["doc_date"] ? $doc_date = " от ".date("d.m.Y", strtotime($result["doc_date"])) : $doc_date = "";
      $result["doc_num"] ? $doc_num = " № ".$result["doc_num"]." " : $doc_num = "";
      $name = '"'.$result["name"].'"'; ?>
      <div class="document" style="background: rgb(<?=$color.', '.$color.', '.$color?>); display: <?=$display?>">
        <?php if ($result["need_auth"] && !$auth) {
          echo $type.$doc_date.$doc_num.$name. ' <span class="needAuth">&nbsp;&nbsp;&nbsp;&nbsp;Необходима авторизация!</span>';
        } else {
          if ($result["file_name"] != "") {
            echo '<a href="download_document.php?id='.$id.'">'.$type.$doc_date.$doc_num.$name.'</a>';
          } else {
            echo $type.$doc_date.$doc_num.$name;
          }
        } ?>
        <?=document($auth, " = ".$id, $color - 9, "none")?>
      </div>
    <?php endwhile;
  endif;
}


function doc_type($x = "") {
  require('connection.php');
  $sql_type = mysql_query('
    SELECT
      type
    FROM
      documents
    WHERE
      type IS NOT NULL AND
        type <> ""
    GROUP BY
      type
  ');
  $option = '<option value=""></option>';
  while ($type = mysql_fetch_array($sql_type)) {
    if ($type['type'] == $x) {
      $option .= '<option value="'.$type['type'].'" selected>'.$type['type'].'</option>';
    } else {
      $option .= '<option value="'.$type['type'].'">'.$type['type'].'</option>';
    }
  }
  return $option;
}

function doc_dir($x = "") {
  require('connection.php');
  $sql_dir = mysql_query('
    SELECT
      directory
    FROM
      documents
    GROUP BY
      directory
  ');
  $option = '<option value=""></option>';
  while ($dir = mysql_fetch_array($sql_dir)) {
    if ($dir['directory'] == $x) {
      $option .= '<option value="'.$dir['directory'].'" selected>'.$dir['directory'].'</option>';
    } else {
      $option .= '<option value="'.$dir['directory'].'">'.$dir['directory'].'</option>';
    }
  }
  return $option;
}

function doc_parents($x = "") {
  require('connection.php');
  $sql_par = mysql_query('
    SELECT
      id,
      type,
      DATE_FORMAT(doc_date, "%d.%m.%Y") as doc_date,
      doc_num,
      name
    FROM
      documents
  ');
  $option = '<option value=""></option>';
  while ($parent = mysql_fetch_array($sql_par)) {
    $id = $parent['id'];
    strlen($parent['type']) ? $type = $parent['type'] : $type = "";
    strlen($parent['doc_date']) ? $doc_date = ' от '.$parent['doc_date'] : $doc_date = "";
    strlen($parent['doc_num']) ? $doc_num = ' № '.$parent['doc_num'] : $doc_num = "";
    strlen($parent['name']) ? $name = ' "'.$parent['name'].'"' : $name = "";
    $parent = $type.$doc_date.$doc_num.$name;
    if ($id == $x) {
      $option .= '<option value="'.$id.'" selected>'.$parent.'</option>';
    } else {
      $option .= '<option value="'.$id.'">'.$parent.'</option>';
    }
  }

  return $option;
}

function decisionYears($type = 1) {
  if (!is_numeric($type)) {
    $type = 1;
  } else {
    $type = floor(abs($type));
  }
  require('connection.php');
  $query = mysql_query('
    SELECT
      YEAR(d.`date`) as `year`
    FROM
      `l_decisions` as d
    WHERE
      d.`type` = '.$type.' AND
      d.`deleted` = 0 AND
      d.`date` IS NOT NULL
    GROUP BY
      YEAR(d.`date`)
    ORDER BY
      `year` ASC
  ');
  $resArray = array();
  while ($result = mysql_fetch_array($query)) {
    $resArray[] = $result['year'];
  }
  return $resArray;
}

function KUSPYearsList() {
  require_once('connection.php');
  $resArray = array();
  $query = mysql_query('
    SELECT
      DISTINCT YEAR(ek.`reg_date`) as `year`
    FROM
      `ek_kusp` as ek
    WHERE
      YEAR(ek.`reg_date`) > 2010
    ORDER BY
      `year` ASC
  ');
  while ($result = mysql_fetch_array($query)) {
    $resArray[] = $result['year'];
  }
  return $resArray;
}

function OrientYearsList() {
  require('connection.php');
  $resArray = array();
  $query = mysql_query('
    SELECT
      DISTINCT o.`year` as `year`
    FROM
      `l_orientations` as o
    ORDER BY
      `year` ASC
  ');
  while ($result = mysql_fetch_array($query)) {
    $resArray[] = $result['year'];
  }
  return $resArray;
}

function ReferencesYearsList() {
  require('connection.php');
  $resArray = array();
  $query = mysql_query('
    SELECT
      DISTINCT YEAR(r.`create_date`) as `year`
    FROM
      `l_references` as r
    ORDER BY
      `year` ASC
  ');
  while ($result = mysql_fetch_array($query)) {
    $resArray[] = $result['year'];
  }
  return $resArray;
}

function WeaponAccountYearsList() {
  require('connect.php');
  $resArray = array();
  $query = '
    SELECT
      DISTINCT YEAR(wa.`reg_date`) as `year`
    FROM
      `l_weapons_account` as wa
    ORDER BY
      `year` ASC
  ';
  $result = $db->query($query);
  while ($row = $result->fetch_object()) {
    $resArray[] = $row->year;
  }
  $result->close();
  $db->close();
  return $resArray;
}

function IndictmentsYearsList() {
  require('connect.php');
  $resArray = array();
  $query = '
    SELECT DISTINCT
      YEAR(f.`create_date`) `year`
    FROM
      `ic_f1_files` as ff
    JOIN
      `l_files` as f ON
        f.`id` = ff.`file` AND
        f.`deleted` = 0
    WHERE
      ff.`deleted` = 0

    UNION

    SELECT
      YEAR(CURRENT_DATE)
      
    ORDER BY `year` ASC
  ';
  $result = $db->query($query);
  while ($row = $result->fetch_object()) {
    $resArray[] = $row->year;
  }
  $result->close();
  $db->close();
  return $resArray;
}

function IndictmentsYearsListForWeapons() {
    require('connect.php');
  $resArray = array();
  $query = '
      SELECT DISTINCT year(w.reg_date) as `year` 
      from l_weapons_account as w
      where w.deleted = 0
      UNION
      SELECT
      YEAR(CURRENT_DATE)
      order by `year`
  ';
  $result = $db->query($query);
  while ($row = $result->fetch_object()) {
    $resArray[] = $row->year;
  }
  $result->close();
  $db->close();
  return $resArray;
}

// ******** мой селект ******** //
function my_select($name, $query_str = '', $par = null, $more_attr = null, $width = '300') {
  $array = array();
  $str = '';
  if (!empty($query_str)) {
    require (KERNEL.'connection.php');
    if (is_string($query_str)) {
      switch($query_str) {
        case 'spr_ovd':
            $query_str = ' SELECT `id_ovd`, `ovd` FROM `spr_ovd` WHERE visuality = "1" ORDER BY `ovd` ';
          break;
        case 'spr_ovd_ibd':
            $query_str = ' SELECT `ibd_code`, `ovd` FROM `spr_ovd` WHERE visuality = "1" AND `ibd_code` > 0 ORDER BY `id_ovd` ';
          break;
        case 'spr_uk':
            $query_str = ' SELECT `id_uk`, `st` FROM `spr_uk` WHERE `visuality` = 1 ORDER BY `st` ';
          break;
        case 'spr_upk':
            $query_str = ' SELECT `st_upk` as `id`, `st_upk` FROM `spr_upk` ';
          break;
        case 'spr_slujba':
            $query_str = ' SELECT `id_slujba`, `slujba` FROM `spr_slujba` ORDER BY `id_slujba` ';
          break;
        case 'spr_relatives_decision':
            $query_str = ' SELECT `id`, `type` FROM `spr_relatives` WHERE `to_obj` = 0 ';
          break;
        case 'spr_relatives_accusatory':
            $query_str = ' SELECT `id`, `type` FROM `spr_relatives` WHERE `to_obj` = 0 AND `ais` = 5 ';
          break;
        case 'spr_org_types':
            $query_str = ' SELECT s.`id`, IF(s.`owner` IS NULL, CONCAT("группа &laquo;", s.`type`, "&raquo;"), s.`type`) as `type` FROM `spr_org_types` as s ORDER BY s.`owner`, s.`type` ';
          break;
        case 'spr_orientation':
            $query_str = ' SELECT `id`, `type` FROM `spr_orientation` ';
          break;
        case 'spr_base_receiving_weapons':
            $query_str = ' SELECT `id`, `name` FROM `spr_base_receiving_weapons` ';
          break;
        case 'spr_purpose_placing':
            $query_str = ' SELECT `id`, `name` FROM `spr_purpose_placing` ';
          break;
        case 'spr_weapon_sorts_fa':
            $query_str = ' 
              SELECT 
                ws.`id`,
                IF(ws.`name` = wsg.`name`, ws.`name`, CONCAT(wsg.`name`, " - ", ws.`name`)) as `name`
              FROM 
                `spr_weapon_sorts` as ws, `spr_weapon_sorts` as wsg
              WHERE 
                ws.`group` = wsg.`id` AND
                ws.`type` = 1
            ';
          break;
        case 'spr_weapon_groups':
            $query_str = ' SELECT `id`, `name` FROM `spr_weapon_groups` ';
          break;
        case 'spr_decision_in_arms':
            $query_str = ' SELECT `id`, `name` FROM `spr_decision_in_arms` ';
          break;
        case 'spr_photo_types':
            $query_str = ' SELECT `id`, `name` FROM `spr_photo_types` ';
          break;
        case 'spr_kusp_decisions':
            $query_str = ' SELECT `id`, `name` FROM `spr_kusp_decisions` ';
          break;
        case 'spr_messenger':
            $query_str = ' SELECT `id`, `messenger` FROM `spr_messenger` ';
          break;
      }
      $query = mysql_query($query_str) or die(mysql_error());
      while ($result = mysql_fetch_array($query)) {
        $array[$result[0]] = $result[1];
      }
    } elseif (is_array($query_str)) {
      foreach ($query_str as $k => $v) {
        $array[$k] = $v;
      }
    }
  }
  $str = ($par) ? $array[$par] : '&nbsp;';
  $return = '<div class="my_select" style="width: '.$width.'px;">';
  $return .= '<input type="hidden" name="'.$name.'" value="'.$par.'"'.((!empty($more_attr)) ? $more_attr : '').'/>';
  $return .= '<button type="button" class="my_select_button"><span class="my_select_button_value">'.$str.'</span>&nbsp;</button>';
  if (!empty($array)) {
    $return .= '<ul class="my_select_list">';
    if (count($array) > 10) $return .= '<li class="my_select_search_list_item skip"><input type="text" class="my_select_search"/></li>';
    $return .= '<li class="skip"><a href="#" id="">&nbsp;</a></li>';
    foreach($array as $id => $value) {
      $return .= '<li><a href="#" id="'.$id.'" '.(($par == $id) ? ' class="selected"' : '').'>'.$value.'</a></li>';
    }
    $return .= '</ul></div>';
  }
  return $return;
}
// ^^^^^^^^ мой селект ^^^^^^^^ //

function my_date_field($name, $value = null, $more_class = null, $more_attr = null) {
  $return = '<div class="datepicker_block">';
  $return .= '<input type="text" maxlength="10" class="datepicker '.$more_class.'" name="'.$name.'" autocomplete="off" placeholder="__.__.____"'.(($value) ? ' value="'.$value.'" ' : '').((!empty($more_attr)) ? ' '.$more_attr : '').'/>';
  $return .= '<div class="ajax_search_result"></div>';
  $return .= '<div class="calendar_icon"><img src="/images/calendar_little.png" height="22px"/></div>';
  $return .= '</div>';
  return $return;
}

// ******** последняя дата выгрузки ******** //
function lastSwap($type) {
  if (preg_match('/legenda/ui', $type)) {
    require('connection.php');
    $query = mysql_query('
      SELECT
        MAX(ekt.`update_time`) as `time`, ekt.`update_date` as `date`
      FROM
        `ek_kusp` as ekt
      WHERE
        ekt.`update_date` = (SELECT MAX(ekd.`update_date`) as `date` FROM `ek_kusp` as ekd)
    ');
  } else {
    require('connection_log.php');
    $query = mysql_query('
      SELECT
        MAX(`time`) as `time`,
        `date`
      FROM
        data_swap
      WHERE
        `date` =
        (
          SELECT
            MAX(`date`)
          FROM
            data_swap
          WHERE
            type = "'.$type.'"
        ) AND
        type = "'.$type.'"
    ');
  }
  while ($result = mysql_fetch_array($query)) {
    $LAST_SWAP_DATE = $result['date'];
    $LAST_SWAP_TIME = $result['time'];
  }
  if (empty($LAST_SWAP_DATE) || empty($LAST_SWAP_TIME)){
    return array('LAST_SWAP_DATE' => '1900-01-01', 'LAST_SWAP_TIME' => '00:00:00');
  } else {
    return array('LAST_SWAP_DATE' => $LAST_SWAP_DATE, 'LAST_SWAP_TIME' => $LAST_SWAP_TIME);
  }
}
// ^^^^^^^^ последняя дата выгрузки ^^^^^^^^ //

// ******** последняя дата выгрузки ОВД******** //
function OVDlastSwap($ovd) {
  $query = mysql_query('
    SELECT
      MAX(lp.`time_end`) as `time`,
      lp.`date_end` as `date`
    FROM
      `ek_protocol` as lp
    WHERE
      `ovd` = (SELECT `legCode` FROM `spr_ovd` WHERE `id_ovd` = '.$ovd.') AND
      `date_end` = (
        SELECT
          MAX(`date_end`)
        FROM
          `ek_protocol`
        WHERE
          `ovd` = (SELECT `legCode` FROM `spr_ovd` WHERE `id_ovd` = '.$ovd.')
      )
  ');
  while ($result = mysql_fetch_array($query)) {
    $LAST_SWAP_DATE = $result['date'];
    $LAST_SWAP_TIME = $result['time'];
  }
  if (empty($LAST_SWAP_DATE) || empty($LAST_SWAP_TIME)){
    return false;
  } else {
    return array('LAST_SWAP_DATE' => $LAST_SWAP_DATE, 'LAST_SWAP_TIME' => $LAST_SWAP_TIME);
  }
}
// ^^^^^^^^ последняя дата выгрузки ОВД ^^^^^^^^ //

// ******** запись лога выгрузки ******** //
function logSwap($type, $date, $time) {
  require(KERNEL.'connection_log.php');
  mysql_query('
    INSERT INTO
      data_swap(`type`, `date`, `time`)
    VALUES
      ("'.$type.'", "'.$date.'", "'.$time.'")
  ') or die(mysql_error());
};
// ^^^^^^^^ запись лога выгрузки ^^^^^^^^ //

// ******** удаление устаревших каталогов сессий ******** //
function emptySessDirRemove() {
  $sessArr = $dirArr = array();
  $arr = glob(DIR_SESSION.'*', GLOB_MARK);
  foreach($arr as $item) {
    if (is_dir($item)) {
      $dirArr[] = str_replace('_tmp_', '', basename($item));
    }
    if(is_file($item)) {
      $sessArr[] = str_replace('sess_', '', basename($item));
    }
  }
  foreach($dirArr as $dir) {
    if (!in_array($dir, $sessArr)) {
      dir_del(DIR_SESSION.'_tmp_'.$dir);
    }
  }
};
// ^^^^^^^^ удаление устаревших каталогов сессий ^^^^^^^^ //

// ******** наименование АИС ******** //
function ais($id) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      `name`, `ais`
    FROM
      `spr_ais`
    WHERE
      `id` = '.$id
  );
  $ais = mysql_fetch_array($query);
  if ($ais['ais']) {
    return 'АИС "'.$ais['name'].'"';
  } else {
    return 'Сервис "'.$ais['name'].'"';
  }
}
// ^^^^^^^^ наименование АИС ^^^^^^^^ //

// ******** проверка входа в АИС ******** //
function is_ais($only = 0) {
  if (!is_array($only) && $only != 0) {
    $tmp[] = $only;
  } else {
    $tmp = $only;
  }
  if (isset($_SESSION['crime']['ais'])) {
    if (is_array($tmp)) {
      if (in_array($_SESSION['crime']['ais'], $tmp)) {
        return ais($_SESSION['crime']['ais']);
      } else {
        header('Location: index.php');
      }
    } else {
      return ais($_SESSION['crime']['ais']);
    }
  } else {
    header('Location: index.php');
  }
}
// ^^^^^^^^ проверка входа в АИС ^^^^^^^^ //

// ******** приведение типа переменной по содержимому ******** //
function get_var_in_data_type($var) {
  $var = trim($var);
  if (is_numeric($var)) {
    if ($var > 2147483647 or ($var < -2147483648)) return (string)$var;
    if (preg_match('/[.,]0{0,}$/ui', $var)) return (integer)$var;
    if (preg_match('/[.,][\d]*[0]{0,}?$/ui', $var)) return (float)$var;
    return (integer)$var;
  }
  if (preg_match('/^(true|false)$/is', $var, $a)) {
    return ($a[0] == 'true') ? true : false;
  }
  if (empty($var)) return null;
  return (string)$var;
}
// ^^^^^^^^ приведение типа переменной по содержимому ^^^^^^^^ //

function breadcrumbs($links = array()) {
  $tot = count($links) - 1;
  $str = array();
  $cntr = 0;
  foreach ($links as $n => $l) {
    if ($l) {
      $str[] = '<a href="'.$l.'">'.$n.'</a> ';
    } else {
      $str[] = (string)($n);
    }
    /*
    if ($cntr < $tot) {
      $str .= '<a href="'.$l.'">'.$n.'</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp; ';
    } else {
      $str .= $n;
    }
    $cntr++;*/
  }
  return implode('&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp; ', $str);
}

function analysis_file_QIWI() {
  $ret = null;
  if (!empty($_SESSION['qiwi_info'])) {
    $ret .= '<ul class="sheets_list">';
    foreach($_SESSION['qiwi_info']['sheets'] as $i => $sheet) {
      $ret .= '<li '.(($i == 0) ? ' class="active"' : '').' id="'.$i.'" onclick="sheets_switch(this)">'.$sheet.'</li>';
    }
    $ret .= '</ul>';
    $ret .= '<div class="sheet_data">';
    $ret .= '<center><span style="font-size: 1.2em;"><strong>QIWI-кошелек № '.$_SESSION['qiwi_info']['account'].'</strong></span></center>';
    foreach($_SESSION['qiwi_info'] as $s => $data) {
      switch(true) {
        case ($s === 0): 
          $ret .= '<table rules="all" border="1" id="'.$s.'" cellpadding="5" width="100%">';
            foreach($data as $n => $arr) {
              $ret .= '<tr>';
              $ret .= '<td width="250px">'.$arr['name'].'</td>';
              $ret .= '<td class="data_cell" id="qiwi_info['.$s.']['.$n.'][data]">'.$arr['data'].'</td>';
              $ret .= '</tr>';
            }
          $ret .= '</table>';
          break;
        case ($s > 0):
          $ret .= '<table rules="all" border="1" id="'.$s .'" style="display: none;" cellpadding="5" width="100%">';
          if (!empty($data)) {
            $ret .= '<tr>';
            foreach($_SESSION['qiwi_info']['headers'][$s] as $header) {
              $ret .= '<th>'.$header.'</td>';
            }
            $ret .= '</tr>';
            foreach($data as $n => $row) {
              $ret .= '<tr>';
                foreach($row as $c => $v) {
                  $ret .= '<td class="data_cell" id="qiwi_info['.$s.']['.$n.']['.$c.']">'.$v.'</td>';
                }
              $ret .= '</tr>';
            }
          } else {
            $ret .= '<tr><td>Сведения отсутствуют</td></tr>';
          }
          $ret .= '</table>';
          break;
      }
    }
    $ret .= '</div>';
    $ret .= '<form type="json"><input type="hidden" name="analysis_file" value="qiwi"/><div class="add_button_box"><div class="button_block"><span class="button_name">Анализ...</span></div></div></form>';
  }
  return $ret;
}

function inet_ntoa($ip) {
  $tmp = explode('.', $ip);
  return $tmp[0]*pow(256, 3)+$tmp[1]*pow(256, 2)+$tmp[2]*pow(256, 1)+$tmp[3]*pow(256, 0);
}

function to_integer($str) {
  $str = preg_replace('/[^\d\.]/', '', $str);
  $str = floor(abs($str));
  return $str;
}

function readable_bytes($size) {
  $fNames = array('байт', 'Кб', 'Мб', 'Гб', 'Тб');
  return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).'&thinsp;'.$fNames[$i] : '0 байт';
}

function send_mail($mail_to, $thema = null, $html = null, $paths = null) {
  
  ini_set('SMTP', '10.158.0.1');
  ini_set('smtp_port', '25');
  
  if (is_array($mail_to))
    $mail_to = implode(', ', $mail_to);
  if (!empty($thema))
    $thema = mb_convert_encoding($thema, 'windows-1251', 'utf-8');
  if (!empty($html))
    $html = mb_convert_encoding($html, 'windows-1251', 'utf-8');

  $EOL = "\r\n"; // ограничитель строк, некоторые почтовые сервера требуют \n - подобрать опытным путём
  $boundary   = "--".md5(uniqid(time()));  // любая строка, которой не будет ниже в потоке данных.  
  $headers    = "MIME-Version: 1.0;$EOL";   
  $headers   .= "Content-Type: multipart/mixed; boundary=\"$boundary\"$EOL";  
  $headers   .= "From: OORI Services Server";  
    
  $multipart  = "--$boundary$EOL";   
  $multipart .= "Content-Type: text/html; charset=windows-1251$EOL";   
  $multipart .= "Content-Transfer-Encoding: base64$EOL";   
  $multipart .= $EOL; // раздел между заголовками и телом html-части 
  $multipart .= chunk_split(base64_encode($html));   
  
  if ($paths) {
    if (!is_array($paths))
      $paths = (array)$paths;
    
    foreach ($paths as $n => $path) {
      $fp = fopen($path, "rb");
      if (!$fp) {
        print "Cannot open file";
        exit();
      }
      $file = fread($fp, filesize($path));
      $name = pathinfo($path, PATHINFO_BASENAME); // в этой переменной надо сформировать имя файла (без всякого пути)  
      fclose($fp);
      
      $multipart .=  "$EOL--$boundary$EOL";
      $multipart .= "Content-Type: application/octet-stream; name=\"$name\"$EOL";
      $multipart .= "Content-Transfer-Encoding: base64$EOL";
      $multipart .= "Content-Disposition: attachment; filename=\"$name\"$EOL";
      $multipart .= $EOL; // раздел между заголовками и телом прикрепленного файла
      $multipart .= chunk_split(base64_encode($file));
    }
  }
  $multipart .= "$EOL--$boundary--$EOL";
  
  if (!mail($mail_to, $thema, $multipart, $headers)) {
    return false;
  } else {
    return true;  
  }
}

function get_meaning_from_spr($spr, $id, $column = 'name', $primary = 'id') {
  require('connect.php');
  $query = '
    SELECT
      `'.$column.'`
    FROM
      `'.$spr.'`
    WHERE
      `'.$primary.'` = '.$id.'
  ';
  if (!$result = $db->query($query))
    return false;
  
  $row = $result->fetch_array(MYSQLI_NUM);
  $result->close();
  $db->close();
  return $row[0];
}


// сложение элементов двух массивов по ключу
function array_elem_sum($array1, $array2) {
  $res = array();
  if (count($array1) > count($array2)) {
    $_t1 =& $array1;
    $_t2 =& $array2;
  } else {
    $_t1 =& $array2;
    $_t2 =& $array1;
  }
  foreach ($_t1 as $k => $v) {
    if (!array_key_exists($k, $_t2)) $_t2[$k] = 0;
    $res[$k] = (float)$v + (float)$_t2[$k];
  }
  return $res;
}
?>
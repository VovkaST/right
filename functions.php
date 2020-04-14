<?php
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

function ovd($login) {
  $ovd = (int)substr($login, 1, 2);
  require ('connection.php');
  $ovd_query = mysql_query('
    SELECT
      id_ovd, ovd_full
    FROM
      spr_ovd
    WHERE
      ibd_code = "'.$ovd.'"
  ') or die(mysql_error());
  while ($ovd = mysql_fetch_array($ovd_query)) {
    return array($ovd["id_ovd"], $ovd["ovd_full"]);
  } 
}

function getOvdName($id) {
  require ('connection.php');
  $ovd_query = mysql_query('
    SELECT
      id_ovd, ovd_full, ovd
    FROM
      spr_ovd
    WHERE
      id_ovd = "'.$id.'"
  ') or die(mysql_error());
  while ($ovd = mysql_fetch_array($ovd_query)) {
    return array($ovd["ovd"], $ovd["ovd_full"]);
  } 
}

function new_session($dir_session, $user) {
  session_start(); // начинаем новую сессию
  $sess_id = session_id();
  $_SESSION['user'] = $user;
  $_SESSION['end_session_time'] = time() + 1800;
  $_SESSION['enter_date'] = $_SESSION['last_active_date'] = date('Y-m-d');
  $_SESSION['enter_time'] = $_SESSION['last_active_time'] = date('H:i:s', time());
  $_SESSION['dir_session'] = $dir_session."_tmp_".$sess_id."/"; // временный каталог сессии
  if ($user == "a051204" || $user == "a051209") {
    $_SESSION['admin'] = 1;
  }
  if (!is_dir($_SESSION['dir_session'])) {
    mkdir($_SESSION['dir_session']); // создаем его
  }
  $_SESSION['activity_id'] = new_activity($sess_id, $_SERVER['REMOTE_ADDR'], $user);// записываем в БД
  setcookie("sess_id", $sess_id, 0x7FFFFFFF, "/"); // записываем сессию в куки
}

function new_activity($sess_id, $ip, $user) {
  require('connection_log.php');
	mysql_query('
		INSERT INTO
			activity(session_id, ip, user, browser,
			enter_date, enter_time, last_active_date, last_active_time)
		VALUES
			("'.$sess_id.'", "'.$ip.'", "'.$user.'", "'.user_browser($_SERVER['HTTP_USER_AGENT']).'",
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


function query_log($activity_id, $par) {
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
  $referer = basename($_SERVER["HTTP_REFERER"]);
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
  if (isset($_SESSION['dir_session'])) {
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
function sel_ovd($ovd = "") {
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
  $option = '<option value=""></option>';
  while ($result = mysql_fetch_array($query)) {
    if ($result['id_ovd'] == $ovd) {
      $option .= '<option value="'.$result['id_ovd'].'" selected>'.$result['ovd'].'</option>';
    } else {
      $option .= '<option value="'.$result['id_ovd'].'">'.$result['ovd'].'</option>';
    }    
  }
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
function sel_uk($sel_uk = 0) {
  require_once('connection.php');
  $query = mysql_query("
    SELECT
      id_uk, st
    FROM 
      spr_uk
	WHERE
	  visuality = 1
	ORDER BY
      st
  ");
  echo '<select name="article_id" id="criminal_st" class="crim_article">';
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
  $query = mysql_query('
    SELECT
      id, type
    FROM 
      spr_relatives
    WHERE
      from_obj = "'.$from.'" AND
      to_obj = "'.$to.'"
    ORDER BY
      type
  ');
  if (mysql_num_rows($query) == 1) {
    $sel = ' selected';
  }
  echo '<select name="rel_type" class="relatives_list" req="true">';
  if (mysql_num_rows($query) > 1) echo '<option value=""></option>';
  while ($result = mysql_fetch_array($query)) {
    if ($result['id'] == $sel_relative) {
      echo '<option value="'.$result['id'].'" selected>'.$result['type'].'</option>';
    } else {
      echo '<option value="'.$result['id'].'" '.$sel.'>'.$result['type'].'</option>';
    }
  }
  echo '</select>';
}


//выбор объектов
function sel_objects($object, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      o.id,
      o.object,
      COUNT(DISTINCT rel.id) as cnt
    FROM
      spr_objects as o
    LEFT JOIN
      l_relatives as rel ON
        (rel.from_obj = "'.$object.'" AND
         rel.from_obj_type = "'.$obj_type.'" AND
         rel.to_obj_type = o.id)
          OR
        (rel.to_obj = "'.$object.'" AND
         rel.to_obj_type = "'.$obj_type.'"AND
         rel.from_obj_type = o.id)
    GROUP BY
      o.id
    ORDER BY
      o.id
  ');
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
  ); // нумеровал для наглядности
  $canceled = array(2, 3); // отключенные объекты
  if ($obj_type == 1) $canceled = array(3);
  if ($obj_type == 2) $canceled = array(2, 3);
  if ($obj_type == 4) $canceled = array(5, 3);
  if ($obj_type == 5) $canceled = array(4, 7, 8, 9, 10, 3);
  if ($obj_type == 6) $canceled = array(3);
  if ($obj_type == 7) $canceled = array(5, 3);
  if ($obj_type == 8) $canceled = array(5, 3);
  if ($obj_type == 9) $canceled = array(5, 3);
  if ($obj_type == 10) $canceled = array(5, 3);
  while ($result = mysql_fetch_assoc($query)) {
    if (!in_array($result["id"], $canceled)) {
      $obj[] = $result;
    }
  }
  $colls = 3; // количество столбцов
  $rows = ceil(count($obj)/$colls); // вычисляем количество элементов в столбец
  $i = 0;
  for ($c = 1; $c <= $colls; $c++) {
    echo '<ul class="objects_list_coll">';
    for ($r = 1; $r <= $rows; $r++) {
      if ($i < count($obj)) {
        $cnt = ($obj[$i]["cnt"] > 0) ? '['.$obj[$i]["cnt"].'] ' : '[&nbsp;&nbsp;] ';
        echo '<li class="objects_list_item"><a href="#" id="'.$forms_list[$obj[$i]["id"]].'">'.$cnt.$obj[$i]["object"].'</a></li>';
        $i++;
      }
    }
    echo '</ul>';
  }
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


//формирование списка платежных интернет систем
function marking($type, $sel = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, marking
    FROM 
      spr_marking
    WHERE
      obj_type = "'.$type.'"
    ORDER BY
      marking
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

//формирование списка типов телефонов
function tel_types($type = '') {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, type
    FROM 
      spr_tel_types
  ');
  echo '<select name="type">';
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

//-------- проверка наличия связи от/к обеъктов --------//
function check_relation ($from_obj, $from_obj_type, $to_obj, $to_obj_type, $type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      *
    FROM
      l_relatives
    WHERE
      from_obj = "'.$from_obj.'" AND
      from_obj_type = "'.$from_obj_type.'" AND
      to_obj = "'.$to_obj.'" AND
      to_obj_type = "'.$to_obj_type.'" AND
      type = "'.$type.'"
  ');
  if (mysql_num_rows($query) > 0) {
    return true;
  } else {
    return false;
  }
}
//-------- проверка наличия связи от/к обеъктов --------//

//-------- ввод связи от/к обеъктов --------//
function add_relation($from_obj, $from_obj_type, $to_obj, $to_obj_type, $type) {
  require_once('connection.php');
  $activity_id = 0;
  if (isset($_SESSION['activity_id'])) {
    $activity_id = $_SESSION['activity_id'];
  }
  mysql_query('
    INSERT INTO
      l_relatives(from_obj, from_obj_type, to_obj, to_obj_type, type,
        create_date, create_time, active_id)
    VALUES
      ("'.$from_obj.'", "'.$from_obj_type.'", "'.$to_obj.'", "'.$to_obj_type.'", "'.$type.'", current_date, current_time, "'.$activity_id.'")
  ');
}
//-------- ввод связи от/к обеъктов --------//


//-------- ввод объекта --------//
function add_object($obj) {
  require_once('connection.php');
  $activity_id = 0;
  if (isset($_SESSION['activity_id'])) {
    $activity_id = $_SESSION['activity_id'];
  }
  switch (substr($obj, 0, 2)) {
    case 'o_':
      $query = '
        INSERT INTO
          '.$obj.'(create_date, create_time, active_id)
        VALUES (current_date, current_time, "'.$activity_id.'")';
      break;
    case 'l_':
      $query = '
        INSERT INTO
          '.$obj.'
        VALUES ()';
      break;
  }
  if (mysql_query($query)) {
    return mysql_insert_id();
  } else {
    return $obj.' insert error: '.mysql_error();
  }
}
//-------- ввод объекта --------//


//-------- обновление объекта --------//
function update_object($table, $id, $array){
  require_once('connection.php');
  $error = '';
  $items = array();
  foreach ($array as $k => $v) {
    if (strpos($k, 'date') !== false) {
      if ($v != '') $v = date('Y-m-d', strtotime($v));
    }
    if (strpos($k, 'number') !== false) {
      if ($v != '') $v = str_replace(array('(', ')', '-', '_'), '', $v);
    }
    $v == '' ? $v = 'NULL' : $v = '"'.mysql_real_escape_string($v).'"';
    $items[] = '`'.$k.'` = '.$v;
  }
  $upd = '';
  if (substr($table, 0, 2) == 'o_') {
    $activity_id = 0;
    if (isset($_SESSION['activity_id'])) {
      $activity_id = $_SESSION['activity_id'];
    }
    $upd = ', `update_date` = current_date, `update_time` = current_time, `update_active_id` = "'.$activity_id.'"';
  }
  mysql_query('
    UPDATE
      '.$table.'
    SET
      '.implode(', ', $items).'
      '.$upd.'
    WHERE
      id = '.$id.'
  ') or $error = 'Ошибка обновления таблицы '.$table.' (объект id = '.$id.'): '.mysql_error();
  if ($error) return $error;
}
//-------- обновление объекта --------//


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

//-------- ввод запросов/ответов --------//
function add_request($array) {
  require_once('connection.php');
  
  mysql_query('
    
  ');
}
//-------- ввод запросов/ответов --------//

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
function search_telephone($num) {
  $num = str_replace(array('(', ')', '-', '_'), '', $num);
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      id, `number`
    FROM
      o_telephone
    WHERE
      `number` LIKE "'.$num.'%"
  ') or die(mysql_error());
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
  $query = mysql_query('
    SELECT
      l.`id`,
      l.`surname`,
      l.`name`,
      l.`fath_name`,
      DATE_FORMAT(l.`borth`, "%d.%m.%Y") as `borth`,
      sr.`type`
    FROM
      l_relatives as r
    LEFT JOIN
      `lico` as l ON
        (CASE
          WHEN ('.$obj_type.' < 1) THEN r.to_obj
          WHEN ('.$obj_type.' > 1) THEN r.from_obj
          WHEN ('.$obj_type.' = 1) THEN
            IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
        END) = l.id
    LEFT JOIN
      `spr_relatives` as sr ON
        r.`type` = sr.`id`
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "1")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "1")
  ') or die(mysql_error());
  $ret = '';
  $ret .= '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="'.CRIMES.'face.php?face_id='.$result["id"].'">'.mb_convert_case($result["surname"].' '.$result["name"].' '.$result["fath_name"], MB_CASE_TITLE, "UTF-8").' '.$result["borth"].' г.р.</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных лиц --------//Карпиков Геннадий Николаевич 10.07.1961 г.р.


//-------- список связанных событий --------//
function related_events($obj, $obj_type) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      e.id, o.ovd, e.kusp_num,
      DATE_FORMAT(e.kusp_date, "%d.%m.%Y") as `kusp_date`,
      e.crim_case,
      DATE_FORMAT(e.crim_case_date, "%d.%m.%Y") as `crim_case_date`,
      DATE_FORMAT(s.date, "%d.%m.%Y") as `date`,
      sr.`type`
    FROM
      l_relatives as r
    LEFT JOIN
      o_event as e ON
        (CASE
          WHEN ('.$obj_type.' < 2) THEN r.to_obj
          WHEN ('.$obj_type.' > 2) THEN r.from_obj
          WHEN ('.$obj_type.' = 2) THEN
            IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
        END) = e.id
    LEFT JOIN
      spr_relatives as sr ON
        r.`type` = sr.id
    LEFT JOIN
      spr_ovd as o ON
        e.ovd_id = o.id_ovd
    LEFT JOIN
      l_suspension as s ON
        e.id = s.event_id AND
        s.resume_date IS NULL
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "2")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "2")
    GROUP BY
      e.id
  ') or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $str = array();
      if ($result["ovd"] != "") $str[] = $result["ovd"];
      if ($result["kusp_num"] != "") $str[] = ' КУСП № '.$result["kusp_num"].' от '.$result["kusp_date"];
      if ($result["crim_case"] != "") $str[] = ' у/д № '.$result["crim_case"];
      if ($result["date"] != "") $str[] = ' (приостановлено '.$result["date"].')';
      $ret .= '<li>'.$result["type"].' &ndash; <a href="'.CRIMES.'event.php?event_id='.$result["id"].'">'.implode(', ', $str).'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных событий --------//


//-------- список связанных документов --------//
function related_documents($obj, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      d.id,
      sr.`type` as rel_type,
      sd.type as doc_type,
      d.`serial`,
      d.`number`
    FROM
      l_relatives as r
    LEFT JOIN
      o_documents as d ON
        (CASE
          WHEN ('.$obj_type.' < 4) THEN r.to_obj
          WHEN ('.$obj_type.' > 4) THEN r.from_obj
          WHEN ('.$obj_type.' = 4) THEN
            IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
        END) = d.id
    LEFT JOIN
      spr_relatives as sr ON
        sr.id = r.type
    LEFT JOIN
      spr_documents as sd ON
        sd.id = d.`type`
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "4")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "4")
  ');
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["rel_type"].' &ndash; <a href="'.CRIMES.'document.php?doc_id='.$result["id"].'">'.$result["doc_type"].': '.$result["serial"].'-'.$result["number"].'</a></li>';
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
      m.id,
      m.`IMEI`,
      sr.`type` as rel_type
    FROM
      l_relatives as r
    LEFT JOIN
      o_mobile_device as m ON
        m.id = r.to_obj OR
        m.id = r.from_obj
    LEFT JOIN
      spr_relatives as sr ON
        sr.id = r.type
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "5")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "5")
  ');
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["rel_type"].' &ndash; <a href="'.CRIMES.'device.php?dev_id='.$result["id"].'">'.$result["IMEI"].'</a></li>';
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
  $query = mysql_query('
    SELECT
      sr.`type`,
      t.`id`,
      t.`number`
    FROM
      l_relatives as r
    JOIN
      o_telephone as t ON
        t.id = (
          CASE
            WHEN ('.$obj_type.' < 6) THEN r.to_obj
            WHEN ('.$obj_type.' > 6) THEN r.from_obj
            WHEN ('.$obj_type.' = 6) THEN
              IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
          END)
    JOIN
      spr_relatives as sr ON
        sr.id = r.`type`
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "6")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "6")
  ');
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="'.CRIMES.'telephone.php?tel_id='.$result["id"].'">'.$result["number"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных телефонов --------//

//-------- список связанных банковских счетов --------//
function related_bank_accounts($obj, $obj_type) {
  require_once('connection.php');
  $query = mysql_query('
    SELECT
      b.`id`,
      b.`number`,
      sr.`type`
    FROM
      l_relatives as r
    LEFT JOIN
      spr_relatives as sr ON
        sr.id = r.type
    LEFT JOIN
      o_bank_account as b ON
        b.id = (
          CASE
            WHEN ('.$obj_type.' < 7) THEN r.to_obj
            WHEN ('.$obj_type.' > 7) THEN r.from_obj
            WHEN ('.$obj_type.' = 7) THEN
              IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
          END
        )
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "7")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "7")
  ') or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="'.CRIMES.'bank_account.php?acc_id='.$result["id"].'">'.$result["number"].'</a></li>';
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
  $query = mysql_query('
    SELECT
      c.`id`,
      c.`number`,
      sr.`type`
    FROM
      l_relatives as r
    LEFT JOIN
      o_bank_card as c ON
        c.id = (
          CASE
            WHEN ('.$obj_type.' < 8) THEN r.to_obj
            WHEN ('.$obj_type.' > 8) THEN r.from_obj
            WHEN ('.$obj_type.' = 8) THEN
              IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
          END
        )
    LEFT JOIN
      spr_relatives as sr ON
        sr.id = r.type
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "8")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "8")
  ') or die(mysql_error());
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="'.CRIMES.'bank_card.php?card_id='.$result["id"].'">'.$result["number"].'</a></li>';
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
  $query = mysql_query('
    SELECT
      w.`id`,
      w.`number`,
      sr.`type`
    FROM
      l_relatives as r
    LEFT JOIN
      o_wallet as w ON
        w.id = (
          CASE
            WHEN ('.$obj_type.' < 9) THEN r.to_obj
            WHEN ('.$obj_type.' > 9) THEN r.from_obj
            WHEN ('.$obj_type.' = 9) THEN
              IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
          END
        )
    LEFT JOIN
      spr_relatives as sr ON
        sr.id = r.type
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "9")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "9")
  ');
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="'.CRIMES.'e-wallet.php?wall_id='.$result["id"].'">'.$result["number"].'</a></li>';
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
  $query = mysql_query('
    SELECT
      m.`id`,
      m.`name`,
      sr.`type`
    FROM
      l_relatives as r
    LEFT JOIN
      o_mail as m ON
        m.id = (
          CASE
            WHEN ('.$obj_type.' < 10) THEN r.to_obj
            WHEN ('.$obj_type.' > 10) THEN r.from_obj
            WHEN ('.$obj_type.' = 10) THEN
              IF(r.to_obj = '.$obj.', r.from_obj, r.to_obj)
          END
        )
    LEFT JOIN
      spr_relatives as sr ON
        sr.id = r.type
    WHERE
      (r.to_obj = "'.$obj.'" AND
       r.to_obj_type = "'.$obj_type.'" AND
       r.from_obj_type = "10")
         OR
      (r.from_obj = "'.$obj.'" AND
       r.from_obj_type = "'.$obj_type.'" AND
       r.to_obj_type = "10")
  ');
  $ret = '<legend>Связанные объекты:</legend>';
  if (mysql_num_rows($query) > 0) {
    $ret .= '<ul class="related_objects_list">';
    while ($result = mysql_fetch_assoc($query)) {
      $ret .= '<li>'.$result["type"].' &ndash; <a href="'.CRIMES.'e-mail.php?mail_id='.$result["id"].'">'.$result["name"].'</a></li>';
    }
    $ret .= '</ul>';
  } else {
    $ret .= '<span>Нет связанных объектов.</span>';
  }
  return $ret;
}
//-------- список связанных почтовых ящиков --------//

//-------- определение браузера --------//
function user_browser($agent) {
  preg_match("/(MSIE|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| )([0-9.]+)/", $agent, $browser_info);
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
  if ($browser == 'Opera' && $version == '9.80') return 'Opera '.substr($agent,-5);
  if ($browser == 'Version') return 'Safari '.$version;
  if (!$browser && strpos($agent, 'Gecko')) return 'Browser based on Gecko';
  return $browser.' '.$version;
}
//-------- определение браузера --------//

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
  require($connection);
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
	$db = mysql_connect('localhost', '...', '...');
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

function selectRefuseYears() {
  require('connection.php');
  $query = mysql_query('
    SELECT
      year(data_resh) as year
    FROM
      otkaz
    WHERE
      deleted = 0
    GROUP BY
      year(data_resh)
    ORDER BY
      data_resh ASC
  ');
  $resArray = array();
  if ($query) {
    while ($result = mysql_fetch_array($query)) {
      $resArray[] = $result['year'];
    }
  }
  return $resArray;
}

// ******** последняя дата выгрузки ******** //
function lastSwap($type) {
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
    LIMIT 1
  ');
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
      MAX(endTime) as time,
      endDate as date
    FROM
      leg_swap_protocol
    WHERE
      id_ovd = '.$ovd.' AND
      endDate = (
        SELECT
          MAX(endDate)
        FROM
          leg_swap_protocol
        WHERE
          id_ovd = '.$ovd.'
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
?>
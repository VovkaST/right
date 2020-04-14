<?php
header('Content-Type: text/html; charset=utf-8');
$need_auth = 0;
if (!isset($_COOKIE['none_auth'])) {
  require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
} else {
  session_start();
  require('d:/www.sites/const.php');
  require(KERNEL.'functions.php');
  $sess_id = session_id();
}

function bp(){
  if (isset($_POST["bp"]) && $_POST["bp"] == 1) {
    $_SESSION['refusal']['bp'] = $_POST["bp"];
  } 
  if (isset($_POST["ovd_otkaz"]) && (!isset($_POST["bp"]))) {
    if (isset($_SESSION['refusal']['bp'])) {
      unset($_SESSION['refusal']['bp']);
    }
  }
}

bp();

//если с формы загрузки файла
if (isset($_FILES["UpLoadFile"])) {
  $file_size = $_FILES["UpLoadFile"]["size"];
  $file_name = $_FILES["UpLoadFile"]["name"];
  $file_tmp_name = $_FILES["UpLoadFile"]["tmp_name"];
  $file_type = substr(strrchr($file_name, "."), 1);
  if (in_array($file_type, $file_type_array)) {
    if ($file_size != "0" && $file_size < "2097152") {
      if (is_uploaded_file($file_tmp_name)) {
        if (copy($file_tmp_name, $_SESSION['dir_session'].uniqid('ref_'))) {
          $_SESSION["refusal"]["uploaded_file"] = $file_name;
          if (strlen($file_name) > 33) {
            $file_name = mb_strcut($file_name, 0, 50, 'UTF-8').'...'.$file_type;
          }
          echo '<div id="file">'.$file_name.'</div>';
        } else {
          echo '<div id="error"><p>Ошибка копирования файла!</p></div>';
        }
      } else {
        echo '<div id="error"><p>Ошибка загрузки файла!</p></div>';
      }
    } else {
      echo '<div id="error"><p>Файл имеет 0 размер или больше 2 мб!</p></div>';
    }
  } else {
    echo '<div id="error">Недопустимый тип файла!</div>';
  }
}

function send_light_fields($field, $par, $handling = 0) {
  if ($handling) {
    $field = htmlspecialchars($field);
    $field = mb_convert_case($field, MB_CASE_TITLE, "UTF-8");
  }
  if (!empty($field)) {
    $_SESSION['refusal'][$par] = $field;
  } else {
    if (isset($_SESSION['refusal'][$par])) {
      unset($_SESSION['refusal'][$par]);
    }
  }
}
//посылка ОВД
if (isset($_POST["ovd_otkaz"])) {
  send_light_fields($_POST["ovd_otkaz"], 'ovd');
}

//посылка статуса
if (isset($_POST["status"])) {
  send_light_fields($_POST["status"], 'status');
}

//посылка службы
if (isset($_POST["slujba"])) {
  send_light_fields($_POST["slujba"], 'slujba');
}

//посылка фамилии
if (isset($_POST["surname"])) {
  send_light_fields($_POST["surname"], 'surname', 1);
}

//посылка имени
if (isset($_POST["name"])) {
  send_light_fields($_POST["name"], 'name', 1);
}

//посылка отчества
if (isset($_POST["father_name"])) {
  send_light_fields($_POST["father_name"], 'father_name', 1);
}

//посылка УПК
if (isset($_POST["upk"])) {
  send_light_fields($_POST["upk"], 'upk');
}

//посылка даты отказного
function send_date($otk_date) {
  $otk_date = htmlspecialchars($otk_date);
  if (!empty($otk_date)) {
    if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $otk_date)) { //если формат даты допустим
      if (strtotime($otk_date) > strtotime('now')) { // дата больше текущей
        echo "Вводимая дата не может быть больше текущей";
      }
      else {
        $_SESSION['refusal']['otk_date'] = $otk_date;
      }
    }
    else {
        echo 'Дату необходимо вводить в формате "00.00.0000"';
    }
  } else {
    if (isset($_SESSION['refusal']['otk_date'])) {
      unset($_SESSION['refusal']['otk_date']);
    }
  }
}
if (isset($_POST["otk_date"])) {
  send_date($_POST["otk_date"]);
}

//добавление КУСП
function send_KUSP($kusp_ovd, $kusp_num, $kusp_date) {
  require(KERNEL.'connection.php');
  $kusp_num = htmlspecialchars($kusp_num);
  $kusp_date = htmlspecialchars($kusp_date);
  $ovd = getOvdName($kusp_ovd)[0];
  $kusp = array("{$kusp_num}", "{$kusp_date}");
  if (isset($_SESSION['refusal']['kusp'])) {
    $is = in_multiarray($kusp, $_SESSION['refusal']['kusp']);
    if ($is === false) {
      $_SESSION['refusal']['kusp'][] = array(
          "kusp_ovd" => $kusp_ovd, 
          "ovd" => $ovd, 
          "kusp_num" => $kusp_num, 
          "kusp_date" => $kusp_date
        );
      echo added_kusp();
    } else {
      echo added_kusp();
      echo '<div class="error" id="kusp_error">';
      echo 'Связь с данным КУСП уже установлена!';
      echo '</div>';
    }
  } else {
    $_SESSION['refusal']['kusp'][] = array(
        "kusp_ovd" => $kusp_ovd, 
        "ovd" => $ovd, 
        "kusp_num" => $kusp_num, 
        "kusp_date" => $kusp_date
      );
    echo added_kusp();
  }
}
if (!empty($_GET["kusp_num"])) {
  send_KUSP($_GET["kusp_ovd"], $_GET["kusp_num"], $_GET["kusp_date"]);
}

//удаление КУСП
if (isset($_POST["kusp_delete"])) {
  unset($_SESSION['refusal']['kusp'][$_POST["kusp_delete"]]);
  if(!count($_SESSION['refusal']['kusp'])) {
    unset($_SESSION['refusal']['kusp']);
  } else {
    echo added_kusp();
  }
}


//введенные статьи
function send_criminal($criminal_st) {
  $criminal_st = htmlspecialchars($criminal_st);
  //выбираем строку ОВД
  require(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      st
    FROM
      spr_uk
    WHERE
      id_uk = "'.$criminal_st.'"
  ');
  while ($result = mysql_fetch_assoc($query)) {
    $st_str = $result["st"];
  }
  if (isset($_SESSION['refusal']['uk'])) {
    $is = in_multiarray($criminal_st, $_SESSION['refusal']['uk'], true);
    if ($is === false) {
      $_SESSION['refusal']['uk'][] = array(
        "criminal_st" => $criminal_st,
        "criminal_st_str" => $st_str
      );
      echo added_criminal();
    } else {
      echo added_criminal();
      echo '<div class="error" id="criminal_error">';
      echo 'Связь с данной статьей уже установлена!';
      echo '</div>';
    }    
  } else {
    $_SESSION['refusal']['uk'][] = array(
        "criminal_st" => $criminal_st,
        "criminal_st_str" => $st_str
      );
      echo added_criminal();
  } 
}
if(!empty($_POST["article_id"])) {
  send_criminal($_POST["article_id"]);
}

//удаление статей
if (isset($_POST["criminal_delete"])) {
  unset($_SESSION['refusal']['uk'][$_POST["criminal_delete"]]);
  if(!count($_SESSION['refusal']['uk'])) {
    unset($_SESSION['refusal']['uk']);
  } else {
    echo added_criminal();
  }
}


//ввод лиц
if (isset($_POST["av_f"])) {
  $av_f = $_POST["av_f"];
  $av_i = $_POST["av_i"];
  $av_o = $_POST["av_o"];
  $av_dr = $_POST["av_dr"];
  if (isset($_POST["declarer"]) && !isset($_POST["victim"])) {
    $relative_id = "1";
    $relative = "Заявитель";
  } 
  elseif (isset($_POST["victim"]) && !isset($_POST["declarer"])) {
    $relative_id = "2";
    $relative = "Потерпевший";
  }
  elseif (isset($_POST["missing"])) {
    $relative_id = "5";
    $relative = "Пропавший без вести";
  }
  elseif (isset($_POST["declarer"]) && isset($_POST["victim"])) {
    $relative_id = "3";
    $relative = "Заявитель, потерпевший";
  }
  $man = array("{$av_f}", "{$av_i}", "{$av_o}", "{$av_dr}");  //собираем в массив ФИО, д.р.
  
  /*
  if (isset($_SESSION['refusal']['offender'])) { //если есть массив причастных лиц
    $match_offender = in_multiarray($man, $_SESSION['refusal']['offender'], true); //проверяем на совпадение
    if($match_offender !== false) { //если совпадения есть
      if (isset($_SESSION['refusal']['av'])) { //если есть причастные лица
        echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
      }
      if (isset($_SESSION['refusal']["org"])) {
        echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
      }
      echo '<div class="error" id="av_error">'; // выдаем предупреждение
      echo 'Связь с данным лицом уже установлена (Причастное лицо)!';
      echo '</div>';
      die(); // прерываем скрипт
    }
  }
  */
  
  $man_id = search_men($av_f, $av_i, $av_o, $av_dr); //ищем лицо в БД  иполучаем его id
  if ($man_id === false) { //если лица нет
    $man_id = "NULL"; // его id = NULL
  }  
  if (isset($_SESSION['refusal']['av'])) { //если уже есть связанные лица
    $match = in_multiarray($man, $_SESSION['refusal']['av'], true); //проверяем на совпадение
    if ($match === false) { //если совпадений нет
      $_SESSION['refusal']['av'][] = array( //записываем массив в сессию
          "id" => $man_id,
          "surname" => mb_convert_case($av_f, MB_CASE_UPPER, "UTF-8"),
          "name" => mb_convert_case($av_i, MB_CASE_UPPER, "UTF-8"),
          "fath_name" => mb_convert_case($av_o, MB_CASE_UPPER, "UTF-8"),
          "borth" => $av_dr,
          "relative_id" => $relative_id,
          "relative" => $relative
        );
      echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
      if (isset($_SESSION['refusal']["org"])) {
        echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
      }
    }
    else { //если есть совпадения
      echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //просто собираем список в визуальное представление
      if (isset($_SESSION['refusal']["org"])) {
        echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
      }
      echo '<div class="error" id="av_error">'; // выдаем предупреждение
      echo 'Связь с данным лицом уже установлена ('.$_SESSION['refusal']['av'][$match]["relative"].')!';
      echo '</div>';
    }
  } else { //если нет связанных лиц
    $_SESSION['refusal']['av'][] = array( //записываем массив в сессию
        "id" => $man_id,
        "surname" => mb_convert_case($av_f, MB_CASE_UPPER, "UTF-8"),
        "name" => mb_convert_case($av_i, MB_CASE_UPPER, "UTF-8"),
        "fath_name" => mb_convert_case($av_o, MB_CASE_UPPER, "UTF-8"),
        "borth" => $av_dr,
        "relative_id" => $relative_id,
        "relative" => $relative
      );
    echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
    if (isset($_SESSION['refusal']["org"])) {
      echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
    }
  }
}
//удаление связи с лицом
if (isset($_POST["face_delete"])) {
  unset($_SESSION['refusal']['av'][$_POST["face_delete"]]);
  if(!count($_SESSION['refusal']['av'])) {
    unset($_SESSION['refusal']['av']);
  } else {
    echo added_faces($_SESSION['refusal']['av'], "added_av_str");
  }
  if (isset($_SESSION['refusal']["org"])) {
    echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
  }
}


//ввод организации
if (isset($_POST["av_org"])) {
  $org = htmlspecialchars($_POST["av_org"]);
  if (isset($_POST["declarer"]) && !isset($_POST["victim"])) {
    $relative_id = "1";
    $relative = "Заявитель";
  } 
  if (isset($_POST["victim"]) && !isset($_POST["declarer"])) {
    $relative_id = "2";
    $relative = "Потерпевший";
  }
  if (isset($_POST["declarer"]) && isset($_POST["victim"])) {
    $relative_id = "3";
    $relative = "Заявитель, потерпевший";
  }
  if (isset($_SESSION['refusal']["org"])) {
    $match_offender = in_multiarray($org, $_SESSION['refusal']['org'], true); //проверяем на совпадение
    if($match_offender !== false) { //если совпадения есть
      if (isset($_SESSION['refusal']['av'])) { //если есть причастные лица
        echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
      }
      if (isset($_SESSION['refusal']["org"])) {
        echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
      }
      echo '<div class="error" id="av_error">'; // выдаем предупреждение
      echo 'Связь с данной организацией уже установлена ('.$_SESSION['refusal']['org'][$match_offender]["relative"].')!';
      echo '</div>';
      die(); // прерываем скрипт
    } else {
      $_SESSION['refusal']['org'][] = array( //записываем массив в сессию
          "org_name" => mb_convert_case($org, MB_CASE_UPPER, "UTF-8"),
          "relative_id" => $relative_id,
          "relative" => $relative
        );
      if (isset($_SESSION['refusal']['av'])) { //если есть причастные лица
        echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
      }
      if (isset($_SESSION['refusal']["org"])) {
        echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
      }
    }
  } else { //если нет связанных организаций
    $_SESSION['refusal']['org'][] = array( //записываем массив в сессию
        "org_name" => mb_convert_case($org, MB_CASE_UPPER, "UTF-8"),
        "relative_id" => $relative_id,
        "relative" => $relative
      );
    if (isset($_SESSION['refusal']['av'])) { //если есть причастные лица
      echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
    }
    if (isset($_SESSION['refusal']["org"])) {
      echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
    }
  }
}

//удаление организации
if (isset($_POST["org_delete"])) {
  unset($_SESSION['refusal']['org'][$_POST["org_delete"]]);
  if(!count($_SESSION['refusal']['org'])) {
    unset($_SESSION['refusal']['org']);
    if (isset($_SESSION['refusal']['av'])) { //если есть причастные лица
      echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
    }
  } else {
    if (isset($_SESSION['refusal']['av'])) { //если есть причастные лица
      echo added_faces($_SESSION['refusal']['av'], "added_av_str"); //собираем список в визуальное представление
    }
    echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
  }
}


//анонимка
if (isset($_POST["anonymous"])) {
  if ($_POST["anonymous"] == 1) {
    $_SESSION['refusal']["anonymous"] = 1;
  }
  if ($_POST["anonymous"] == 0) {
    unset($_SESSION['refusal']["anonymous"]);
  }
}

//рапорт сотрудника
if (isset($_POST["decl_emp"])) {
  if ($_POST["decl_emp"] == 1) {
    $_SESSION['refusal']["decl_emp"] = 1;
  }
  if ($_POST["decl_emp"] == 0) {
    unset($_SESSION['refusal']["decl_emp"]);
  }
}

//ввод причастных лиц
if(isset($_POST["offender_f"])) {
  $offender_f = $_POST["offender_f"];
  $offender_i = $_POST["offender_i"];
  $offender_o = $_POST["offender_o"];
  $offender_dr = $_POST["offender_dr"];
  $relative_id = "4";
  $relative = "Причастное лицо";
  $man = array($offender_f, $offender_i, $offender_o, $offender_dr);  //собираем в массив ФИО, д.р.
  
  /*
  if (isset($_SESSION['refusal']['av'])) { //если есть массив заявителей, потерпевших, БВП
    $match_av = in_multiarray($man, $_SESSION['refusal']['av'], true); //проверяем на совпадение
    if($match_av !== false) { //если совпадения есть
      if (isset($_SESSION['refusal']['offender'])) { //если есть причастные лица
        echo added_faces($_SESSION['refusal']['offender'], "added_offender_str"); //собираем список в визуальное представление
      }
      echo '<div class="error" id="offender_error">'; // выдаем предупреждение
      echo 'Связь с данным лицом уже установлена ('.$_SESSION['refusal']['av'][$match_av]["relative"].')!';
      echo '</div>';
      die(); // прерываем скрипт
    }
  }
  */
  
  $man_id = search_men($offender_f, $offender_i, $offender_o, $offender_dr); //ищем лицо в БД  иполучаем его id
  if ($man_id === false) { //если лица нет
    $man_id = "NULL"; // его id = NULL
  }
  if (isset($_SESSION['refusal']['offender'])) { //если уже есть связанные лица
    $match = in_multiarray($man, $_SESSION['refusal']['offender'], true); //проверяем на совпадение
    if ($match === false) { //если совпадений нет
      $_SESSION['refusal']['offender'][] = array( //записываем массив в сессию
          "id" => $man_id,
          "surname" => mb_convert_case($offender_f, MB_CASE_UPPER, "UTF-8"),
          "name" => mb_convert_case($offender_i, MB_CASE_UPPER, "UTF-8"),
          "fath_name" => mb_convert_case($offender_o, MB_CASE_UPPER, "UTF-8"),
          "borth" => $offender_dr,
          "relative_id" => $relative_id,
          "relative" => $relative
        );
      echo added_faces($_SESSION['refusal']['offender'], "added_offender_str"); //собираем список в визуальное представление
    }
    else { //если есть совпадения
      echo added_faces($_SESSION['refusal']['offender'], "added_offender_str"); //просто собираем список в визуальное представление
      echo '<div class="error" id="offender_error">'; // выдаем предупреждение
      echo 'Связь с данным причастным лицом уже установлена!';
      echo '</div>';
    }
  }
  else { //если нет связанных лиц
    $_SESSION['refusal']['offender'][] = array( //записываем массив в сессию
        "id" => $man_id,
        "surname" => mb_convert_case($offender_f, MB_CASE_UPPER, "UTF-8"),
        "name" => mb_convert_case($offender_i, MB_CASE_UPPER, "UTF-8"),
        "fath_name" => mb_convert_case($offender_o, MB_CASE_UPPER, "UTF-8"),
        "borth" => $offender_dr,
        "relative_id" => $relative_id,
        "relative" => $relative
      );
    echo added_faces($_SESSION['refusal']['offender'], "added_offender_str"); //собираем список в визуальное представление
  }
}
//удаление связи с причастным лицом
if (isset($_POST["offender_delete"])) {
  unset($_SESSION['refusal']['offender'][$_POST["offender_delete"]]);
  if(!count($_SESSION['refusal']['offender'])) {
    unset($_SESSION['refusal']['offender']);
  } else {
    echo added_faces($_SESSION['refusal']['offender'], "added_offender_str");
  }
}

//СФОРМИРОВАТЬ
if (isset($_POST["reg"])) {
  @$otkazLog = writeSessionString($_SESSION["activity_id"]);
  $file_ready = $form_ready = $KUSP_ready = $criminal_ready = $av_ready = $offender_ready = false;
  $error = "";
  if (isset($_SESSION["refusal"]["uploaded_file"])) {
    $file_ready = true;
  } else {
    $error[] = "Не выбран файл отказного";
  }
  if (
    isset($_SESSION['refusal']['status']) && 
    isset($_SESSION['refusal']['ovd']) && 
    isset($_SESSION['refusal']['slujba']) && 
    isset($_SESSION['refusal']['surname']) &&
    isset($_SESSION['refusal']['name']) &&
    isset($_SESSION['refusal']['father_name']) &&
    isset($_SESSION['refusal']['upk']) &&
    isset($_SESSION['refusal']['otk_date'])) {
      $form_ready = true;
  } else {
    $error[] = "Заполнены не все поля основных сведений об отказном";
  }
  if (isset($_SESSION['refusal']['kusp'])) {
    $KUSP_ready = true;
  } else {
    $error[] = "Не указаны КУСП";
  }
  if (isset($_SESSION['refusal']['bp'])) {
    $criminal_ready = true;
  } else {
    if (isset($_SESSION['refusal']['uk'])) {
      $criminal_ready = true;
    } else {
      $error[] = "Не указаны статьи УК РФ";
    }
  }
  
  if (isset($_SESSION['refusal']['av'])) { //проверяем список "заявитель/потерпевший/пропавший без вести"
    if (isset($_SESSION['refusal']['bp'])) { //если по БВП
      $is = in_multiarray("5", $_SESSION['refusal']['av'], true); //ищем такую связь
      if ($is !== false) { // если связь есть
        $av_ready = true; // то форма готова
      } else { // иначе
        $error[] = "Не указан без вести пропавший"; // ошибка
      }
    } else { // если не по БВП
      $av_ready = true;
    }
  }
  if (isset($_SESSION['refusal']['org'])) {
    $av_ready = true;
  }
  if (isset($_SESSION['refusal']["anonymous"])) {
    $av_ready = true;
  }
  if (isset($_SESSION['refusal']["decl_emp"])) {
    $av_ready = true;
  }
  if (!$av_ready) { // если списка нет
    $error[] = "Не указан заявитель/потерпевший/пропавший без вести"; // ошибка
  }
  
  
  if ((isset($_SESSION['refusal']['upk']) && $_SESSION['refusal']['upk'] == 1)) {
    $offender_ready = true;
  } else {
    if (isset($_SESSION['refusal']['offender'])) {
      $offender_ready = true;
    } else {
      $error[] = "Не указано причастное лицо";
    }
  }
  
  if ($file_ready && $form_ready && $KUSP_ready && $criminal_ready && $av_ready && $offender_ready) { //если все страница валидна
    require('reg.php');
  } else {
    echo implode(', ', $error);
  }
}

// ******** ввод долгов ******** //
if (!empty($_POST['debt_id'])) {
  require(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      ld.id_ovd,
      ld.emp_s,
      ld.emp_n,
      ld.emp_fn,
      ld.reg_num,
      DATE_FORMAT(ld.reg_date, "%d.%m.%Y") as reg_date,
      spr.id_slujba,
      DATE_FORMAT(ld.dec_date, "%d.%m.%Y") as dec_date,
      ld.article,
      ld.article_part,
      ld.article_note,
      ld.article_proc_par
    FROM
      leg_decisions as ld
    LEFT JOIN
      spr_slujba as spr ON
        spr.slujbaLeg = ld.emp_service
    WHERE
      ld.id = "'.$_POST['debt_id'].'"
    LIMIT 1
  ');
  $result = mysql_fetch_assoc($query);
  if (isset($_SESSION['refusal'])) {
    unset($_SESSION['refusal']);
  }
  if (!empty($result["article"])) {
    $article = $result["article"];
    if (!empty($result["article_note"])) {
      $article .= '.'.$result["article_note"];
    }
    if (!empty($result["article_part"])) {
      $article .= ' ч.'.$result["article_part"];
    }
  }
  $crimQuery = mysql_query('
    SELECT
      id_uk
    FROM
      spr_uk
    WHERE
      st = "'.$article.'"
  ');
  $crimRes = mysql_fetch_assoc($crimQuery);
  send_criminal($crimRes['id_uk']);
  send_light_fields($result["id_ovd"], 'ovd');
  send_light_fields($result["id_slujba"], 'slujba');
  send_light_fields($result["emp_s"], 'surname', 1);
  send_light_fields($result["emp_n"], 'name', 1);
  send_light_fields($result["emp_fn"], 'father_name', 1);
  send_date($result["dec_date"]);
  if (!empty($result["article_proc_par"])) {
    $proc = substr($result["article_proc_par"], 0, 1);
    if ($proc > 0 && $proc <= 6) {
      send_light_fields($proc, 'upk');
    }
  }
  send_KUSP($result["id_ovd"], $result["reg_num"], $result["reg_date"]);
}
// ^^^^^^^^ ввод долгов ^^^^^^^^ //
?>
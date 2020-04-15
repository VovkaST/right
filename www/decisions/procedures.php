<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$jsonERR = array();
$jsonData = true;
$json = $jsonMSG = '';

function delete_empty_array_elements($array) {
  foreach($array as $k => $v) {
    if (empty($v) || in_array($v, array('Имя', 'Фамилия', 'Отчество', 'Организация'))) unset($array[$k]);
  }
  return $array;
}

require_once('class.decision.php');


if (isset($_FILES['file_upload']) && !empty($_FILES['file_upload']) && ($_FILES['file_upload']['error'] == 0)) {
  $jsonData = false;
  $max_size = pow(2, 20) * 2; // 2 мегабайта
  $size = $_FILES['file_upload']['size'];
  $name = $_FILES['file_upload']['name'];
  $tmp_name = $_FILES['file_upload']['tmp_name'];
  if (Decision::correct_file_type($name)) {
    if (($size > '0') && ($size < $max_size)) {
      if (is_uploaded_file($tmp_name)) {
        if (empty($_SESSION['dir_session'])) {
          $_SESSION['dir_session'] = session_save_path()."_tmp_".session_id().'\\'; // временный каталог сессии
          if (!is_dir($_SESSION['dir_session'])) {
            mkdir($_SESSION['dir_session']); // создаем его
          }
        }
        if (!copy($tmp_name, $_SESSION['dir_session'].mb_convert_encoding($name, 'Windows-1251', 'UTF-8'))) {
          $jsonERR[] = 'Ошибка копирования файла!';
        } else {
          $_SESSION['decision']['data']['file_original'] = $name;
          $json['.file_block .added_row'] = '<span class="added_relation">'.$name.'<span class="delete_relation" group="file">&times;</span></span>';
          $json['.file_block .added_row'] .= '<div style="clear: left;"></div>';
          $add_function = '<script type="text/javascript">window.parent.$(\'.uploading_file_block input[type="file"]\').attr("disabled", "true");</script>';
          $add_function .= '<script type="text/javascript">window.parent.$(\'.uploading_file_block .file_upload\').attr("status", "disabled");</script>';
          $add_function .= '<script type="text/javascript">window.parent.$(\'.uploading_file_block span.file_input_value\').html("&nbsp;");</script>';
        }
      } else {
        $jsonERR[] = 'Ошибка загрузки файла!';
      }
    } else {
      $jsonERR[] = 'Файл имеет 0 размер или больше 2 мб!';
    }
  } else {
    $jsonERR[] = 'Неверный тип файла. Допустимы файлы "doc", "docx", "rtf".';
  }
  $add_function_req = '<script type="text/javascript">window.parent.$(\'.uploading_file_block .ajax_response_wait\').remove();</script>';
}

if (!empty($_POST['form_name'])) {
  switch($_POST['form_name']) {
    case 'main_form':
      if ($_POST['date'] == '__.__.____') $_POST['date'] = null;
      if (!empty($_POST['date'])) {
        $_POST['date'] = date('Y-m-d', strtotime($_POST['date']));
        if ((!empty($_SESSION['decision']['data']['date']) and $_SESSION['decision']['data']['date'] != $_POST['date']) or empty($_SESSION['decision']['data']['date'])) {
          if (strtotime($_POST['date']) > (strtotime(date('d.m.Y')))) {
            $jsonERR[] = 'Дата решения не может быть больше текущей даты!';
            break;
          }
          if (!empty($_SESSION['decision']['kusp']['list'])) {
            foreach($_SESSION['decision']['kusp']['list'] as $kusp) {
              if (strtotime($_POST['date']) < strtotime($kusp['date'])) {
                $jsonERR[] = 'Дата решения не может быть меньше даты регистрации КУСП!';
                if (!empty($_SESSION['decision']['data']['date'])) $_SESSION['decision']['data']['date'] = null;
                break;
              }
            }
            if (count($jsonERR)) break;
          }
          $dec_date = new DateTime($_POST['date']);
          $cur_date = new DateTime(date('d.m.Y'));
          $interval = $dec_date->diff($cur_date);
          if ($interval->days > 30) {
            $jsonMSG[] = 'Внимание! <br /> Вы указали дату, которая больше текущей на '.$interval->days.' '.case_of_word($interval->days, 'день').'. В случае, если она является ошибочной, возможно неверное выставление долгов по вводу.';
          }
        }
      }
      unset($_POST['form_name']);
      if (
        (!empty($_POST['emp_s']) and !preg_match('/^[а-яё \t]+$/iu', $_POST['emp_s'])) or
        (!empty($_POST['emp_n']) and !preg_match('/^[а-яё \t]+$/iu', $_POST['emp_n'])) or
        (!empty($_POST['emp_fn']) and !preg_match('/^[а-яё \t]+$/iu', $_POST['emp_fn']))
      ) {
        $jsonERR[] = 'Установочные данные вводятся ТОЛЬКО русскими буквами!';
        break;
      }
      $_POST['emp_s'] = htmlentities(preg_replace('/(Фамилия)/', '', $_POST['emp_s']));
      $_POST['emp_n'] = htmlentities(preg_replace('/(Имя)/', '', $_POST['emp_n']));
      $_POST['emp_fn'] = htmlentities(preg_replace('/(Отчество)/', '', $_POST['emp_fn']));
      $_SESSION['decision']['data'] = (empty($_SESSION['decision']['data'])) ? $_POST : array_merge($_SESSION['decision']['data'], $_POST);
      break;
    
    case 'kusp_form':
      if (empty($_POST['ovd']) or empty($_POST['kusp']) or empty($_POST['date'])) {
        $jsonERR[] = 'Не все поля заполнены.';
        break;
      }
      if (!is_numeric($_POST['kusp'])) {
        $jsonERR[] = 'КУСП указывается в числовом формате!<br />Каждый номер КУСП вводится отдельно!';
        break;
      }
      if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['date'])) { //если формат даты допустим
        if (strtotime($_POST['date']) > strtotime('now')) { // дата больше текущей
          $jsonERR[] = 'Вводимая дата не может быть больше текущей!';
          break;
        }
        if (!empty($_SESSION['decision']['data']['date']) and strtotime($_POST['date']) > strtotime($_SESSION['decision']['data']['date'])) {
          $jsonERR[] = 'Вводимая дата не может быть больше даты вынесения решения';
          break;
        }
        $kusp_date = new DateTime($_POST['date']);
        $cur_date = new DateTime(date('d.m.Y'));
        $interval = $kusp_date->diff($cur_date);
        if ($interval->days > 30) {
          $jsonMSG[] = 'Внимание! <br /> Вы указали дату, которая меньше текущей на '.$interval->days.' '.case_of_word($interval->days, 'день').'. В случае, если она является ошибочной, возможно неверное выставление долгов по вводу.';
        }
        $_POST['date'] = date('Y-m-d', strtotime($_POST['date']));
      } else {
        $jsonERR[] = 'Дату необходимо вводить в формате "'.date('d.m.Y', strtotime('now')).'"!';
        break;
      }
      
      unset($_POST['form_name']);
      if (!empty($_SESSION['decision']['kusp']['list'])) {
        foreach($_SESSION['decision']['kusp']['list'] as $kusp) {
          if (!count(array_diff_assoc($_POST, $kusp))) {
            $jsonERR[] = 'Связь с таким КУСП уже установлена!';
            break;
          }
        }
        if (count($jsonERR)) break;
      }
      
      $_SESSION['decision']['kusp']['list'][] = $_POST;
      $json['.messages_block .added_row'] = related_kusp($_SESSION['decision']['kusp']['list']);
      break;
      
    case 'uk_form':
      if (empty($_POST['uk'])) {
        $jsonERR[] = 'Не выбрана статья УК РФ!';
        break;
      }
      if (!empty($_SESSION['decision']['uk'])) {
        if (in_array($_POST['uk'], $_SESSION['decision']['uk']['list'])) {
          $jsonERR[] = 'Такая статья уже указана!';
          break;
        }
      }
      unset($_POST['form_name']);
      $_SESSION['decision']['uk']['list'][] = $_POST['uk'];
      sort($_SESSION['decision']['uk']['list'], SORT_NUMERIC);
      $json['.criminal_block .added_row'] = related_uk();
      break;
      
    case 'objects_form':
      if (empty($_POST['relative'])) {
        $jsonERR[] = 'Не указан тип связи!';
        break;
      }
      if ((empty($_POST['surname']) or (mb_strlen($_POST['surname'], 'UTF-8') < 2)) and empty($_POST['title'])) {
        $jsonERR[] = 'Не указан ни один объект!';
        break;
      }
      if (!empty($_POST['surname'])) {
        if (empty($_POST['name']) or empty($_POST['borth']) or (!empty($_POST['fath_name']) and mb_strlen($_POST['fath_name'], 'UTF-8') < 5)) {
          $jsonERR[] = 'Установочные данные указаны неполностью!';
          break;
        }
        if (
          !preg_match('/^[а-яё \t]+$/iu', $_POST['name']) or
          !preg_match('/^[а-яё \t]+$/iu', $_POST['surname']) or
          (!empty($_POST['fath_name']) and !preg_match('/^[а-яё \t]+$/iu', $_POST['fath_name']))
        ) {
          $jsonERR[] = 'Установочные данные вводятся ТОЛЬКО русскими буквами!';
          break;
        }
        if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['borth'])) { //если формат даты допустим
          if (strtotime($_POST['borth']) > strtotime('now')) { // дата больше текущей
            $jsonERR[] = 'Вводимая дата не может быть больше текущей!';
            break;
          }
          $b_date = new DateTime($_POST['borth']);
          $cur_date = new DateTime(date('d.m.Y'));
          $interval = $b_date->diff($cur_date);
          if ($interval->y > 100) {
            $jsonERR[] = 'Указанный Вами возраст составляет '.$interval->y.' '.case_of_word($interval->y, 'год').' и, по-видимому, является ошибочным.';
            break;
          }
        } else {
          $jsonERR[] = 'Дату необходимо вводить в формате "'.date('d.m.Y', strtotime('now')).'"!';
          break;
        }
        $rel = $_POST['relative'];
        unset($_POST['form_name'], $_POST['relative'], $_POST['title']);
        $_POST['surname'] = htmlentities(mb_convert_case($_POST['surname'], MB_CASE_UPPER, 'UTF-8'));
        $_POST['name'] = htmlentities(mb_convert_case($_POST['name'], MB_CASE_UPPER, 'UTF-8'));
        $_POST['fath_name'] = (empty($_POST['fath_name'])) ? '-' : htmlentities(mb_convert_case($_POST['fath_name'], MB_CASE_UPPER, 'UTF-8'));
        $_POST['borth'] = date('Y-m-d', strtotime($_POST['borth']));
        
        if (!empty($_SESSION['decision']['faces']['list'][$rel])) {
          foreach($_SESSION['decision']['faces']['list'][$rel] as $face) {
            if (!count(array_diff_assoc($_POST, $face))) {
              $jsonERR[] = 'Такая связь с этим лицом уже установлена!';
              break;
            }
          }
          if (count($jsonERR)) break;
        }
        $_SESSION['decision']['faces']['list'][$rel][] = $_POST;
      } else {
        if (!empty($_SESSION['decision']['organisations']['list'][$_POST['relative']])) {
          foreach($_SESSION['decision']['organisations']['list'][$_POST['relative']] as $org) {
            if ($org['title'] == $_POST['title']) {
              $jsonERR[] = 'Связь с такой организацией уже установлена!';
              break;
            }
          }
          if (count($jsonERR)) break;
        }
        $_SESSION['decision']['organisations']['list'][$_POST['relative']][] = array('id' => null, 'title' => htmlentities(mb_convert_case($_POST['title'], MB_CASE_UPPER, 'UTF-8')));
      }
      $json['.objects_block .added_row'] = related_faces_organisations();
      break;
      
    case 'registration':
      if (empty($_SESSION['decision']['data']['file_original'])) {
        $jsonERR[] = 'Не загружен электронный файл.';
        break;
      }
      if (!is_file($_SESSION['dir_session'].mb_convert_encoding($_SESSION['decision']['data']['file_original'], 'Windows-1251', 'UTF-8'))) {
        $jsonERR[] = 'Файл отсутствует на сервере. Возможно время сессии истекло. Обновите страницу и повторите ввод данных.';
        break;
      }
      
      if (empty($_SESSION['decision']['data']['date'])) $main[] = 'дата решения';
      if (empty($_SESSION['decision']['data']['status'])) $main[] = 'статус';
      if (empty($_SESSION['decision']['data']['ovd'])) $main[] = 'ОВД';
      if (empty($_SESSION['decision']['data']['service'])) $main[] = 'служба';
      if (empty($_SESSION['decision']['data']['emp_s']) or empty($_SESSION['decision']['data']['emp_n']) or empty($_SESSION['decision']['data']['emp_fn'])) $main[] = 'сотрудник, принявший решение';
      if (empty($_SESSION['decision']['data']['upk'])) $main[] = 'основание';
      
      if (!empty($main)) {
        $jsonERR[] = 'Не все поля раздела основных сведений заполнены ('.implode(', ', $main).')!';
        break;
      }
      
      if (empty($_SESSION['decision']['kusp']['list'])) {
        $jsonERR[] = 'Не заполнен раздел КУСП!';
        break;
      }
      
      if (!empty($_SESSION['decision']['data']['missed'])) {
        if (empty($_SESSION['decision']['faces']['list'][5])) {
          $jsonERR[] = 'Не указан без вести пропавший!';
          break;
        }
      } else {
        if (empty($_SESSION['decision']['uk']['list'])) {
          $jsonERR[] = 'Не указаны статьи УК РФ!';
          break;
        }
        if (!empty($_SESSION['decision']['faces']['list'][5])) {
          $jsonERR[] = 'Ошибочная связь "Без вести пропавший"!';
          break;
        }
      }
      
      if ((empty($_SESSION['decision']['data']['anonymous']) and empty($_SESSION['decision']['data']['declarer_employeer'])) and 
           (
             (empty($_SESSION['decision']['faces']['list'][1]) and empty($_SESSION['decision']['faces']['list'][3])) and
             (empty($_SESSION['decision']['organisations']['list'][1]) and empty($_SESSION['decision']['organisations']['list'][3]))
           )
         ) {
        $jsonERR[] = 'Не указан заявитель!';
        break;
      }
      
      if (
           empty($_SESSION['decision']['data']['missed']) and empty($_SESSION['decision']['data']['anonymous']) and empty($_SESSION['decision']['data']['declarer_employeer']) and
           (
             (empty($_SESSION['decision']['faces']['list'][2]) and empty($_SESSION['decision']['faces']['list'][3])) and
             (empty($_SESSION['decision']['organisations']['list'][2]) and empty($_SESSION['decision']['organisations']['list'][3]))
           )
         ) {
        $jsonERR[] = 'Не указан потерпевший!';
        break;
      }
      
      if ($_SESSION['decision']['data']['upk'] > 1 and empty($_SESSION['decision']['faces']['list'][4]) and empty($_SESSION['decision']['data']['missed'])) {
        $jsonERR[] = 'Не указано заподозренное лицо!';
        break;
      }
      
      $decision = new Decision();
      $decision->set_type(1);
      $decision->restore_from_session();
      $decision->registration();
      $json['.decision_block .registration_number_block'] = '<div class="opacity_back" href="index.php"></div><div class="info_box">
        <div class="block_header">Регистрационный номер электронного документа:</div>
        <div class="reg_number"><span>'.$decision->data['reg'].'</span></div>
        <div class="prim">Данный номер необходимо указать на титульном листе отказного материала.</div>
        <div class="popup_window_button_block"><div class="add_button_box" href="index.php">
          <div class="button_block"><span class="button_name">Ok</span></div>
        </div></div>
        </div>';
      unset($_SESSION['decision']);
      break;
  }
}

if (!empty($_POST['ajsrch'])) {
  switch($_POST['ajsrch']) {
    case 'employeer':
      unset($_POST['ajsrch']);
      $fields = delete_empty_array_elements($_POST);
      $like = array('emp_s', 'emp_n', 'emp_fn');
      $str = array();
      foreach($fields as $f => $v) {
        $str[] = 'd.`'.$f.'` '.((in_array($f, $like)) ? 'LIKE "'.$v.'%"' : '= '.$v);
      }
      require_once(KERNEL.'connection.php');
      $query = mysql_query('
        SELECT
          d.`emp_s`, d.`emp_n`, d.`emp_fn`,
          CONCAT(
            ", ", s.`slujba`, ", ", o.`ovd`
          ) as `ovd_str`
        FROM
          `l_decisions` as d
        JOIN
          `spr_slujba` as s ON
            s.`id_slujba` = d.`service`
        JOIN
          `spr_ovd` as o ON
            o.`id_ovd` = d.`ovd`
        WHERE
          '.implode(' AND ', $str).'
        GROUP BY
          d.`emp_s`, d.`emp_n`, d.`emp_fn`
      ');
      while ($result = mysql_fetch_assoc($query)) {
        $json[] = mb_convert_case($result['emp_s'].' '.$result['emp_n'].' '.$result['emp_fn'], MB_CASE_TITLE, 'UTF-8').$result['ovd_str'];
        $group_result[] = $result;
      }
      break;
      
    case 'man':
      unset($_POST['ajsrch']);
      if (!empty($_POST['id'])) unset($_POST['id']);
      $fields = delete_empty_array_elements($_POST);
      if (empty($fields['name']) and empty($fields['fath_name']) and empty($fields['borth'])) break;
      $str = array();
      foreach ($fields as $f => $v) {
        switch ($f) {
          case 'surname': $str[] = 'l.`surname` = UPPER("'.$v.'")'; break;
          case 'borth': $str[] = 'DATE_FORMAT(l.`borth`, "%d.%m.%Y") LIKE "'.$v.'%"'; break;
          default: $str[] = 'l.`'.$f.'` LIKE "'.$v.'%"';  break;
        }
      }
      require_once(KERNEL.'connection.php');
      $query = mysql_query('
        SELECT
          l.`id`, l.`surname`, l.`name`, l.`fath_name`,
          DATE_FORMAT(l.`borth`, "%d.%m.%Y") as `borth`
        FROM
          `o_lico` as l
        WHERE
          '.implode(' AND ', $str)
      );
      while ($result = mysql_fetch_assoc($query)) {
        foreach ($result as $k => $v) {
          $result[$k] = mb_convert_case($v, MB_CASE_TITLE, 'UTF-8');
        }
        $json[] = $result['surname'].' '.$result['name'].' '.$result['fath_name'].' '.$result['borth'];
        $group_result[] = $result;
      }
      break;
  }
}

if (isset($_POST['del_relation']) and isset($_POST['group'])) {
  switch($_POST['group']) {
    case 'file':
      if (unlink($_SESSION['dir_session'].mb_convert_encoding($_SESSION['decision']['data']['file_original'], 'Windows-1251', 'UTF-8'))) {
        unset($_SESSION['decision']['data']['file_original']);
        $json['.decision_block .file_block form'] = file_input('file_upload').'<div class="added_row"><div style="clear: left;"></div></div>';
      } else {
        $jsonERR[] = 'Ошибка удаления файла!';
      }
      break;
  
    case 'kusp':
      unset($_SESSION['decision']['kusp']['list'][$_POST['del_relation']]);
      if (count($_SESSION['decision']['kusp']['list']) == 0) {
        unset($_SESSION['decision']['kusp']);
        $json['.messages_block .added_row'] = '';
      } else {
        $json['.messages_block .added_row'] = related_kusp($_SESSION['decision']['kusp']['list']);
      }
      break;
    
    case 'uk':
      unset($_SESSION['decision']['uk']['list'][$_POST['del_relation']]);
      if (count($_SESSION['decision']['uk']['list']) == 0) {
        unset($_SESSION['decision']['uk']);
        $json['.criminal_block .added_row'] = '';
      } else {
        $json['.criminal_block .added_row'] = related_uk();
      }
      break;
      
    case 'objects_face':
    case 'objects_organisation':
      $obj = ($_POST['group'] == 'objects_face') ? 'faces' : 'organisations';
      $relation = explode('|', $_POST['del_relation']); // связь | индекс
      unset($_SESSION['decision'][$obj]['list'][$relation[0]][$relation[1]]);
      if (!count($_SESSION['decision'][$obj]['list'][$relation[0]])) {
        unset($_SESSION['decision'][$obj]['list'][$relation[0]]);
      }
      if (!count($_SESSION['decision'][$obj]['list'])) {
        unset($_SESSION['decision'][$obj]);
      }
      if (empty($_SESSION['decision']['organisations']['list']) and empty($_SESSION['decision']['faces']['list'])) {
        $json['.objects_block .added_row'] = '';
      } else {
        $json['.objects_block .added_row'] = related_faces_organisations();
      }
      break;
  }
  if (!count($_SESSION['decision'])) {
    unset($_SESSION['decision']);
  }
}

if (!empty($_REQUEST['method'])) {
  if (!empty($_REQUEST['id']))
    $id = $_REQUEST['id'];
  /*$id = null;
  if (!empty($_REQUEST['id'])) {
    if (!is_numeric($_REQUEST['id'])) {
      unset($_REQUEST['id']);
    } else {
      $id = floor(abs($_REQUEST['id']));
    }
  }*/
  switch($_REQUEST['method']) {
    case 'continue':
      $jsonMSG[] = 'ready';
      break;
    
    case 'add':
      if (!empty($_SESSION['decision'])) unset($_SESSION['decision']);
      $jsonMSG[] = 'ready';
      break;

    case 'edit':
      if (empty($id)) break;
      $decision = new Decision();
      if ($decision->set_id($id) !== false) {
        $decision->save_to_session();
        $jsonMSG[] = 'ready';
      } else {
        $jsonERR[] = 'Такое решение отсутствует в БД!';
      }
      break;
      
    case 'delete':
      if (empty($id) or empty($_REQUEST["deleter"]) or empty($_REQUEST["reason"])) break;
      if (mb_strlen($_REQUEST["reason"], 'UTF-8') < 10) {
        $jsonERR[] = 'Не указана причина удаления!';
        break;
      }
      if (mb_strlen($_REQUEST["deleter"], 'UTF-8') < 5) {
        $jsonERR[] = 'Не указан сотрудник!';
        break;
      }
      if (!preg_match('/^[а-яё .\t]+$/iu', $_REQUEST["reason"]) or !preg_match('/^[а-я .\t]+$/iu', $_REQUEST["deleter"])) {
        $jsonERR[] = 'Поля заполняются только русскими буквами!';
        break;
      }
      $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
      $deleter = mysql_real_escape_string($_REQUEST["deleter"]);
      $reason = mysql_real_escape_string($_REQUEST["reason"]);
      $delete_info = $_SESSION['user']['user'].", id сеанса ".$_SESSION['activity_id'].", ".date('d.m.Y, H:i');
      require(KERNEL.'connection.php');
      $query = mysql_query('
        UPDATE
          `l_decisions`
        SET
          `deleted` = 1, `delete_info` = "'.$delete_info.'", `delete_emp` = "'.$deleter.'", `delete_reason` = "'.$reason.'",
          `update_date` = current_date, `update_time` = current_time, `update_active_id` = '.$active_id.'
        WHERE
          `id` = '.$id.'
      ');
      if (!$query) {
        $jsonERR[] = "Ошибка SQL: ".mysql_error();
        break;
      }
      $jsonMSG[] = 200;
      break;
      
    case 'debt':
      if (empty($id)) break;
      require(KERNEL.'connection.php');
      /*$query = mysql_query('
        SELECT
          ld.`dec_date`, UPPER(ld.`emp_s`) as `emp_s`, UPPER(ld.`emp_n`) as `emp_n`, UPPER(ld.`emp_fn`) as `emp_fn`,
          IF(serv.`id_slujba` IS NULL, 8, serv.`id_slujba`) as `service`, ld.`article_proc_par`,
          ld.`reg_date`, ld.`reg_num`, ld.`ovd`, uk.`id_uk` as `uk`
        FROM
          `leg_decisions` as ld
        LEFT JOIN
          `spr_uk` as uk ON
            uk.`st` = CONCAT(
              IF(ld.`article` IS NOT NULL, ld.`article`, ""),
              IF(ld.`article_note` IS NOT NULL, CONCAT(".", ld.`article_note`), ""),
              IF(ld.`article_part` IS NOT NULL AND ld.`article_part` <> 0, CONCAT(" ч.", ld.`article_part`), "")
            )
        LEFT JOIN
          `spr_slujba` as serv ON
            serv.`slujba` LIKE CONCAT("%", ld.`emp_service` ,"%")
        WHERE
          ld.`id` = '.$id
      );
      if (!mysql_num_rows($query)) {
        $jsonERR[] = "Указанная запись не найдена!";
        break;
      }
      $result = mysql_fetch_assoc($query);
      $decision = new Decision();
      $decision->set_type(1);
      $decision->add_kusp(array('kusp' => $result['reg_num'], 'date' => $result['reg_date'], 'ovd' => $result['ovd']));
      $decision->add_uk($result['uk']);
      $decision->data['date'] = $result['dec_date'];
      $decision->data['ovd'] = $result['ovd'];
      $decision->data['service'] = $result['service'];
      if (is_numeric($result['article_proc_par'])) $decision->data['upk'] = $result['article_proc_par'];
      $decision->data['emp_s'] = $result['emp_s'];
      $decision->data['emp_n'] = $result['emp_n'];
      $decision->data['emp_fn'] = $result['emp_fn'];
      $decision->save_to_session();
      $jsonMSG[] = 'ready';*/
      $query = mysql_query('
        SELECT
          DATE_FORMAT(dh.`dec_date`, "%d.%m.%Y") as `dec_date`,
          TRIM(dh.`emp_person`) as `emp_person`,
          TRIM(LOWER(
             IF(
              POSITION("," IN dh.`emp_position`) > 0,
              SUBSTRING(dh.`emp_position`, 1, POSITION("," IN dh.`emp_position`) - 1),
              dh.`emp_position`)
           )) as `service`, dh.`proc_par`,
           DATE_FORMAT(ek.`reg_date`, "%d.%m.%Y") as `reg_date`,
           ek.`reg_number`, ek.`ovd`, dh.`qualification`
        FROM
          `ek_dec_history` as dh
        JOIN
          `ek_kusp` as ek ON
            ek.`id` = dh.`kusp`
        WHERE
          dh.`id_SB_RESH` = "'.$id.'" 
      ');
      if (!mysql_num_rows($query)) {
        $jsonERR[] = "Указанная запись не найдена!";
        break;
      }
      $result = mysql_fetch_assoc($query);
      $emp = explode(' ', $result['emp_person']);
      $decision = new Decision();
      $decision->set_type(1);
      $decision->add_kusp(array('kusp' => $result['reg_number'], 'date' => $result['reg_date'], 'ovd' => $result['ovd']));
      $decision->add_uk_by_string($result['qualification']);
      $decision->data['date'] = $result['dec_date'];
      $decision->data['ovd'] = $result['ovd'];
      $decision->add_service_by_string($result['service']);
      if (is_numeric($result['proc_par'])) $decision->data['upk'] = $result['proc_par'];
      if (array_key_exists(0, $emp)) $decision->data['emp_s'] = $emp[0];
      if (array_key_exists(1, $emp)) $decision->data['emp_n'] = $emp[1];
      if (array_key_exists(2, $emp)) $decision->data['emp_fn'] = $emp[2];
      $decision->save_to_session();
      $jsonMSG[] = 'ready';
      break;
  }
}

// -------- вывод данных -------- //
$res = array_diff($jsonERR, array()); // убираем пустые строки массива ошибок

if (count($res) > 0) {
  $resp['error'] = implode(', ', $jsonERR);
} else {
  if (!empty($json)) $resp['html'] = $json;
  if (!empty($jsonMSG)) $resp['msg'] = implode(', ', $jsonMSG);
  if (!empty($group_result)) $resp['group_result'] = $group_result;
}
if ($jsonData) {  // если есть данные на JSON
  if (!empty($resp)) echo json_encode($resp);
} else {
  echo '<script type="text/javascript">window.parent.json_response_handling('.json_encode($resp).')</script>';
  if (!empty($add_function)) echo $add_function;
}
if (!empty($add_function_req)) echo $add_function_req;

// ^^^^^^^^ вывод данных ^^^^^^^^ //
?>
<?php
set_time_limit(0);
$need_auth = 0;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$jsonERR = array();
$jsonData = true;

if (!isset($_SESSION['crime']['ais'])) {
  echo json_encode(array('error' => 'Вход в АИС не выполнен!'));
  die();
}

$handled_form = $json = $match = $jsonMSG = '';
$vars = get_defined_vars();

function get_table_fields($table) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('DESCRIBE `'.$table.'`') or die(mysql_error());
  while ($result = mysql_fetch_assoc($query)) {
    $fields[] = strtolower($result['Field']); // создаем массив полей
  }
  return $fields;
}

function data_handling($array, $id, $obj, $script, $ind) {
  global $handled_form, $json, $jsonERR;
  $handled_form = 'relation_form';
  $type = $array['rel_type'];
  $arr1 = array('id' => $id, 'type' => $ind);  // связываемый объект
  $arr2 = array('id' => $array['viewed_obj'], 'type' => $array['viewed_obj_type']); // главный просматриваемый объект
  unset($array['viewed_obj_type'], $array['viewed_obj'], $array['rel_type']); // убираем из массива ненужные элементы
  if (isset($array['decision']) && isset($array['decision_date']) && isset($array['decision_number'])) {
    $decision = $array['decision'];
    $array['decision_date'] = $array['decision_date'][$decision - 1];
    $array['decision_number'] = $array['decision_number'][$decision - 1];
  }
  $sorted = sort_arrays_by_key($arr1, $arr2, 'type');
  if (check_relation($sorted[0]['id'], $sorted[0]['type'], $sorted[1]['id'], $sorted[1]['type'], $type, $array['ais'])) {  // проверка наличия связи с ранее введенным объектом
    $json = call_user_func($script, $arr2['id'], $arr2['type']);
    $jsonERR[] = 'Такая связь уже существует!';
  } else {
    add_relation($sorted[0]['id'], $sorted[0]['type'], $sorted[1]['id'], $sorted[1]['type'], $type, $array['ais']);
    $jsonERR[] = update_object($obj, $id, $array);
    $json = call_user_func($script, $arr2['id'], $arr2['type']);;
  }
}

function object_markings($obj, $type) {
  $ret = array();
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      om.`id`, om.`marking`
    FROM
      `l_object_marking` as om
    WHERE
      om.`obj_type` = '.$type.' AND
      om.`object` = '.$obj
  );
  while($result = mysql_fetch_assoc($query)) {
    $ret[$result['id']] = $result['marking'];
  }
  return $ret;
}

function object_markings_update($array, $obj, $type){
  $marks = object_markings($obj, $type);
  $to_add = array_diff($array, $marks);  // добавление
  $to_del = array_diff($marks, $array);  // удаление
  if (!empty($to_add)) {
    $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
    foreach ($to_add as $m) {
      $add[] = '('.$m.', '.$obj.', '.$type.', current_date, current_time, '.$activity_id.')';
    }
    mysql_query('
      INSERT INTO
        `l_object_marking`(`marking`, `object`, `obj_type`, `create_date`, `create_time`, `active_id`)
      VALUES
        '.implode(', ', array_values($add)).'
    ') or $error[] = mysql_error();
  }
  if (!empty($to_del)) {
    mysql_query('
      DELETE FROM
        `l_object_marking`
      WHERE
        `id` IN ('.implode(',', array_keys($to_del)).')
    ') or $error[] = mysql_error();
  }
  return (isset($error)) ? implode(', ', $error) : true;
}

function upload_file($file, $req_id) {
  global $json, $jsonData, $jsonERR;
  $file_size = $file["size"];
  $file_name = $file["name"];
  $file_tmp_name = $file["tmp_name"];
  if ($file_size != "0" && $file_size < "2097152") {
    if (is_uploaded_file($file_tmp_name)) {
      if (copy($file_tmp_name, REQUESTS.$req_id.'_'.iconv("UTF-8", "Windows-1251", $file_name))) {
        $jsonData = false;
        $json['request_image_cell'] = '<a href="'.CRIMES.'download_file.php?id='.$req_id.'">Скачать образ запроса</a>';
        return $req_id.'_'.$file_name;
      } else {
        $jsonERR[] = 'Ошибка копирования файла!';
      }
    } else {
      $jsonERR[] = 'Ошибка загрузки файла!';
    }
  } else {
    $jsonERR[] = 'Файл имеет 0 размер или больше 2 мб!';
  }
}

function request_object_save($request, $object_type, $object, $relation) {
  $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
  require_once(KERNEL.'connection.php');
  mysql_query('
    INSERT INTO
      `l_request_object`(`request`, `relation`, `object_type`, `object`, `create_date`, `create_time`, `active_id`)
    VALUES
      ('.$request.', '.$relation.', '.$object_type.', '.$object.', current_date, current_time, '.$activity_id.')
  ');
  if (mysql_insert_id() == 0) {
    return false;
  } else {
    return true;
  }
}

function search_men3($f, $i, $o = '', $dr = '') {
  $str = array();
  if ($f != '') $str['surname'] = mb_convert_case($f, MB_CASE_UPPER, "UTF-8");
  if ($i != '') $str['name'] = mb_convert_case($i, MB_CASE_UPPER, "UTF-8");
  if ($o != '') $str['fath_name'] = mb_convert_case($o, MB_CASE_UPPER, "UTF-8");
  if ($dr != '') $str['borth'] = date('Y-m-d', strtotime($dr));
  foreach ($str as $field => $value) {
    $sql[] = $field.' = "'.$value.'"';
  }
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      id, surname, name, fath_name, borth
    FROM
      o_lico
    WHERE
      '.implode(' AND ', $sql).'
  ') or die(mysql_error());
  if (mysql_num_rows($query)) {
    while ($result = mysql_fetch_array($query)) {
      return $result["id"];
    }
  } else {
    return 0;
  }
}

function handling_address($par) {
  global $jsonERR, $array;
  if (isset($par['region']) && !empty($par['region'])) {
    if (($array['region'] = search_id('spr_region', array('code' => $par['region']))) === false) {
      $jsonERR[] = 'Ошибка поиска региона.';
    }
  }
  
  if (isset($par['district']) && !empty($par['district'])) {
    if (($array['district'] = search_id('spr_district', array('code' => $par['district']))) === false) {
      $jsonERR[] = 'Ошибка поиска района.';
    }
  }
  
  if (isset($par['city']) && !empty($par['city'])) {
    if (($array['city'] = search_id('spr_city', array('code' => $par['city']))) === false) {
      $jsonERR[] = 'Ошибка поиска населенного пункта.';
    }
  }
  
  if (isset($par['locality']) && !empty($par['locality'])) {
    if (($array['locality'] = search_id('spr_locality', array('code' => $par['locality']))) === false) {
      $jsonERR[] = 'Ошибка поиска НП в городе.';
    }
  }
  
  if (isset($par['street']) && !empty($par['street'])) {
    if (($array['street'] = search_id('spr_street', array('code' => $par['street']))) === false) {
      $jsonERR[] = 'Ошибка поиска улицы.';
    }
  }
  
  if (isset($par['house'])) {
    if (!empty($par['house']) && !is_numeric($par['house'])) {
      $jsonERR[] = 'Номер дома указывается только в цифровом значении.';
    }
  }
  
  if (isset($par['flat'])) {
    if (!empty($par['flat']) && !is_numeric($par['flat'])) {
      $jsonERR[] = 'Номер квартиры указывается только в цифровом значении.';
    }
  }
  unset($array['region_text'], $array['district_text'], $array['city_text'], $array['locality_text'], $array['street_text']);
}

function analysis_objects($cntr) {
  $objects = array('Тел.номер', 'IMEI');
  $option = '<select name="analysis_objects[]['.$cntr.']" class="analysis_objects" style="width: 100%;">';
  $option .= '<option value=""></option>';
  foreach ($objects as $id => $object) {
    $option .= '<option value="'.$id.'">'.$object.'</option>';
  }
  $option .= '</select>';
  return $option;
}

if (isset($_REQUEST['data_form']) && !isset($_REQUEST['ajsrch']) && !isset($_POST['select'])) {
  $array = $vars['_REQUEST'];
  $array['ais'] = $_SESSION['crime']['ais'];
  $files = $vars['_FILES'];
  
   /*  обрабатываем дату и номер решения  */
  if (isset($array['decision'])) {
    $decision = $array['decision'];
    if (isset($array['decision_date']) && is_array($array['decision_date'])) $array['decision_date'] = $array['decision_date'][$decision - 1];
    if (isset($array['decision_number']) && is_array($array['decision_number'])) $array['decision_number'] = $array['decision_number'][$decision - 1];
    if (isset($array['article_id']) && is_array($array['article_id'])) $array['article_id'] = $array['article_id'][$decision - 1];
  }
  /*  обрабатываем дату, номер решения, статью УК  */
  
  if (isset($array["x"])) unset($array["x"]);
  if (isset($array["y"])) unset($array["y"]);
  
  switch($_REQUEST['data_form']) {
    case 'form_event':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) { // если пришли с формы связи
        $id = search_event($array["kusp_num"], $array["kusp_date"], $array["ovd_id"]);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_event');
        }
        data_handling($array, $id, 'o_event', 'related_events', 2);
      } else {                    // если обновляли данные на event.php
        $handled_form = 'main_form';
        
        if (strlen(preg_replace('/\D/', '', $array['emp_telephone'])) < 6) {
          $jsonERR[] = 'Не указан контактный телефон сотрудника.';
          break;
        }
        if (!empty($array['decision_date']) && !empty($array['kusp_date'])) {
          if (strtotime($array['decision_date']) < strtotime($array['kusp_date'])) {
            $jsonERR[] = 'Дата КУСП не может быть больше даты вынесения решения.';
            break;
          }
        }
        if (isset($array['marking'])) {
          $array['marking'] = array_keys(array_diff($array['marking'], array(0)));
          if (count($array['marking']) == 0) {
            $jsonERR[] = 'Не указана окраска события.';
            break;
          }
        }
        
        if ($array['id'] != '') { // если открыли старое событие
          $id = $array['id'];
        } else {                  // если начали ввод нового события
          $id = add_object('o_event');
          if (is_numeric($id)) {
            $json['object_id'] = '<input type="hidden" name="id" value="'.$id.'"/>';
            $json['objects_list_block'] = sel_objects($id, 2);
            $_SESSION['crime']['event_id'] = $id;
          } else {
            $jsonERR[] = $id;
          }
        }
        $susp_date = $array['susp_date'];
        $susp_resume_date = $array['susp_resume_date'];
        unset($array['susp_date'], $array['susp_resume_date'], $array['id']);
        if (($susp_date != "") && ($susp_resume_date == "")) add_suspension($id, $susp_date);
        if ($susp_resume_date != "") suspension_resume($id, $susp_date, $susp_resume_date);
        $jsonERR[] = update_object('o_event', $id, $array);
        if (isset($array['marking'])) {
          object_markings_update($array['marking'], $id, 2);
        }
      }
      break;
      
    case 'form_face':
      unset($array['data_form']);
      
      if (isset($array['viewed_obj'])) {
        if (empty($array["surname"]) or empty($array["name"]) or empty($array["borth"])) {
          $jsonERR[] = 'Указаны не полные установочные данные';
          break;
        }
        $id = search_men3($array["surname"], $array["name"], $array["fath_name"], $array["borth"]);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_lico');
        }
        
        if (is_numeric($array['viewed_obj_type'])) {
          data_handling($array, $id, 'o_lico', 'related_faces', 1);
        } else {
          switch($array['viewed_obj_type']) {
            case 'request':
              if (request_object_save($array['viewed_obj'], 1, $id, $array['rel_type']) === false) {
                $jsonERR[] = 'Вводимый объект уже имеет такую связь с изменяемым запросом!';
              }
              $json = related_faces($array['viewed_obj'], 'request');
              break;
          }
          $handled_form = 'relation_form';
        }
      } else {
        $handled_form = 'main_form';
        $jsonERR[] = 'Таблица "Лица" временно закрыта для редактирования!';
      }
      break;
      
    case 'form_address':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        handling_address($array);
        if (count($jsonERR) == 0) {
          $id = search_address($array);
          if ($id) {
            $match = '<br/>Был использован ранее введенный объект.';
          } else {
            $id = add_object('o_address');
          }
          data_handling($array, $id, 'o_address', 'related_address', 3);
        }
      } else {
        $handled_form = 'main_form';
        $jsonERR[] = 'Таблица "Адрес" временно закрыта для редактирования!';
      }
      break;
      
    case 'form_document':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        if (preg_match('/[\D]/', $array["number"])) {
          $jsonERR[] = 'Номер документа должен состоять только из цифр.';
          break;
        }
        $id = search_document($array["serial"], $array["number"], $array["type"]);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_documents');
        }
        data_handling($array, $id, 'o_documents', 'related_documents', 4);
      } else {
        $handled_form = 'main_form';
        $id = $array['id'];
        unset($array['id']);
        $jsonERR[] = update_object('o_documents', $id, $array);
      }
      break;
      
    case 'form_device':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        if (preg_match('/[\D]/', $array['IMEI'])) {
          $jsonERR[] = 'IMEI устройства должен состоять только из цифр.';
          break;
        }
        if((mb_strlen($array['IMEI']) < 14) or (mb_strlen($array['IMEI']) > 17))
        {  
          $jsonERR[] = 'Некорректный IMEI.';
          break;
        }
        $id = search_device($array['IMEI']);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_mobile_device');
        }
        data_handling($array, $id, 'o_mobile_device', 'related_mobile_devices', 5);
      } else {
        $handled_form = 'main_form';
        $id = $array['id'];
        unset($array['id']);
        $jsonERR[] = update_object('o_mobile_device', $id, $array);
      }
      break;
      
    case 'form_telephone':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        if (!$phone = search_telephone($array['number'])) {
          $phone[0]['id'] = add_object('o_telephone');
        } else {
          $match = '<br/>Был использован ранее введенный объект.';
        }
        if (is_numeric($array['viewed_obj_type'])) {
          data_handling($array, $phone[0]['id'], 'o_telephone', 'related_telephones', 6);
        } else {
          switch($array['viewed_obj_type']) {
            case 'request':
              if (request_object_save($array['viewed_obj'], 6, $phone[0]['id'], $array['rel_type']) === false) {
                $jsonERR[] = 'Вводимый объект уже имеет такую связь с изменяемым запросом!';
              }
              $json = related_telephones($array['viewed_obj'], 'request');
              break;
          }
          $handled_form = 'relation_form';
        }
      } else {
        $handled_form = 'main_form';
        $id = $array['id'];
        unset($array['id']);
        if (isset($array['IMSI'])) {
          $IMSI = search_id('l_imsi', array('IMSI' => $array['IMSI'], 'telephone' => $id), '=');
          if (!$IMSI) {
            $IMSI = add_object('l_imsi');
          }
          $jsonERR[] = update_object('l_imsi', $IMSI, array('IMSI' => $array['IMSI'], 'telephone' => $id));
          $json['imsi_cell'] = related_imsi($id);
        }
        unset($array['IMSI']);
        $jsonERR[] = update_object('o_telephone', $id, $array);
      }
      break;
    
    case 'form_bank_account':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        if (preg_match('/[\D]/', $array["number"])) {
          $jsonERR[] = 'Номер банковского счета должен состоять только из цифр.';
          break;
        }
        $id = search_bank_account($array['number']);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_bank_account');
        }
        if (is_numeric($array['viewed_obj_type'])) {
          data_handling($array, $id, 'o_bank_account', 'related_bank_accounts', 7);
        } else {
          switch($array['viewed_obj_type']) {
            case 'request':
              if (request_object_save($array['viewed_obj'], 7, $id, $array['rel_type']) === false) {
                $jsonERR[] = 'Вводимый объект уже имеет такую связь с изменяемым запросом!';
              }
              $json = related_bank_accounts($array['viewed_obj'], 'request');
              break;
          }
          $handled_form = 'relation_form';
        }
      } else {
        $handled_form = 'main_form';
        $id = $array['id'];
        unset($array['id']);
        $jsonERR[] = update_object('o_bank_account', $id, $array);
      }
      break;

    case 'form_bank_card':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        if (preg_match('/[\D]/', $array["number"])) {
          $jsonERR[] = 'Номер банковской карты должен состоять только из цифр.';
          break;
        }
        $id = search_bank_card($array['number']);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_bank_card');
        }
        if (is_numeric($array['viewed_obj_type'])) {
          data_handling($array, $id, 'o_bank_card', 'related_bank_cards', 8);
        } else {
          switch($array['viewed_obj_type']) {
            case 'request':
              if (request_object_save($array['viewed_obj'], 8, $id, $array['rel_type']) === false) {
                $jsonERR[] = 'Вводимый объект уже имеет такую связь с изменяемым запросом!';
              }
              $json = related_bank_cards($array['viewed_obj'], 'request');
              break;
          }
          $handled_form = 'relation_form';
        }
      } else {
        $handled_form = 'main_form';
        $id = $array['id'];
        unset($array['id']);
        if (!isset($array["SMS"])) $array["SMS"] = 0;
        if (!isset($array["online"])) $array["online"] = 0;
        $jsonERR[] = update_object('o_bank_card', $id, $array);
      }
      break;
    
    case 'form_e-wallet':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        $id = search_wallet($array['number']);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_wallet');
        }
        if (is_numeric($array['viewed_obj_type'])) {
          data_handling($array, $id, 'o_wallet', 'related_wallets', 9);
        } else {
          switch($array['viewed_obj_type']) {
            case 'request':
              if (request_object_save($array['viewed_obj'], 9, $id, $array['rel_type']) === false) {
                $jsonERR[] = 'Вводимый объект уже имеет такую связь с изменяемым запросом!';
              }
              $json = related_wallets($array['viewed_obj'], 'request');
              break;
          }
          $handled_form = 'relation_form';
        }
      } else {
        $handled_form = 'main_form';
        $id = $array['id'];
        unset($array['id']);
        $jsonERR[] = update_object('o_wallet', $id, $array);
      }
      break;
    
    case 'form_e-mail':
      unset($array['data_form']);
      if (isset($array['viewed_obj'])) {
        $id = search_mail($array['name']);
        if ($id) {
          $match = '<br/>Был использован ранее введенный объект.';
        } else {
          $id = add_object('o_mail');
        }
        
        if (is_numeric($array['viewed_obj_type'])) {
          data_handling($array, $id, 'o_mail', 'related_mails', 10);
        } else {
          switch($array['viewed_obj_type']) {
            case 'request':
              if (request_object_save($array['viewed_obj'], 10, $id, $array['rel_type']) === false) {
                $jsonERR[] = 'Вводимый объект уже имеет такую связь с изменяемым запросом!';
              }
              $json = related_mails($array['viewed_obj'], 'request');
              break;
          }
          $handled_form = 'relation_form';
        }
      } else {
        $handled_form = 'main_form';
        $id = $array['id'];
        unset($array['id']);
        $jsonERR[] = update_object('o_mail', $id, $array);
      }
      break;
      
    case 'form_requests':
      if (empty($array['type'])) {
        $jsonERR[] = 'Не указан тип запроса!';
        break;
      }
      if (empty($array['organisation'])) {
        $jsonERR[] = 'Не указана организация-адресат!';
        break;
      }
      if (empty($array['request_date'])) {
        $jsonERR[] = 'Не указана дата запроса!';
        break;
      } else {
        if (strtotime($array['request_date']) > strtotime('now')) {
          $jsonERR[] = 'Дата запроса не может быть больше текущей!';
          break;
        }
      }
      if (empty($array['request_number'])) {
        $jsonERR[] = 'Не указан номер запроса!';
        break;
      }
      unset($array['data_form']);
      $handled_form = 'relation_form';
      $array['event'] = $array['viewed_obj'];
      unset($array['viewed_obj']);
      require_once(KERNEL.'connection.php');
      $array['organisation'] = mysql_real_escape_string($array['organisation']);
      $array['request_date'] = date('Y-m-d', strtotime($array['request_date']));
      $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
      mysql_query('
        INSERT INTO
          `l_requests`(`type`, `organisation`, `request_date`, `request_number`, `ais`, `event`, `create_date`, `create_time`, `active_id`)
        VALUES
          ("'.implode('", "', $array).'", current_date, current_time, '.$activity_id.')
      ');
      if (mysql_insert_id() == 0) {
        $jsonERR[] = 'Такой запрос уже направлялся!';
        break;
      }
      $json = related_requests($array['event'], 2);
      break;

    case 'form_detailization':
      $handled_form = 'main_form';
      unset($array['data_form']);
      if (preg_match('/([01][0-9]|2[0-3])\:([0-5][0-9])/', $array['connection_time'])) {
        $array['connection_time'] = date('H:i:s', strtotime($array['connection_time']));
      } else {
        $jsonERR[] = 'Неверный формат времени!';
      }
      if (($array['connection_length'] != '') && !preg_match('/^\d+$/', $array['connection_length'])) {
        $jsonERR[] = 'Продолжительность указывается в секундах!';
      }
      if (count($jsonERR) == 0) { // если данные валидны, то добавляем...
        $detail = array();
        // ------- ... телефон -------- //
        $array['subscriber'] = preg_replace('/[^\d]+/', '', $array['subscriber']); // оставляем в номере только цифры
        if (!$phone = search_telephone($array['subscriber'], '=')) {
          $phone[0]['id'] = add_object('o_telephone');
        }
        $jsonERR[] = update_object('o_telephone', $phone[0]['id'], array('number' => $array['subscriber']));
        unset($array['subscriber']);
        
        // ------- ... адрес -------- //
        handling_address($array);
        $address = array();
        $fields = get_table_fields('o_address');  // получаем список полей таблицы `o_address`
        foreach ($array as $key => $value) {  // формируем список значений для вставки в таблицу адреса
          if (in_array($key, $fields)) {
            $address[$key] = $value;
          }
        }
        $address_id = search_address($array);
        if (!$address_id) {
          $address_id = add_object('o_address');
        }
        if (is_numeric($address_id)) {
          $jsonERR[] = update_object('o_address', $address_id, $address);
        }
        foreach($fields as $field) {
          unset($array[$field]);  // удаляем из основного массива элементы, добавленные в таблицу адреса
        }
        $detail['base_station_address'] = $address_id;
        unset($address, $address_id);
        
        // ------- ... детализацию -------- //
        $fields = get_table_fields('l_detailizations');  // получаем список полей таблицы `l_detailizations`
        switch ($array['direction']) {  // определяем направление звонка
          case 1:
            $detail['subscriber_1'] = $array['viewed_obj'];
            $detail['subscriber_2'] = $phone[0]['id'];
            break;
          case 2:
            $detail['subscriber_1'] = $phone[0]['id'];
            $detail['subscriber_2'] = $array['viewed_obj'];
            break;
        }
        //unset($array['viewed_obj'], $array['direction']);
        foreach ($array as $key => $value) { // формируем список значений для вставки в таблицу детализаций
          if (in_array($key, $fields)) {
            if (strpos($key, '_date') !== false) {
              $detail[$key] = date('Y-m-d', strtotime($value));  // переворачиваем дату
            } else {
              $detail[$key] = $value;
            }
          }
        }
        foreach($fields as $field) {
          unset($array[$field]);  // удаляем из основного массива элементы, добавленные в таблицу детализаций
        }
        $detail_id = add_object('l_detailizations');
        if (is_numeric($detail_id)) {
          $jsonERR[] = update_object('l_detailizations', $detail_id, $detail);
        }
        if (!check_relation($detail['subscriber_1'], 6, $detail['subscriber_2'], 6, 25)) {
          add_relation($detail['subscriber_1'], 6, $detail['subscriber_2'], 6, 25);
        }
        $json = connection_indicators($array['viewed_obj']);
      }
      
      break;
      
    case 'form_messenger':
      $handled_form = 'relation_form';
      if (empty($array['type'])) {
        $jsonERR[] = 'Не указан тип мессенджера!';
        break;
      }
      if (empty($array['account'])) {
        $jsonERR[] = 'Не указан аккаунт!';
        break;
      } else {
        if (!preg_match('/^[a-zа-я0-9_\s]*$/ui', $array['account'])) {
          $jsonERR[] = 'Имя аккаунта должно состоять из цифр или букв, или символа "_"!';
          break;
        }
      }
      $mess = search_id('l_messengers', array('account' => $array['account'], 'type' => $array['type']));
      if ($mess) {
        $match = '<br/>Был использован ранее введенный объект.';
      } else {
        $mess = add_object('l_messengers');
        $jsonERR[] = update_object('l_messengers', $mess, $array);
      }
      if (search_id('l_messenger_obj_rel', array('messenger' => $mess, 'object' => $array['viewed_obj'], 'object_type' => $array['viewed_obj_type']))) {
        $jsonERR[] = 'Такой аккаунт уже указан!';
        break;
      }
      $rel = add_object('l_messenger_obj_rel');
      $jsonERR[] = update_object('l_messenger_obj_rel', $rel, array('messenger' => $mess, 'object' => $array['viewed_obj'], 'object_type' => $array['viewed_obj_type'], 'ais' => $array['ais']));
      $json = related_messengers($array['viewed_obj'], $array['viewed_obj_type']);
      break;
      
    case 'form_nickname':
      $handled_form = 'relation_form';
      if (empty($array['nickname'])) {
        $jsonERR[] = 'Не указана кличка!';
        break;
      }
      $nick = search_id('l_nicknames', array('nickname' => $array['nickname']));
      if ($nick) {
        $match = '<br/>Был использован ранее введенный объект.';
      } else {
        $nick = add_object('l_nicknames');
        $jsonERR[] = update_object('l_nicknames', $nick, $array);
      }
      if (search_id('l_nickname_obj_rel', array('nickname' => $nick, 'object' => $array['viewed_obj'], 'ais' => $array['ais']))) {
        $jsonERR[] = 'Такая кличка уже указана!';
        break;
      }
      $rel = add_object('l_nickname_obj_rel');
      $jsonERR[] = update_object('l_nickname_obj_rel', $rel, array('nickname' => $nick, 'object' => $array['viewed_obj'], 'ais' => $array['ais']));
      $json = related_nicknames($array['viewed_obj'], 1);
      break;
      
    case 'form_drugs':
      $handled_form = 'relation_form';
      if (empty($array['drug'])) {
        $jsonERR[] = 'Не указано наркотическое вещество!';
        break;
      }
      if (empty($array['weight'])) {
        $jsonERR[] = 'Не указан вес!';
        break;
      }
      if (!is_numeric($array['weight'])) {
        $jsonERR[] = 'Вес указывается в числовом значении!<br/>Для отделения дробной части используйте символ точки.';
        break;
      }
      if (search_id('l_withdrawn_drug', array('drug' => $array['drug'], 'event' => $array['viewed_obj']))) {
        $jsonERR[] = 'Данный вид наркотика уже указан!';
        break;
      }
      //$array['weight'] = preg_replace('/[.]/', ',', $array['weight']);
      $array['event'] = $array['viewed_obj'];
      unset($array['viewed_obj']);
      $rel = add_object('l_withdrawn_drug');
      $jsonERR[] = update_object('l_withdrawn_drug', $rel, $array);
      $json = related_drugs($array['event']);
      break;
  }
  switch ($handled_form) {  // имя обрабатываемой формы
    case 'main_form':
      $jsonMSG = 'Изменения успешно сохранены!';
      break;
    case 'relation_form':
      $jsonMSG = 'Связь установлена!'.$match;
      break;
  }
} else {
  if (isset($_REQUEST['data_form']) && in_array($_REQUEST['data_form'], array('form_address', 'form_detailization'))) {  // если пришли с формы адреса или детализации
    
    unset($_REQUEST['data_form'], $_REQUEST['id']);
    $_REQUEST = array_diff($_REQUEST, array(null));
    
    if (isset($_REQUEST['ajsrch'])) {  // быстрый поиск по КЛАДР
      switch ($_REQUEST['ajsrch']) {
        case 'region':
          if (isset($_REQUEST['district']) || isset($_REQUEST['city']) || isset($_REQUEST['locality']) || isset($_REQUEST['street'])) break;
          require_once(KERNEL.'connection.php');
          $query = mysql_query('
            SELECT
              r.`id`,
              CONCAT(
                RTRIM(r.`name`), ", ", RTRIM(s.`scname`)
              ) as `name`,
              r.`code`
            FROM
              `spr_region` as r
            LEFT JOIN
              `spr_socr` as s ON
                s.`id` = r.`socr` AND
                s.`level` = 1
            WHERE
              r.`name` LIKE "'.$_REQUEST['region_text'].'%" AND
              r.`code` LIKE "%00"
          ') or $jsonERR[] = mysql_error();
          while ($result = mysql_fetch_assoc($query)) {
            $json[$result['code']] = $result['name'];
          }
          break;

        case 'district':
          if (isset($_REQUEST['city']) || isset($_REQUEST['locality']) || isset($_REQUEST['street'])) break;
          if (isset($_REQUEST['region'])) {
            $region = substr($_REQUEST['region'], 0, 2);
            require_once(KERNEL.'connection.php');
            $query = mysql_query('
              SELECT
                d.`id`,
                CONCAT(
                  RTRIM(d.`name`), ", ", RTRIM(s.`scname`)
                ) as `name`,
                d.`code`
              FROM
                `spr_district` as d
              LEFT JOIN
                `spr_socr` as s ON
                  s.`id` = d.`socr` AND
                  s.`level` = 2
              WHERE
                d.`name` LIKE "'.$_REQUEST['district_text'].'%" AND
                d.`code` LIKE "'.$region.'%"
            ') or $jsonERR[] = mysql_error();
            while ($result = mysql_fetch_assoc($query)) {
              $json[$result['code']] = $result['name'];
            }
          }
          break;
          
        case 'city': // город
          if (isset($_REQUEST['locality']) || isset($_REQUEST['street'])) break; // если есть НП или улица, ничего не ищем
          if (isset($_REQUEST['region']) && !isset($_REQUEST['district'])) {  // если есть регион, но нет района
            $code = substr($_REQUEST['region'], 0, 2);
            if (in_array($code, array(77, 78, 91, 99))) { // проверяем на город федерального значения
              $district = $code;
            }
          }
          if (isset($_REQUEST['district']) || isset($_REQUEST['region']) || isset($district)) {
            if (!isset($district)) {
              if (isset($_REQUEST['district'])) {
                $district = substr($_REQUEST['district'], 0, 5);
              } else {
                $district = substr($_REQUEST['region'], 0, 2).'000';
              }
            }
            require_once(KERNEL.'connection.php');
            $query = mysql_query('
              SELECT
                c.`id`,
                CONCAT(
                  RTRIM(c.`name`), ", ", RTRIM(s.`scname`)
                ) as `name`,
                c.`code`
              FROM
                `spr_city` as c
              LEFT JOIN
                `spr_socr` as s ON
                  s.`id` = c.`socr` AND
                  s.`level` = 3
              WHERE
                c.`name` LIKE "'.$_REQUEST['city_text'].'%" AND
                c.`code` LIKE "'.$district.'%"
            ') or $jsonERR[] = mysql_error();
            while ($result = mysql_fetch_assoc($query)) {
              $json[$result['code']] = $result['name'];
            }
          }
          break;
          
        case 'locality': // населенный пункт
          if (isset($_REQUEST['street'])) break;  // если есть улица, ничего не ищем
          if (isset($_REQUEST['region']) && !isset($_REQUEST['city']) && !isset($_REQUEST['district'])) {  // если есть регион, но нет района и НП
            $code = substr($_REQUEST['region'], 0, 2);
            if (in_array($code, array(77, 78, 91, 99))) { // проверяем на город федерального значения
              $city = $code;
            }
          }
          if (isset($_REQUEST['city']) || isset($_REQUEST['district']) || isset($city)) {
            if (!isset($city)) {
              if (isset($_REQUEST['city'])) {
                $city = substr($_REQUEST['city'], 0, 8);
              } else {
                $city = substr($_REQUEST['district'], 0, 5).'000';
              }
            }
            require_once(KERNEL.'connection.php');
            $query = mysql_query('
              SELECT
                l.`id`,
                CONCAT(
                  RTRIM(l.`name`), ", ", RTRIM(s.`scname`)
                ) as `name`,
                l.`code`
              FROM
                `spr_locality` as l
              LEFT JOIN
                `spr_socr` as s ON
                  s.`id` = l.`socr` AND
                  s.`level` = 4
              WHERE
                l.`name` LIKE "'.$_REQUEST['locality_text'].'%" AND
                l.`code` LIKE "'.$city.'%"
            ') or $jsonERR[] = mysql_error();
            while ($result = mysql_fetch_assoc($query)) {
              $json[$result['code']] = $result['name'];
            }
          }
          break;
        
        case 'street':
          if (isset($_REQUEST['region'])) {  // если есть регион, но нет района и НП
            $code = substr($_REQUEST['region'], 0, 2);
            if (in_array($code, array(77, 78, 91, 99))) { // проверяем на город федерального значения
              $city = $code;
            }
          }
          if (isset($_REQUEST['city']) || isset($_REQUEST['locality']) || isset($city)) {
            if (!isset($city)) {
              if (isset($_REQUEST['locality'])) {
                $city = substr($_REQUEST['locality'], 0, 11);
              } else {
                $city = substr($_REQUEST['city'], 0, 8).'000';
              }
            }
            require_once(KERNEL.'connection.php');
            $query = mysql_query('
              SELECT
                str.`id`,
                CONCAT(
                  RTRIM(str.`name`), ", ", RTRIM(s.`scname`)
                ) as `name`,
                str.`code`
              FROM
                `spr_street` as str
              LEFT JOIN
                `spr_socr` as s ON
                  s.`id` = str.`socr` AND
                  s.`level` = 5
              WHERE
                str.`name` LIKE "'.$_REQUEST['street_text'].'%" AND
                str.`code` LIKE "'.$city.'%"
            ') or $jsonERR[] = mysql_error();
            while ($result = mysql_fetch_assoc($query)) {
              $json[$result['code']] = $result['name'];
            }
          }
          break;
      }
    } else {
      handling_address($array);
      if (count($jsonERR) == 0) {
        $id = search_address($array);
         if ($id === false) {
          $id = add_object('o_address');
          $jsonERR[] = update_object('o_address', $id, $array);
        }
      }
      /*$jsonERR[] = $array['time'];
      $id = search_address($array);
      if ($id === false) {
        $id = add_object('o_address');
        $jsonERR[] = update_object('o_address', $id, $array);
      }*/
    }
  }
}

if (isset($_REQUEST['phone_search'])) {
  if ($json = detect_mob_operator($_REQUEST['phone_search'])) {
    $jsonMSG = 'Принадлежность телефонного номера установлена! <br/> <center>Не забудьте сохранить данные!</center>';
  } else {
    $jsonERR[] = 'Принадлежность телефонного номера неизвестна! <br/> <center>Мы уже знаем об этой проблеме. Повторите попытку позже.</center>';
    //mail('centrori@kir.mvd.ru', 'Operator detector', 'unknown number "'.$_REQUEST['phone_search'].'"', 'From: server');
  }
}

if (isset($_FILES['qiwi_file_analyse'])) {
  while(true) {
    $jsonData = false;
    $max_size = pow(2, 20) * 2; // 2 мегабайта
    $size = $_FILES['qiwi_file_analyse']['size'];
    $name = $_FILES['qiwi_file_analyse']['name'];
    $tmp_name = $_FILES['qiwi_file_analyse']['tmp_name'];
    $tmp = pathinfo($name);
    if (in_array(mb_strtolower($tmp['extension'], 'UTF-8'), array('xls'))) {
      if (($size > '0') && ($size < $max_size)) {
        if (is_uploaded_file($tmp_name)) {
          if (empty($_SESSION['dir_session'])) {
            $_SESSION['dir_session'] = session_save_path()."_tmp_".session_id().'\\'; // временный каталог сессии
            if (!is_dir($_SESSION['dir_session'])) {
              mkdir($_SESSION['dir_session']); // создаем его
            }
          }
          $file = $_SESSION['dir_session'].mb_convert_encoding($name, 'Windows-1251', 'UTF-8');
          if (!copy($tmp_name, $file)) {
            $jsonERR[] = 'Ошибка копирования файла!';
            break;
          }
        } else {
          $jsonERR[] = 'Ошибка загрузки файла!';
          break;
        }
      } else {
        $jsonERR[] = 'Файл имеет 0 размер или больше 2 мб!';
        break;
      }
    } else {
      $jsonERR[] = 'Неверный тип файла. Допустимы файлы "doc", "docx", "rtf".';
      break;
    }
    
    set_time_limit(0);
    require_once(KERNEL.'Excel/reader.php');

    $data = new Spreadsheet_Excel_Reader();
    $data->setOutputEncoding('utf-8');
    $data->read($file);
    
    $account = preg_replace('/\D/', '', $data->sheets[0]['cells'][1][1]);
    /*if (strlen($account) > 10) {
      $account = substr($account, strlen($account) - 10, 10);
    }*/
    
    $sheets = array(
      'info' => 'Рег.данные', 
      'cards' => 'Банковские карты', 
      'qvx' => 'Карты QIWI', 
      'in' => 'Входящие платежи', 
      'out' => 'Исходящие платежи', 
      'all (in and out)' => 'Платежи', 
      'mobile' => 'Мобильные платежи'
    );
    
    $sheet_cols = array(
      'Дата создания кошелька' => 'create_date',
      'IP регистрации' => 'ip_reg',
      'Терминал' => 'terminal',
      'Адрес терминала' => 'terminal_address',
      'Тип терминала' => 'terminal_type',
      'Провайдер' => 'provider',
      'Дата последнего входа' => 'last_enter_date',
      'IP последнего входа' => 'last_enter_ip',
      'SMS подтверждение' => 'SMS_confirm',
      'Статус кошелька' => 'status',
      'Email' => 'e-mail',
      'Состояние идентификации' => 'identification_status',
      'ИН ФИО ' => 'IN_fio',
      'ФИО' => 'fio',
      'ИН Фамилия' => 'surname',
      'Фамилия отправителя' => 'sender_surname',
      'Фамилия получателя' => 'reciever_surname',
      'ИН Имя' => 'name',
      'Имя отправителя' => 'sender_name',
      'Имя получателя' => 'reciever_name',
      'ИН Отчество' => 'fath_name',
      'Отчество отправителя' => 'sender_f_name',
      'Отчество получателя' => 'reciever_f_name',
      'ИН Дата рождения' => 'borth',
      'ИН Серия и номер паспорта' => 'IN_passport',
      'ИН Дата выдачи паспорта' => 'IN_passport_date',
      'ИН Орган, выдавший ДУЛ' => 'IN_passport_department',
      'ИН Код подразделения' => 'IN_passport_code',
      'ИН Гражданство' => 'IN_citizenship',
      'ИН Адрес регистрации' => 'IN_reg_addres',
      'ИН ИНН' => 'inn',
      'ИН СНИЛС' => 'snils',
      'ИН ОМС' => 'oms',
      'Состояние' => 'state',
      'Баланс' => 'balance',
      'Номер карты' => 'card_number',
      'Тип карты' => 'card_system',
      'Страна банка' => 'bank_country',
      'ФИО' => 'fio',
      'Банк' => 'bank',
      'IP привязки' => 'reg_ip',
      'Создана' => 'create_date',
      'Привязана' => 'reg_date',
      'Одобрена' => 'okay_date',
      'Активирована' => 'activation_date',
      'Удалена' => 'del_date',
      'Причина удаления' => 'del_reason',
      'Оборот' => 'turnover',
      'Документ' => 'document',
      'Адрес' => 'address',
      'Дата' => 'date',
      'Источник' => 'source',
      'Номер' => 'number',
      'Тип' => 'type',
      'Тип операции' => 'operation_type',
      'Счет' => 'account',
      'Тип счета' => 'account_type',
      'Сумма' => 'sum',
      'Статус' => 'status',
      'Комментарий' => 'comment',
      'Приложение' => 'supplement',
      'UDID' => 'UDID',
      'IP-адрес' => 'ip_address',
      'Пополнение' => 'refill',
      'Метод оплаты' => 'payment_method'
    );
    
    $not_valid_values = array('n/a'); // невалидные значения
    
    unset($_SESSION['qiwi_info']);
    
    $_SESSION['qiwi_info']['account'] = $account;
    $_SESSION['qiwi_info']['file'] = $file;
    
    foreach($data->boundsheets as $i => $sheet) {
      $_SESSION['qiwi_info']['sheets'][$i] = $sheets[$sheet['name']]; // переименованные названия листов
    }
    
    for($s = 0; $s < count($_SESSION['qiwi_info']['sheets']); $s++) { // цикл по листам
      
      if ($s == 0) {  // лист 'Рег.данные'
        foreach($data->sheets[$s]['cells'] as $i => $row) { // цикл по строкам
          switch ($i) {
            case 1:
              break;
            case 11:
              $face = array(null, null, null); // пустой массив ФИО
              if (!empty($row[2])) {           // если ФИО указаны
                $_f = explode(' ', $row[2]);   // разбиваем ФИО в массив
                $face[0] = $_f[0];
                $face[1] = $_f[1];
                unset($_f[0], $_f[1]);
                $face[2] = implode(' ', $_f);
              }
              $_tmp['ИН Фамилия'] = $face[0];
              $_tmp['ИН Имя'] = $face[1];
              $_tmp['ИН Отчество'] = $face[2];
              break;
            default:
              $_tmp[$row[1]] = ((!empty($row[2]) and !in_array($row[2], $not_valid_values)) ? $row[2] : null); // по умолчанию - проверяем на пустоту и валидность строки
              break;
          }
        }

        $data->sheets[$s]['cells'] = array();
        foreach($_tmp as $n => $v) {
          $data->sheets[$s]['cells'][$sheet_cols[$n]]['data'] = $v;
          $data->sheets[$s]['cells'][$sheet_cols[$n]]['name'] = $n;
        }
        $_SESSION['qiwi_info'][$s] = $data->sheets[$s]['cells'];

      } else {
        if ($data->sheets[$s]['numRows'] > 1) {
        
          for($i = 1; $i <= $data->sheets[$s]['numCols']; $i++) {
            $empty_array[$i] = null;
          }
          
          $cntr = 1;
          foreach($data->sheets[$s]['cells'] as $i => $row) {  // цикл по строкам
            switch ($i) {
              case 1:
                break;
              case 2:
                $header = $row;
                break;
              default:
                $row = array_replace($empty_array, $row);
                foreach ($row as $n => $v) {
                  $par = $sheet_cols[$header[$n]];
                  $val = ((!empty($v) and !in_array($v, $not_valid_values)) ? $v : null);
                  $_SESSION['qiwi_info'][$s][$cntr][$par] = $val;// qiwi_info->лист->строка->ячейка
                }
                $cntr++;
                break;
            }
          }
          
          $_SESSION['qiwi_info']['headers'][$s] = $header;
          $empty_array = null;
        } else {
          $_SESSION['qiwi_info'][$s] = array();
        }
      }
    }
    
    $json['.file_data'] = analysis_file_QIWI();
    break;
  }
  $add_function_req = '<script type="text/javascript">window.parent.$(\'.uploading_file_block .ajax_response_wait\').remove();</script>';
  $add_function_req .= '<script type="text/javascript">window.parent.$(\'.data_cell\').live_redaction();</script>';
}

if (!empty($_POST['qiwi_info'])) {
  
  $sheet = key($_POST['qiwi_info']);
  $row = key($_POST['qiwi_info'][$sheet]);
  $cell = key($_POST['qiwi_info'][$sheet][$row]);
  $val = $_POST['qiwi_info'][$sheet][$row][$cell];
  
  $_SESSION['qiwi_info'][$sheet][$row][$cell] = $val;
  
}

if (!empty($_POST['analysis_file'])) {
  switch($_POST['analysis_file']) {
    case 'qiwi':
      set_time_limit(0);
      require_once(KERNEL.'connection.php');
      $query = mysql_query('
        SELECT
          w.`id`
        FROM
          `o_wallet` as w
        WHERE
          w.`number` = "'.$_SESSION['qiwi_info']['account'].'" AND
          w.`payment_system` = 13
        LIMIT 1
      ');
      if (!mysql_num_rows($query)) {
        $jsonERR[] = 'Не найден кошелек с таким номером!';
        break;
      }
      
      function f_act($f_act) {  // сетевая активность
        $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
        $f_act['ip'] = inet_ntoa($f_act['ip']);
        mysql_query('
          INSERT INTO
            `l_net_activity`(`'.implode('`, `', array_keys($f_act)).'`, `create_date`, `create_time`, `active_id`)
          VALUES
            ("'.implode('", "', array_values($f_act)).'", current_date, current_time, '.$activity_id.')
          ON DUPLICATE KEY UPDATE
            `registration` = `registration`
        ') or die('1: '.mysql_error());
        $id = mysql_insert_id();
        if ($id == 0) {
          $where = null;
          foreach($f_act as $f => $v) {
            $where[] = '`'.$f.'` = "'.$v.'"';
          }
          $query = mysql_query(' SELECT `id` FROM `l_net_activity` WHERE '.implode(' AND ', $where));
          $result = mysql_fetch_assoc($query);
          $id = $result['id'];
        }
        return $id;
      }
      
      function f_rel($f_rel) {  // связи объектов
        $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
        $fields = $values = null;
        foreach($f_rel as $i => $r) {
          if ($i == 0) $fields = array_keys($r);
          $values[] = '('.implode(', ', array_values($r)).', current_date, current_time, '.$activity_id.')';
        }
        mysql_query('
          INSERT INTO
            `l_relatives`(`'.implode('`, `', array_values($fields)).'`, `create_date`, `create_time`, `active_id`)
          VALUES
            '.implode(', ', $values).'
          ON DUPLICATE KEY UPDATE
            `ais` = `ais`
        ') or die('2: '.mysql_error());
        return mysql_insert_id();
      }
      
      function f_rel_add($f_rel_add) {  // дополнение к связи объектов
        $fields = $values = null;
        foreach($f_rel_add as $i => $r) {
          $tmp = null;
          if (empty($fields)) $fields = array_keys($r);
          foreach($r as $f => $v) {
            $tmp[] = (empty($v)) ? 'NULL' : '"'.$v.'"';
          }
          $values[] = '('.implode(', ', array_values($tmp)).')';
        }
        mysql_query('
          INSERT INTO
            `l_relatives_addition`(`'.implode('`, `', array_values($fields)).'`)
          VALUES
            '.implode(', ', $values).'
          ON DUPLICATE KEY UPDATE
            `type` = `type`
        ') or die('3: '.mysql_error());
        return mysql_insert_id();
      }
      
      function insert_bank_card($f_bc) {
        $comp_keys = array('number');
        $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
        $upd_str = null;
        foreach($f_bc as $f => $v) {
          if (!in_array($f, $comp_keys)) {
            $upd_str[] = '`'.$f.'` = "'.$v.'"';
          }
        }
        mysql_query('
          INSERT INTO
            `o_bank_card`(`'.implode('`, `', array_keys($f_bc)).'`, create_date, create_time, active_id)
          VALUES
            ("'.implode('", "', array_values($f_bc)).'", current_date, current_time, '.$activity_id.')
          ON DUPLICATE KEY UPDATE
            '.(!empty($upd_str) ? implode(', ', $upd_str).', ' : '').'
            `update_date` = current_date,
            `update_time` = current_time,
            `update_active_id` = '.$activity_id.'
        ') or die('4: '.mysql_error());
        $bank_card = mysql_insert_id();
        if ($bank_card == 0) {
          $bank_card = preg_replace('/\*+/', '%', $cells['card_number']);
          $query = mysql_query('
            SELECT bc.`id` FROM `o_bank_card` as bc WHERE 
              bc.`number` LIKE "'.$bank_card.'" LIMIT 1
          ');
          $result = mysql_fetch_assoc($query);
          $bank_card = $result['id'];
        }
        return $bank_card;
      }
      
      function save_object($obj, $data) {
        $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
        $o_list = array(6 => 'o_telephone', 7 => 'o_bank_account', 8 => 'o_bank_card', 9 => 'o_wallet');
        if ($obj == 6) {
          if (!empty($data['number']) and strlen($data['number']) > 10) {
            $data['number'] = substr($data['number'], strlen($data['number']) - 10, 10);
          }
        }
        require_once(KERNEL.'connection.php');
        mysql_query('
          INSERT INTO
            `'.$o_list[$obj].'`(`'.implode('`, `', array_keys($data)).'`, `create_date`, `create_time`, `active_id`)
          VALUES
            ("'.implode('", "', array_values($data)).'", current_date, current_time, '.$activity_id.')
          ON DUPLICATE KEY UPDATE
            `update_date` = current_date, `update_time` = current_time, `update_active_id` = '.$activity_id.'
        ') or die('5: '.mysql_error());
        $id = mysql_insert_id();

        if ($id == 0) {
          $where = null;
          foreach($data as $f => $v) {
            $where[] = '`'.$f.'` = "'.$v.'"';
          }
          $query = mysql_query('
            SELECT `id` FROM `'.$o_list[$obj].'` WHERE '.implode(' AND ', $where).' LIMIT 1
          ') or die(mysql_error());
          $result = mysql_fetch_assoc($query);
          $id = $result['id'];
        }
        return $id;
      }
      
      $result = mysql_fetch_assoc($query);
      
      $wallet = $result['id'];
      $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
      $ais = (isset($_SESSION['crime']['ais'])) ? $_SESSION['crime']['ais'] : 0;
      $f_w = $f_m = $f_p = $f_l = $f_rel = $upd_str = array();
      $comp_key = array(
        'lico' => array('surname', 'name', 'fath_name', 'borth'),
        'document' => array('type', 'serial', 'number'),
        'bank_card' => array('number')
      );
      $query = mysql_query('SELECT `id`, `status` FROM `spr_status`');  // справочник статусов
      while($result = mysql_fetch_assoc($query)) {
        $spr_status[$result['status']] = $result['id'];
      }
      $trans = null;
      foreach($_SESSION['qiwi_info'] as $sheet => $data) {  // цикл по листам
        switch($sheet) {
          case '0':  // Рег.данные
            $f_act = array(   // сетевая активность
              0 => array('registration' => 1, 'object' => $wallet, 'obj_type' => 9), // регистрация
              1 => array('registration' => 0, 'object' => $wallet, 'obj_type' => 9)  // активность
            );
            foreach($data as $f => $v) { // цикл по полям
              if (!empty($v['data'])) {
                switch(true) {
                  case (array_key_exists($f, $f_l)):
                    $f_l[$f] = $v['data'];
                    break;
                  case (array_key_exists($f, $f_act)):
                    $f_act[$f] = $v['data'];
                    break;
                  default:
                    switch($f) {
                      case 'status':
                      case 'identification_status':
                      case 'state':
                        $f_w[$f] = (array_key_exists($v['data'], $spr_status)) ? $spr_status[$v['data']] : 0;
                        break;
                      case 'surname':
                      case 'name':
                      case 'fath_name':
                      case 'inn':
                      case 'snils':
                      case 'oms':
                        $f_l[$f] = mysql_real_escape_string(mb_convert_case($v['data'], MB_CASE_UPPER, 'UTF-8'));
                        break;
                      case 'borth':
                        $f_l['borth'] = date('Y-m-d', strtotime($v['data']));
                        break;
                      case 'IN_reg_addres':
                        $f_l['note'] = 'Зарегистрирован: '.$v['data'];
                        break;
                      case 'SMS_confirm':
                        $f_w['SMS_confirm'] = ($v['data'] == 'Да') ? 1 : 0;
                        break;
                      case 'create_date':
                        $f_w['reg_date'] = $f_act[0]['date'] = date('Y-m-d', strtotime($v['data']));
                        $f_w['reg_time'] = $f_act[0]['time'] = date('H:i:s', strtotime($v['data']));
                        break;
                      case 'last_enter_date':
                        $f_act[1]['date'] = date('Y-m-d', strtotime($v['data']));
                        $f_act[1]['time'] = date('H:i:s', strtotime($v['data']));
                        break;
                      case 'ip_reg':
                        $f_act[0]['ip'] = $v['data'];
                        break;
                      case 'last_enter_ip':
                        $f_act[1]['ip'] = $v['data'];
                        break;
                      case 'e-mail':
                        $f_m['name'] = $v['data'];
                        break;
                      case 'IN_passport':
                        if (strlen($v['data']) == 10) {
                          $f_p['type'] = 1;
                          $f_p['serial'] = substr($v['data'], 0, 4);
                          $f_p['number'] = substr($v['data'], 4, 6);
                        }
                        break;
                      case 'IN_passport_date':
                        $f_p['serial'] = date('Y-m-d', strtotime($v['data']));
                        break;
                      case 'IN_passport_department':
                        $f_p['by_whom'] = $v['data'];
                        break;
                    }
                    break;
                }
              }
            }
            if (empty($f_act[0]['ip'])) unset($f_act[0]); // очищаем активность с пустыми ip-адресами
            if (empty($f_act[1]['ip'])) unset($f_act[1]);
            if (!empty($f_l['surname']) and empty($f_l['fath_name'])) $f_l['fath_name'] = '-'; // проверяем пустое отчество
            
            if (!empty($f_l)) { // лицо
              $upd_str = null;
              foreach($f_l as $f => $v) {
                if (!in_array($f, $comp_key['lico'])) {
                  $upd_str[] = '`'.$f.'` = "'.$v.'"';
                }
              }
              mysql_query('
                INSERT INTO
                  `o_lico`(`'.implode('`, `', array_keys($f_l)).'`, create_date, create_time, active_id)
                VALUES
                  ("'.implode('", "', array_values($f_l)).'", current_date, current_time, '.$activity_id.')
                ON DUPLICATE KEY UPDATE
                  '.(!empty($upd_str) ? implode(', ', $upd_str).', ' : '').'
                  `update_date` = current_date,
                  `update_time` = current_time,
                  `update_active_id` = '.$activity_id.'
              ');
              $face = mysql_insert_id();
              if ($face == 0) {
                $query = mysql_query('
                  SELECT l.`id` FROM `o_lico` as l WHERE 
                    l.`surname` = "'.$f_l['surname'].'" AND 
                    l.`name` = "'.$f_l['name'].'" AND 
                    l.`fath_name` = "'.$f_l['fath_name'].'" AND 
                    l.`borth` = "'.$f_l['borth'].'"
                ');
                $result = mysql_fetch_assoc($query);
                $face = $result['id'];
              }
              $f_rel[] = array('from_obj' => $face, 'from_obj_type' => 1, 'to_obj' => $wallet, 'to_obj_type' => 9, 'type' => 52, 'ais' => $ais);
            }
            
            
            if (!empty($f_p)) { // документ
              $upd_str = null;
              foreach($f_p as $f => $v) {
                if (!in_array($f, $comp_key['document'])) {
                  $upd_str[] = '`'.$f.'` = "'.$v.'"';
                }
              }
              mysql_query('
                INSERT INTO
                  `o_documents`(`'.implode('`, `', array_keys($f_p)).'`, create_date, create_time, active_id)
                VALUES
                  ("'.implode('", "', array_values($f_p)).'", current_date, current_time, '.$activity_id.')
                ON DUPLICATE KEY UPDATE
                  '.(!empty($upd_str) ? implode(', ', $upd_str).', ' : '').'
                  `update_date` = current_date,
                  `update_time` = current_time,
                  `update_active_id` = '.$activity_id.'
              ');
              $doc = mysql_insert_id();
              if ($doc == 0) {
                $query = mysql_query('
                  SELECT `id` FROM `o_documents` WHERE 
                    `type` = 1 AND `serial` = "'.$f_p['serial'].'" AND `number` = "'.$f_p['number'].'"
                ');
                $result = mysql_fetch_assoc($query);
                $doc = $result['id'];
              }
              $f_rel[] = array('from_obj' => $doc, 'from_obj_type' => 4, 'to_obj' => $wallet, 'to_obj_type' => 9, 'type' => 60, 'ais' => $ais);
              if (!empty($face)) {
                $f_rel[] = array('from_obj' => $face, 'from_obj_type' => 1, 'to_obj' => $doc, 'to_obj_type' => 4, 'type' => 8, 'ais' => $ais);
              }
            }
            
            if (!empty($f_w)) { // электронный кошелек
              $upd_str = null;
              foreach($f_w as $f => $v) {
                $upd_str[] = '`'.$f.'` = "'.$v.'"';
              }
              mysql_query('
                UPDATE
                  `o_wallet`
                SET
                  '.implode(', ', $upd_str).'
                WHERE
                  `id` = '.$wallet.'
              ');
            }
            
            if (!empty($f_m)) { // почта
              mysql_query('
                INSERT INTO
                  `o_mail`(`name`, create_date, create_time, active_id)
                VALUES
                  ("'.strtoupper($f_m['name']).'", current_date, current_time, '.$activity_id.')
                ON DUPLICATE KEY UPDATE
                  `update_date` = current_date,
                  `update_time` = current_time,
                  `update_active_id` = '.$activity_id.'
              ');
              $mail = mysql_insert_id();
              if ($mail == 0) {
                $query = mysql_query('
                  SELECT m.`id` FROM `o_mail` as m WHERE 
                    m.`name` = "'.$f_m['name'].'"
                ');
                $result = mysql_fetch_assoc($query);
                $mail = $result['id'];
              }
              $f_rel[] = array('from_obj' => $wallet, 'from_obj_type' => 9, 'to_obj' => $mail, 'to_obj_type' => 10, 'type' => 45, 'ais' => $ais);
            }
            
            if (!empty($f_act)) {  // сетевая активность
              foreach($f_act as $i => $a) {
                f_act($a);
              }
            }
            
            if (!empty($f_rel)) {  // связи объектов
              f_rel($f_rel);
            }
            break;
          
          case '1':  // Банковские карты
          case '2':  // Карты QIWI
            if (!empty($data)) {
              $query = mysql_query('SELECT `id`, UPPER(`system`) as `system` FROM `spr_bank_systems`');  // справочник статус
              while($result = mysql_fetch_assoc($query)) {
                $spr_bs[$result['system']] = $result['id'];
              }
              foreach($data as $row => $cells) {   // цикл по строкам
                $f_bc = $f_act = $rel = $n_act = null;
                $f_rel_add = array(
                  1 => array('type' => 1), // привязка
                  2 => array('type' => 2), // одобрение
                  3 => array('type' => 3),  // активация
                  4 => array('type' => 4)  // удаление
                );
                foreach($cells as $f => $v) {      // цикл по ячейкам
                  $v = mysql_real_escape_string($v);
                  if (!empty($v)) {
                    switch($f) {
                      case 'card_number':
                        $f_bc['number'] = $v;
                        break;
                      case 'card_system':
                        if (strpos($v, 'QV') === 0) {
                          $f_bc['system'] = $spr_bs[strtoupper('Visa')];
                        } else {
                          $f_bc['system'] = $spr_bs[strtoupper($v)];
                        }
                        break;
                      case 'fio':
                        $f_bc['owner'] = strtoupper($v);
                        break;
                      case 'bank':
                        $f_bc['bank'] = $v;
                        break;
                      case 'reg_ip':
                        $f_act['ip'] = $v;
                        break;
                      case 'reg_date':
                        $f_rel_add[1]['date'] = date('Y-m-d', strtotime($v));
                        $f_rel_add[1]['time'] = date('H:i:s', strtotime($v));
                        break;
                      case 'okay_date':
                        $f_rel_add[2]['date'] = date('Y-m-d', strtotime($v));
                        $f_rel_add[2]['time'] = date('H:i:s', strtotime($v));
                        break;
                      case 'activation_date':
                        $f_rel_add[3]['date'] = date('Y-m-d', strtotime($v));
                        $f_rel_add[3]['time'] = date('H:i:s', strtotime($v));
                        break;
                      case 'del_date':
                        $f_rel_add[4]['date'] = date('Y-m-d', strtotime($v));
                        $f_rel_add[4]['time'] = date('H:i:s', strtotime($v));
                        break;
                    }
                  }
                }
                
                
                $bank_card = insert_bank_card($f_bc);
                
                // связь кошелька с картой
                $rel = f_rel(array(0 => array('from_obj' => $bank_card, 'from_obj_type' => 8, 'to_obj' => $wallet, 'to_obj_type' => 9, 'type' => 80, 'ais' => $ais)));
                if ($rel == 0) {
                  $query = mysql_query('
                    SELECT `id` FROM `l_relatives` WHERE 
                      `from_obj` = '.$bank_card.' AND 
                      `from_obj_type` = 8 AND 
                      `to_obj` = '.$wallet.' AND 
                      `to_obj_type` = 9 AND 
                      `type` = 80 AND 
                      `ais` = '.$ais.'
                  ') or die(mysql_error());
                  $result = mysql_fetch_assoc($query);
                  $rel = $result['id'];
                }
                $f_rel_add[1]['relative'] = $f_rel_add[2]['relative'] = $f_rel_add[3]['relative'] = $f_rel_add[4]['relative'] = $rel;
                
                
                if (!empty($f_act['ip'])) {
                  // сетевая активность привязки
                  $n_act = f_act(
                    array(
                      'ip' => $f_act['ip'], 
                      'date' => $f_rel_add[1]['date'], 
                      'time' => $f_rel_add[1]['time'], 
                      'object' => $wallet, 
                      'obj_type' => 9
                    )
                  );
                  if ($n_act == 0) {
                    $query = mysql_query('
                      SELECT `id` FROM `l_net_activity` WHERE 
                        `ip` = INET_ATON("'.$f_act['ip'].'") AND 
                        `date` = "'.$f_rel_add[1]['date'].'" AND
                        `time` = "'.$f_rel_add[1]['time'].'" AND
                        `object` = '.$wallet.' AND
                        `obj_type` = 9
                      LIMIT 1
                    ');
                    $result = mysql_fetch_assoc($query);
                    $n_act = $result['id'];
                  }
                  $f_rel_add[1]['net_activity'] = $n_act;
                  $f_rel_add[2]['net_activity'] = $f_rel_add[3]['net_activity'] = $f_rel_add[4]['net_activity'] = null;
                }
                
                foreach($f_rel_add as $i => $d) {
                  if (empty($d['time']) or empty($d['date'])) unset($f_rel_add[$i]);
                }
                f_rel_add($f_rel_add);
              }
            }
            break;
          
          case '5':  // все платежи
            if (!empty($data)) {
              foreach($data as $row => $cells) {  // цикл по строкам
                $original = array(
                  'net_activity' => null,
                  'date' => null,
                  'time' => null,
                  'source' => null,
                  'number' => null,
                  'type' => null,
                  't_address' => null,
                  'provider' => null,
                  'account' => null,
                  'sum' => null,
                  'comment' => null,
                  'sender_surname' => null,
                  'sender_name' => null,
                  'sender_f_name' => null,
                  'reciever_surname' => null,
                  'reciever_name' => null,
                  'reciever_f_name' => null,
                  'from_obj' => null,
                  'from_obj_type' => null,
                  'to_obj' => null,
                  'to_obj_type' => null
                );
                $n_act = $anoth_obj = $anoth_obj_type = $provider = $account = null;
                foreach($cells as $f => $v) {     // цикл по ячейкам
                  $v = mysql_real_escape_string($v);
                  switch($f) {
                    case 'date':
                      $n_act['date'] = date('Y-m-d', strtotime($v));
                      $n_act['time'] = date('H:i:s', strtotime($v));
                      $original['date'] = date('Y-m-d', strtotime($v));
                      $original['time'] = date('H:i:s', strtotime($v));
                      break;
                    case 'refill':
                      $original['source'] = $v;
                      break;
                    case 'terminal_address':
                      $original['t_address'] = $v;
                      break;
                    case 'supplement':
                      $n_act['application'] = $v;
                      break;
                    case 'ip_address':
                      $n_act['ip'] = $v;
                      break;
                    case 'UDID':
                      $n_act['udid'] = $v;
                      break;
                    case 'number':
                    case 'type':
                    case 'provider':
                    case 'sum':
                    case 'account':
                    case 'comment':
                    case 'sender_surname':
                    case 'sender_name':
                    case 'sender_f_name':
                    case 'reciever_surname':
                    case 'reciever_name':
                    case 'reciever_f_name':
                      $original[$f] = $v;
                      break;
                  }
                }
                if (!empty($cells['operation_type'])) {
                  switch($cells['operation_type']) {
                    case 'in_qiwi': // входящие
                    case 'in_other':
                      $original['to_obj'] = $wallet;
                      $original['to_obj_type'] = 9;
                      $provider = $original['source'];
                      $account = $original['number'];
                      break;
                    case 'out':     // исходящие
                      $original['from_obj'] = $wallet;
                      $original['from_obj_type'] = 9;
                      $provider = $original['provider'];
                      $account = $original['account'];
                      break;
                  }
                }
                if (!empty($original['provider'])) {
                  switch (true){
                    case (preg_match("/(visa direct|mastercard|банк|втб)/ui", $provider)):
                      $obj = null;
                       /*
                         карта - 15, 16, 18, 19
                         счет - 20 - 25
                       */
                      switch(strlen($account)) {
                        case 15:
                        case 16:
                        case 18:
                        case 19:
                          $obj = 8;
                          break;
                        case 20:
                        case 21:
                        case 22:
                        case 23:
                        case 24:
                        case 25:
                          $obj = 7;
                          break;
                      }
                      if (!empty($obj)) {
                        $obj_id = save_object($obj, array('number' => $account));
                        $f_rel[] = array(
                          'from_obj' => $obj_id, 
                          'from_obj_type' => $obj, 
                          'to_obj' => $wallet, 
                          'to_obj_type' => 9, 
                          'type' => (($obj == 7) ? 44 : 43), 
                          'ais' => $ais
                        );
                        $anoth_obj = $obj_id;
                        $anoth_obj_type = $obj;
                      }
                      break;

                    case (preg_match("/(webmoney|qiwi)/ui", $provider, $str)):
                      switch(mb_convert_case($str[0], MB_CASE_LOWER, 'UTF-8')) {
                        case 'webmoney': $ps = 1; break;
                        case 'qiwi': $ps = 13; break;
                      }
                      $wallet2 = save_object(9, array('number' => $account, 'payment_system' => $ps));
                      if (($ps == 13 and strlen($account) == 11) or $ps != 13) {
                        $f_rel[] = array(
                          'from_obj' => $wallet, 
                          'from_obj_type' => 9, 
                          'to_obj' => $wallet2, 
                          'to_obj_type' => 9, 
                          'type' => 46, 
                          'ais' => $ais
                        );
                      }
                      if ($ps == 13 and strlen($account) == 11) {
                        $obj_id = save_object(6, array('number' => $account, 'type' => 1));
                        $f_rel[] = array(
                          'from_obj' => $obj_id, 
                          'from_obj_type' => 6, 
                          'to_obj' => $wallet2, 
                          'to_obj_type' => 9, 
                          'type' => 70, 
                          'ais' => $ais
                        );
                      }
                      $anoth_obj = $wallet2;
                      $anoth_obj_type = 9;
                      break;

                    case (preg_match("/(tele2|билайн|мтс|мегафон|связь|gsm)/ui", $provider)):
                      $obj_id = save_object(6, array('number' => $account, 'type' => 1));
                      $f_rel[] = array(
                        'from_obj' => $obj_id, 
                        'from_obj_type' => 6, 
                        'to_obj' => $wallet, 
                        'to_obj_type' => 9, 
                        'type' => 81, 
                        'ais' => $ais
                      );
                      $anoth_obj = $obj_id;
                      $anoth_obj_type = 6;
                      break;
                      
                    case (preg_match("/(вконтакте|агент@mail.ru)/ui", $provider, $str)):
                      $mess = null;
                      switch(mb_convert_case($str[0], MB_CASE_LOWER, 'UTF-8')) {
                        case 'вконтакте': $mess = 7; break;
                        case 'агент@mail.ru': $mess = 6; break;
                      }
                      if (!preg_match('/[\D]/', $account)) {
                        mysql_query('
                          INSERT INTO
                            `l_messengers`(`account`, `type`, `create_date`, `create_time`, `active_id`)
                          VALUES
                            ("'.$account.'", '.$mess.', current_date, current_time, '.$activity_id.')
                          ON DUPLICATE KEY UPDATE
                            `update_date` = current_date, `update_time` = current_time, `update_active_id` = '.$activity_id.'
                        ') or die('Error 6: '.mysql_error());
                        $id = mysql_insert_id();
                        if ($id == 0) {
                          $query = mysql_query(' SELECT `id` FROM `l_messengers` WHERE `account` = "'.$account.'" AND `type` = '.$mess );
                          $result = mysql_fetch_assoc($query);
                          $id = $result['id'];
                        }
                        mysql_query('
                          INSERT INTO
                            `l_messenger_obj_rel`(`messenger`, `object`, `object_type`, `ais`, `create_date`, `create_time`, `active_id`)
                          VALUES
                            ('.$id.', '.$wallet.', 9, '.$ais.', current_date, current_time, '.$activity_id.')
                        ');
                        $anoth_obj = $id;
                        $anoth_obj_type = 'mg';
                      }
                      break;
                  }
                }
                
                if (empty($original['from_obj']) and empty($original['from_obj_type'])) {
                  $original['from_obj'] = $anoth_obj;
                  $original['from_obj_type'] = $anoth_obj_type;
                } else {
                  $original['to_obj'] = $anoth_obj;
                  $original['to_obj_type'] = $anoth_obj_type;
                }
                
                if (!empty($n_act['ip']) and !empty($original['from_obj']) and !empty($original['from_obj_type'])) {
                  $n_act['object'] = $original['from_obj'];
                  $n_act['obj_type'] = $original['from_obj_type'];
                  $original['net_activity'] = f_act($n_act);
                }
                
                $trans[] = $original;
              }
            }
            break;
          
          case '6':
            if (!empty($data)) {
              foreach($data as $row => $cells) {  // цикл по строкам
                $original = array(
                  'net_activity' => null,
                  'date' => null,
                  'time' => null,
                  'source' => null,
                  'number' => null,
                  'type' => null,
                  't_address' => null,
                  'provider' => null,
                  'account' => null,
                  'sum' => null,
                  'comment' => null,
                  'sender_surname' => null,
                  'sender_name' => null,
                  'sender_f_name' => null,
                  'reciever_surname' => null,
                  'reciever_name' => null,
                  'reciever_f_name' => null,
                  'from_obj' => null,
                  'from_obj_type' => null,
                  'to_obj' => null,
                  'to_obj_type' => null
                );
                foreach($cells as $f => $v) {     // цикл по ячейкам
                  $v = mysql_real_escape_string($v);
                  switch($f) {
                    case 'date':
                      $original['date'] = date('Y-m-d', strtotime($v));
                      $original['time'] = date('H:i:s', strtotime($v));
                      break;
                    case 'terminal':
                      $original['number'] = $v;
                      break;
                    case 'terminal_address':
                      $original['t_address'] = $v;
                      break;
                    case 'terminal_type':
                      $original['source'] = $v;
                      break;
                    case 'sum':
                    case 'comment':
                      $original[$f] = $v;
                      break;
                  }
                }
                $original['to_obj'] = $wallet;
                $original['to_obj_type'] = 9;
                $trans[] = $original;
              }
              
            }
            break;
        }
      }
      
      //******** сохраняем данные ********//
      if (!empty($trans)) {
        $original = array(
          'net_activity',
          'date',
          'time',
          'source',
          'number',
          'type',
          't_address',
          'provider',
          'account',
          'sum',
          'comment',
          'sender_surname',
          'sender_name',
          'sender_f_name',
          'reciever_surname',
          'reciever_name',
          'reciever_f_name',
          'from_obj',
          'from_obj_type',
          'to_obj',
          'to_obj_type'
        );
        $fields = '`'.implode('`, `', $original).'`';
        foreach($trans as $i => $tr) {
          $str = null;
          foreach($tr as $f => $v) {
            $tr[$f] = (empty($v)) ? 'null' : '"'.$v.'"';
            $str[] = '`'.$f.'` = '.$tr[$f];
          }
          mysql_query('
            INSERT INTO 
              `l_transactions`('.$fields.')
            VALUES
              ('.implode(', ', array_values($tr)).')
            ON DUPLICATE KEY UPDATE
              '.implode(', ', $str).'
          ') or die('Error 7: '.mysql_error());
        }
      }
      
      if (!empty($f_rel)) {
        $fields = $str = null;
        foreach($f_rel as $i => $rel) {
          if (empty($fields)) $fields = '`'.implode('`, `', array_keys($rel)).'`';
          $str[] = '('.implode(', ', array_values($rel)).', current_date, current_time, '.$activity_id.')';
        }
        mysql_query('
          INSERT INTO 
            `l_relatives`('.$fields.', `create_date`, `create_time`, `active_id`)
          VALUES
            '.implode(', ', $str).'
          ON DUPLICATE KEY UPDATE
            `ais` = `ais`
        ') or die('Error 8: '.mysql_error());
      }
      //******** сохраняем данные ********
      break;
      
  }
}

if (isset($_POST['select'])) {
  
  switch($_POST['select']) {
    case 'drug_type':
      if (isset($_POST['type']) && is_numeric($_POST['type'])) {
        require_once(KERNEL.'connection.php');
        $query = mysql_query('
          SELECT
            d.`id`, d.`name`
          FROM
            `spr_drugs` as d
          WHERE
            d.`type` = '.$_POST['type'].'
          ORDER BY
            d.`name`
        ');
        $json['div.my_select'] = '<input type="hidden" name="drug" value=""/>';
        $json['div.my_select'] .= '<button type="button" class="my_select_button"><span class="my_select_button_value">Не выбран</span>&nbsp;</button>';
        $json['div.my_select'] .= '<ul class="my_select_list">';
        if (mysql_num_rows($query) > 10) $json['div.my_select'] .= '<li class="my_select_search_list_item skip"><input type="text" class="my_select_search"/></li>';
        $json['div.my_select'] .= '<li class="skip"><a href="#" id="">Не выбран</a></li>';
        while($result = mysql_fetch_assoc($query)) {
          $json['div.my_select'] .= '<li><a href="#" id="'.$result['id'].'">'.$result['name'].'</a></li>';
        }
        $json['div.my_select'] .= '</ul></div>';
      } elseif (isset($_POST['type']) && $_POST['type'] == '') {
        $json['div.my_select'] = '<input type="hidden" name="drug" value=""/>';
        $json['div.my_select'] .= '<button type="button" class="my_select_button"><span class="my_select_button_value">Не выбран</span>&nbsp;</button>';
      }
      break;
    default:
      die();
      break;
  }
}

if (!empty($_POST['restore'])) {
  switch($_POST['restore']) {
    case 'qiwi_info':
      $json['.file_data'] = analysis_file_QIWI();
      $json['.file_data'] .= '<script type="text/javascript">$(\'.data_cell\').live_redaction();</script>';
      break;
  }
}

if (!empty($_POST['method'])) {
  switch($_POST['method']) {
    case 'without_objects':
      if (!isset($_POST['ovd'])) break;
      if (!is_numeric($_POST['ovd'])) break;
      $id = floor(abs($_POST['ovd']));
      if ($id > 0) {
        $ovd = 'e.`ovd_id` = '.$id;
        $group = 'GROUP BY e.`ovd_id`';
      } else {
        $ovd = 'e.`ovd_id` NOT IN(5,6,7,10,60,61,62,63,64,65,66)';
        $group = null;
      }
      require_once(KERNEL.'connection.php');
      $query = mysql_query('
        SELECT
          COUNT(DISTINCT IF(rel.`id` IS NULL, e.`id`, NULL)) as `wo_obj`
        FROM
          `o_event` as e
        LEFT JOIN
          `l_relatives` as rel ON
            (
             rel.`from_obj` = e.`id` AND
             rel.`from_obj_type` = 2
            )
               OR
            (
             rel.`to_obj` = e.`id` AND
             rel.`to_obj_type` = 2
            )
        WHERE
          '.$ovd.' AND
          e.`ais` = '.$_SESSION['crime']['ais'].'
        '.$group.'
      ');
      $result = mysql_fetch_assoc($query);
      $cnt = abs($result['wo_obj']);
      if ($id > 0) {
        if ($cnt == 0) {
          $json['td.without_objects#'.$id] = $cnt;
        } else {
          $json['td.without_objects#'.$id] = '<a href="events_dept.php?ovd_id='.$id.'&type=4">'.$cnt.'</a>';
        }
      } else {
        $json['th.without_objects#'.$id] = $cnt;
      }
      break;
  }
}

$res = array_diff($jsonERR, array(null)); // убираем пустые строки массива ошибок


if (count($res) > 0) { // если ошибки есть
  echo json_encode(array(
    'error' => implode(', ', $jsonERR)
  ));
} else {
  if ($jsonData) {  // если есть данные на JSON
    if ($json != '') $resp['html'] = $json;
    if ($jsonMSG != '') $resp['msg'] = $jsonMSG;
    if (isset($resp)) echo json_encode($resp);
  } else {
    foreach($json as $key => $value) {
      echo '<script type="text/javascript">window.parent.$("'.$key.'").html(\''.$value.'\')</script>';
    }
    if (!empty($jsonMSG)) echo '<script type="text/javascript">window.parent.info_box("handling_done", "'.$jsonMSG.'")</script>';
    if (!empty($add_function_req)) echo $add_function_req;
  }
}
?>
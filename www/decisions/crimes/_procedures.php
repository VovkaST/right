<?php
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
        if (preg_match('/[\D]/', $array["IMEI"])) {
          $jsonERR[] = 'IMEI устройства должен состоять только из цифр.';
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
        if (preg_match('/[^a-zа-я0-9_]/', $array['account'])) {
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
    mail('centrori@kir.mvd.ru', 'Operator detector', 'unknown number "'.$_REQUEST['phone_search'].'"', 'From: server');
  }
}

if (isset($_FILES['file_analyse'])) {
  if (isset($_SESSION['file_analyse'])) {
    if (is_file($_SESSION['dir_session'].$_SESSION['file_analyse'])) unlink($_SESSION['dir_session'].$_SESSION['file_analyse']);
  }
  $file = $_FILES['file_analyse'];
  $file_name = $file["name"];
  $file_tmp_name = $file["tmp_name"];
  $file_size = $file["size"];
  if ($file_size != "0" && $file_size < "2097152") {
    if (is_uploaded_file($file_tmp_name)) {
      $file = uniqid();
      $path = $_SESSION['dir_session'].$file;
      if (copy($file_tmp_name, $path)) {
        $jsonData = false;
        $_SESSION['file_analyse'] = $file;
        $jsonMSG = 'Файл успешно загружен!';
      } else {
        $jsonERR[] = 'Ошибка копирования файла!';
      }
    } else {
      $jsonERR[] = 'Ошибка загрузки файла!';
    }
  } else {
    $jsonERR[] = 'Файл имеет 0 размер или больше 2 мб!';
  }
  if (count($jsonERR) == 0) {
    set_time_limit(0);
    require_once(KERNEL.'Excel/reader.php');

    $data = new Spreadsheet_Excel_Reader();
      $data->setOutputEncoding('utf-8');
      $data->read($path);
      $cols = $data->sheets[0]['numCols'];
      $cntr = 1;
      $json['file_data'] = '<table rules="all" border="1" cellpadding="5" width="100%" class="analysed_file_table">';
      $json['file_data'] .= '<th width="30px">№<br/>п/п</th>';
      for ($c = 1; $c <= $cols; $c++) {
        $json['file_data'] .= '<th>'.analysis_objects($c).'</th>';
      }
      for ($r = 1; $r <= $data->sheets[0]['numRows']; $r++) {
        $json['file_data'] .= '<tr>';
        $json['file_data'] .= '<td align="center">'.$cntr++.'.</td>';
        for ($c = 1; $c <= $cols; $c++) {
          $tmp = preg_replace('/[-"_,]/', ' ', $data->sheets[0]['cells'][$r][$c]);
          $tmp = preg_replace('[\A\xa0]', '', $tmp);
          $tmp = str_replace(array(' Нет', ' нет', 'Ё', 'ё', '  '), array(' ', ' ', 'е', 'е', ' '), $tmp);
          $tmp = trim($tmp);
          $json['file_data'] .= '<td>'.$tmp.'</td>';
          
        }
        $json['file_data'] .= '</tr>';
        if ($r == 10) {
          $json['file_data'] .= '<tr><td align="center">...</td><td colspan="'.$cols.'">Всего извлечено '.$data->sheets[0]['numRows'].' записей</td></tr>';
          break;
        }
      }
      $json['file_data'] .= '</table>';
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
      echo '<script type="text/javascript">window.parent.$(".'.$key.'").html(\''.$value.'\')</script>';
    }
    echo '<script type="text/javascript">window.parent.info_box("handling_done", "'.$jsonMSG.'")</script>';
  }
}
?>
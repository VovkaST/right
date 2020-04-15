<?php
error_reporting(E_ERROR | E_PARSE);  // эта х-ня все исправила

$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once('require.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['weapon'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$jsonERR = array();
$jsonData = true;
$json = $jsonMSG = $updates = '';

foreach ($_POST as $k => $v) {
  if (is_string($v))
    $_POST[$k] = trim($v);
  if (is_array($v)) {
    foreach ($v as $_k => $_v) {
      $_POST[$k][$_k] = trim($_v);
    }
  }
}


// -------- данные с форм -------- //
if (!empty($_POST['form_name'])) {
  try {
    switch ($_POST['form_name']) {
      case 'main_form':
        if (isset($_SESSION['WeaponAccount']['object'])) {
          $acc = unserialize($_SESSION['WeaponAccount']['object']);
        } else {
          $acc = new WeaponAccount();
        }
        if (is_null($acc->get_ovd()))
          $acc->set_ovd($_SESSION['user']['ovd_id']);
        
        if (isset($_POST['number'])) {
          if (!$acc->set_reg_number($_POST['number']))
            throw new Exception($acc->get_last_error());
        }
        
        if (isset($_POST['reg_date'])) {
          if (!$acc->set_reg_date($_POST['reg_date']))
            throw new Exception($acc->get_last_error());
        }
        
        if (isset($_POST['base_receiving'])) {
          if (!$acc->set_base_receiving($_POST['base_receiving']))
            throw new Exception($acc->get_last_error());
        }
        
        if (isset($_POST['purpose_placing'])) {
          if (!$acc->set_purpose_placing($_POST['purpose_placing']))
            throw new Exception($acc->get_last_error());
        }
        
        if (isset($_POST['incoming_number']))
          $acc->set_incoming_number($_POST['incoming_number']);
        
        if (isset($_POST['incoming_date'])) {
          if (!$acc->set_incoming_date($_POST['incoming_date']))
            throw new Exception($acc->get_last_error());
        }
        
        if (!empty($_POST['kusp']['number']) and !empty($_POST['kusp']['date'])) {
          $kusp = new Kusp();
          
          if (!$kusp->set_kusp($_POST['kusp']['number'], $_POST['kusp']['date'], $_SESSION['user']['ovd_id']))
            throw new Exception($kusp->get_last_error());
          
          $acc->set_kusp($kusp);
        } else {
          $acc->unset_kusp();
        }
        
        if (!empty($_POST['cc']['crime_case_number']) and !empty($_POST['cc']['crim_case_date'])) {
          $cc = new CrimeCase();
          $cc->set_case($_POST['cc']['crime_case_number'], $_POST['cc']['crim_case_date'], $_SESSION['user']['ovd_id']);
          $acc->set_crime_case($cc);
        } else {
          $acc->unset_crime_case();
        }
        
        $_SESSION['WeaponAccount']['object'] = serialize($acc);
        break;
      
      case 'firearms':
        if (isset($_SESSION['WeaponAccount']['temp'][1])) {
          $wpn = unserialize($_SESSION['WeaponAccount']['temp'][1]);
        } else {
          $wpn = new Weapon();
        }
        $wpn->set_type(1);
        $wpn->set_quantity_incoming(1);
        $wpn->set_quantity_total(1);
        goto point_overall;
      
      case 'ammunition':
        if (isset($_SESSION['WeaponAccount']['temp'][2])) {
          $wpn = unserialize($_SESSION['WeaponAccount']['temp'][2]);
        } else {
          $wpn = new Weapon();
        }
        $wpn->set_type(2);
        goto point_overall;
      
      case 'explosives':
        if (isset($_SESSION['WeaponAccount']['temp'][3])) {
          $wpn = unserialize($_SESSION['WeaponAccount']['temp'][3]);
        } else {
          $wpn = new Weapon();
        }
        $wpn->set_type(3);
        $wpn->set_quantity_incoming(1);
        $wpn->set_quantity_total(1);
        goto point_overall;
        
      case 'steelarms':
        if (isset($_SESSION['WeaponAccount']['temp'][4])) {
          $wpn = unserialize($_SESSION['WeaponAccount']['temp'][4]);
        } else {
          $wpn = new Weapon();
        }
        $wpn->set_type(4);
        $wpn->set_quantity_incoming(1);
        $wpn->set_quantity_total(1);
        goto point_overall;
        
        point_overall:
        
        if (isset($_POST['sort'])) {
          if (!$wpn->set_sort($_POST['sort']))
            throw new Exception($wpn->get_last_error());
        }
        
        if (isset($_POST['group'])) {
          if (!$wpn->set_group($_POST['group']))
            throw new Exception($wpn->get_last_error());
        }
        
        if (isset($_POST['model'])) {
          if (!$wpn->set_model($_POST['model']))
            throw new Exception($wpn->get_last_error());
        }
        
        if (!empty($_POST['unknown_model']))
          $wpn->set_unknown_model($_POST['unknown_model']);
        
        if (isset($_POST['add_attributes'])) {
          if (!$wpn->set_add_attributes($_POST['add_attributes']))
            throw new Exception($wpn->get_last_error());
        }
        
        if (isset($_POST['manufacture_year'])) {
          if (!$wpn->set_manufacture_year($_POST['manufacture_year']))
            throw new Exception($wpn->get_last_error());
        }
        
        if (isset($_POST['series']))
          $wpn->set_series($_POST['series']);
        
        if (isset($_POST['caliber'])) {
          if (!$wpn->set_caliber($_POST['caliber']))
            throw new Exception($wpn->get_last_error());
        }
        
        if (isset($_POST['number']))
          $wpn->set_number($_POST['number']);
        
        if (isset($_POST['storage']))
          $wpn->set_storage($_POST['storage']);
          
        if (isset($_POST['note']))
          $wpn->set_note($_POST['note']);
        
        if (isset($_POST['barrel_series']))
          $wpn->set_barrel_series($_POST['barrel_series']);
        
        if (isset($_POST['barrel_number']))
          $wpn->set_barrel_number($_POST['barrel_number']);
        
        if (isset($_POST['fore-end_serial']))
          $wpn->set_fore_end_serial($_POST['fore-end_serial']);
        
        if (isset($_POST['fore-end_number']))
          $wpn->set_fore_end_number($_POST['fore-end_number']);
        
        if (isset($_POST['shoe_serial']))
          $wpn->set_shoe_serial($_POST['shoe_serial']);
        
        if (isset($_POST['shoe_number']))
          $wpn->set_shoe_number($_POST['shoe_number']);
        
        if (isset($_POST['quantity_incoming'])) {
          if (!$wpn->set_quantity_incoming($_POST['quantity_incoming']))
            throw new Exception($wpn->get_last_error());
        }
        
        if ($wpn->get_type() == 1) {
          if (isset($_SESSION['WeaponAccount']['object'])) {
            $acc = unserialize($_SESSION['WeaponAccount']['object']);
          } else {
            $acc = new WeaponAccount();
          }
          $acc->append_weapon($wpn);
          
          $_SESSION['WeaponAccount']['object'] = serialize($acc);
          $updates['text']['.item.current .page_title .count'] = ' ('.$acc->get_count(1).')';
        } else {
          $_SESSION['WeaponAccount']['temp'][$wpn->get_type()] = serialize($wpn);
        }
        
        break;
    }
  } catch (Exception $exc) {
    $jsonData = true;
    if ($exc->getMessage() != '')
      $jsonERR[] = $exc->getMessage();
  }
}
// ^^^^^^^^ данные с форм ^^^^^^^^ //
$acc = $wpn = null;

// -------- работа с методами -------- //
if (!empty($_POST['method'])) {
  try {
    switch ($_POST['method']) {
      case 'pages':
        if (!empty($_POST['id']))
          include('page_'.$_POST['id'].'.php');
        $json['#pages'] = $content;
        $updates['removeClass']['.actions_list .item'] = 'current';
        $updates['addClass']['.actions_list .item.'.$_POST['id']] = 'current';
        break;
      
      case 'add':
        if (isset($_SESSION['WeaponAccount']))
          unset($_SESSION['WeaponAccount']);
        $jsonMSG[] = 'ready';
        break;
      
      case 'continue':
        $jsonMSG[] = 'ready';
        break;
        
      case 'add_item':
        switch ($_POST['object']) {
          case 'ammunition':
            if (empty($_SESSION['WeaponAccount']['temp'][2]))
              throw new Exception('Не заполнено ни одно поле.');
            
            $wpn = unserialize($_SESSION['WeaponAccount']['temp'][2]);
            if (is_null($wpn->get_sort()))
              $emptyF[] = '"Вид"';
            if ($wpn->get_sort() != 24 and is_null($wpn->get_group()))
              $emptyF[] = '"К оружию"';
            if ($wpn->get_sort() != 24 and is_null($wpn->get_caliber()))
              $emptyF[] = '"Калибр"';
            if (is_null($wpn->get_quantity_incoming()))
              $emptyF[] = '"Количество"';
            if (is_null($wpn->get_storage()))
              $emptyF[] = '"Хранилище"';
            if (!empty($emptyF))
              throw new Exception('Не заполнены поля: '.implode(', ', $emptyF));
            goto point_overall_add_item;
          
          case 'explosives':
            if (empty($_SESSION['WeaponAccount']['temp'][3]))
              throw new Exception('Не заполнено ни одно поле.');
            $wpn = unserialize($_SESSION['WeaponAccount']['temp'][3]);
            goto point_overall_add_item;
          
          case 'steelarms':
            if (empty($wpn)) {
              if (empty($_SESSION['WeaponAccount']['temp'][4]))
                throw new Exception('Не заполнено ни одно поле.');

              $wpn = unserialize($_SESSION['WeaponAccount']['temp'][4]);
            }
            if (is_null($wpn->get_sort()))
              $emptyF[] = '"Вид"';
            if (!is_null($wpn->get_series()) and is_null($wpn->get_number()))
              $emptyF[] = '"Номер"';
            if (is_null($wpn->get_storage()))
              $emptyF[] = '"Хранилище"';
            if (!empty($emptyF))
              throw new Exception('Не заполнены поля: '.implode(', ', $emptyF));
            goto point_overall_add_item;
            
            point_overall_add_item:
            
            if (isset($_SESSION['WeaponAccount']['object'])) {
              $acc = unserialize($_SESSION['WeaponAccount']['object']);
            } else {
              $acc = new WeaponAccount();
            }
            $n = $acc->append_weapon($wpn);
            $_SESSION['WeaponAccount']['object'] = serialize($acc);
            unset($_SESSION['WeaponAccount']['temp'][$wpn->get_type()]);
            
            switch ($wpn->get_type()) {
              case 2:
                $updates['append']['#pages .wpn_decision .added_items'] = '
                  <div n="'.$n.'" class="added_relation new" title="Несохраненная запись">
                    <span class="order_number"></span>. <b>'.$wpn->get_sort_string().((!is_null($wpn->get_group_string())) ? ' ('.$wpn->get_group_string().')' : null).'</b>: 
                      '.((!is_null($wpn->get_caliber())) ? 'калибр &ndash; <i>'.$wpn->get_caliber().' мм</i>, ' : null).'
                      количество &ndash; <i>'.$wpn->get_quantity_incoming().' '.(($wpn->get_sort() == 24) ? 'гр' : 'шт').'.</i> 
                      Хранилище: '.$wpn->get_storage().'.
                      <i>'.((!is_null($wpn->get_note())) ? '(прим.: '.$wpn->get_note().')' : null).'<i/>
                    <span class="delete_relation" id="'.$n.'" group="ammunition">×</span>
                  </div>
                ';
                break;
              case 3:
              case 4:
                $updates['append']['#pages .wpn_decision .added_items'] = '
                  <div n="'.$n.'" class="added_relation new" title="Несохраненная запись">
                    <span class="order_number"></span>. <b>'.$wpn->get_sort_string().'</b>: 
                      серия &ndash; <i>'.$wpn->get_series().'</i>, 
                      № &ndash; <i>'.$wpn->get_number().'</i>.
                      Хранилище: '.$wpn->get_storage().'.
                      <i>'.((!is_null($wpn->get_note())) ? '(прим.: '.$wpn->get_note().')' : null).'<i/>
                    <span class="delete_relation" id="'.$n.'" group="explosives">×</span>
                  </div>
                ';
                break;
            }
            $updates['resetForm'][] = '#pages .wpn_decision form.sess_form';
            $updates['text']['.item.current .page_title .count'] = ' ('.$acc->get_count($wpn->get_type()).')';
            $updates['order_recalc'][] = '#pages .wpn_decision .added_relation';
            unset($wpn);
            break;
        }
        break;
      
      case 'tmp_decision':
        if (empty($_POST['object'])) {
          if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
            throw new Exception('<b>`tmp_decision` error</b>: parameter `object` is empty.');
          } else {
            throw new Exception('<center><b>Ошибка проверки данных №1!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
          }
        }
        $type = null;
        switch ($_POST['object']) {
          case 'firearms':
            $type = 1;
          case 'ammunition':
            if (empty($type)) $type = 2;
          case 'explosives':
            if (empty($type)) $type = 3;
          case 'steelarms':
            if (empty($type)) $type = 4;
          
            if (isset($_SESSION['WeaponAccount']['tmp_decision'][$type])) {
              $dec = unserialize($_SESSION['WeaponAccount']['tmp_decision'][$type]);
            } else {
              $dec = new WeaponDecision();
              if ($type != 2) $dec->set_quantity();
            }
            break;
          default:
            if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
              throw new Exception('<b>`tmp_decision` error</b>: parameter `object` is undefined.');
            } else {
              throw new Exception('<center><b>Ошибка проверки данных №2!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
            }
            break;
        }
        if (isset($_POST['date'])) {
          if (!$dec->set_date($_POST['date']))
            throw new Exception($dec->get_last_error());
        }
        if (isset($_POST['decision'])) {
          if (!$dec->set_decision($_POST['decision']))
            throw new Exception($dec->get_last_error());
        }
        if (isset($_POST['page'])) {
          if (!$dec->set_page($_POST['page']))
            throw new Exception($dec->get_last_error());
        }
        if (isset($_POST['number']))
          $dec->set_number($_POST['number']);
        if (isset($_POST['case']))
          $dec->set_case($_POST['case']);
        
        if (!empty($_POST['weapons'])) {
          $tmp_wpn = null;
          foreach ($_POST['weapons'] as $n => $id) {
            if (isset($_POST['quantity'][$id])) {
              $tmp_wpn[$id] = $_POST['quantity'][$id];
            } else {
              if ($type != 2) {
                $tmp_wpn[$id] = 1;
              } else {
                continue;
              }
            }
            $dec->set_tmp_wpn($tmp_wpn);
          }
        } else {
          $dec->set_tmp_wpn();
        }
        $_SESSION['WeaponAccount']['tmp_decision'][$type] = serialize($dec);
        break;
        
      case 'save_decision':
        switch ($_POST['object']) {
          case 'firearms':
            $type = 1;
          case 'ammunition':
            if (empty($type)) $type = 2;
          case 'explosives':
            if (empty($type)) $type = 3;
          case 'steelarms':
            if (empty($type)) $type = 4;
          
            if (empty($_SESSION['WeaponAccount']['tmp_decision'][$type]))
              throw new Exception('<b>Решение</b>: нечего сохранять.');
            $dec = unserialize($_SESSION['WeaponAccount']['tmp_decision'][$type]);
            break;
          default:
            if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
              throw new Exception('<b>`save_decision` error</b>: parameter `object` is undefined.');
            } else {
              throw new Exception('<center><b>Ошибка проверки данных №3!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
            }
            break;
        }
        if (is_null($dec->get_tmp_wpn()))
          throw new Exception('<b>Решение</b>: не выбрано ни одного наименования.');
          
        if (isset($_SESSION['WeaponAccount']['object'])) {
          $acc = unserialize($_SESSION['WeaponAccount']['object']);
        } else {
          if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
            throw new Exception('<b>`save_decision` error</b>: не найден объект "Квитанция"!');
          } else {
            throw new Exception('<center><b>Ошибка проверки данных №4!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
          }
        }
        
        if (!$dec->save()) {
          $dec->db->rollback();
          throw new Exception($dec->get_last_error());
        }
        
        $dec->db->commit();
        $updates['resetForm'][] = '#pages .wpn_history form';
        foreach ($dec->get_tmp_wpn() as $n => $id) {
          $wpn = new Weapon($n);
          if (!in_array($dec->get_decision(), array(6,7)))
            $updates['text']['.added_relation#'.$n.' .count'] = ' в КХО: '.$wpn->get_quantity_total().' шт.';
        }
        $acc->set_weapon_quantity($type, $wpn->get_id(), $wpn->get_quantity_total());
        
        $updates['append']['#pages .wpn_history .added_items'] = '
                  <div class="added_relation">
                    <span class="order_number"></span>. '.$dec->get_decision_string().' №'.$dec->get_number().' от '.$dec->get_date().':<ul>';
        
        foreach ($dec->get_tmp_wpn() as $w => $q) {
          $w = new Weapon($w);
          $str = null;
          if ($wpn->is_model()) {
            $str = '&nbsp;&nbsp;'.$wpn->get_model_string();
          } else {
            $str = '&nbsp;&nbsp;'.$wpn->get_sort_string().((!is_null($wpn->get_group_string())) ? ' ('.$wpn->get_group_string().')' : null);
          }
          $updates['append']['#pages .wpn_history .added_items'] .= '<li>&nbsp;&nbsp; &ndash;'.$str.', '.$q.' шт.</li>';
        }
        
        $updates['append']['#pages .wpn_history .added_items'] .= '</ul>';
        
        if (!is_null($dec->get_case()))
          $updates['append']['#pages .wpn_history .added_items'] .= '<div class="right-align"><i>Дело №'.$dec->get_case().', стр.'.$dec->get_page().'</i></div>';
        
        $updates['append']['#pages .wpn_history .added_items'] .= '</div>';
        $updates['text']['#pages .bookmark_list .item#history .count'] = '('.count($acc->get_weapon_history($type)).')';
        $updates['order_recalc'][] = '#pages .wpn_history .added_items .added_relation';
        $_SESSION['WeaponAccount']['object'] = serialize($acc);
        unset($_SESSION['WeaponAccount']['tmp_decision'][$type]);
        break;
        
      case 'reset':
        if (empty($_POST['object']))
          break;
        $type = null;
        switch ($_POST['object']) {
          case 'firearms':
            if (isset($_SESSION['WeaponAccount']['object'])) {
              $acc = unserialize($_SESSION['WeaponAccount']['object']);
            } else {
              $acc = new WeaponAccount();
            }
            if (!$acc->unset_weapon(1))
              throw new Exception($acc->get_last_error());
            $_SESSION['WeaponAccount']['object'] = serialize($acc);
            $updates['text']['.item.current .page_title .count'] = '';
            break;
          
          case 'ammunition':
            $type = 2;
          case 'explosives':
            if (empty($type)) $type = 3;
          case 'steelarms':
            if (empty($type)) $type = 4;
            if (isset($_SESSION['WeaponAccount']['temp'][$type]))
              unset($_SESSION['WeaponAccount']['temp'][$type]);
            unset($type);
            break;
        }
        $updates['resetForm'][] = '.'.$_POST['object'].' form.sess_form';
        break;

      case 'account_save':
        if (isset($_SESSION['WeaponAccount']['object'])) {
          $acc = unserialize($_SESSION['WeaponAccount']['object']);
        } else {
          $acc = new WeaponAccount();
        }
        
        if (!$acc->save()) {
          //$acc->db->rollback();
          throw new Exception($acc->get_last_error());
        }
        
        $acc->db->commit();
        $json['.registration_block .response_place'] = '
              <div class="opacity_back" href="index.php"></div><div class="info_box">
                <div class="block_header">Квитанция №'.$acc->get_reg_number().'</div>
                <div class="message">Успешно сохранено!</div>
                <ul class="next_way">
                  <li><a href="receipt.php?id='.$acc->get_id().'">Продолжить редактирование</a></li>
                  <li><a href="index.php">Стартовая страница</a></li>
                </ul>
              </div>';
        if (isset($_SESSION['WeaponAccount']))
          unset($_SESSION['WeaponAccount']);
        break;
    }
  } catch (Exception $exc) {
    $jsonData = true;
    if ($exc->getMessage() != '')
      $jsonERR[] = $exc->getMessage();
  }
}
// ^^^^^^^^ работа с методами ^^^^^^^^ //
$acc = $wpn = null;

// -------- живой поиск по БД -------- //
if (isset($_POST['ajsrch'])) {
  try {
    switch ($_POST['ajsrch']) {
      // -------- справочник оружия -------- //
      case 'fa_model':
        if (!isset($_POST['value']))
          throw new Exception('Недостаточно параметров!');
        
        require(KERNEL.'connect.php');
        $val = $db->real_escape_string($_POST['value']);
        $val = str_replace(' ', '%', $val);
        if ($_POST['value'] != '') 
          $where = 'WHERE wm.`model` LIKE "%'.$val.'%"';
        $query = '
          SELECT
            wm.`id` as `model`,
            CONCAT(
              IF(wm.`sort` IS NULL, "", CONCAT(LOWER(wm.`sort`), ": " )),
              wm.`model`,
              IF(wm.`barrels` IS NULL, "", CONCAT("; стволов: ", wm.`barrels`)),
              CASE
                WHEN wm.`barrels` IS NOT NULL THEN
                  CONCAT( " (", 
                    CONCAT(
                      IF(wm.`barrel_1_caliber` IS NOT NULL, wm.`barrel_1_caliber`, ""),
                      IF(wm.`barrel_1_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_1_cart_length`), "")
                    ),
                    CONCAT(
                      IF(wm.`barrel_2_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_2_caliber`), ""),
                      IF(wm.`barrel_2_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_2_cart_length`), "")
                    ),
                    CONCAT(
                      IF(wm.`barrel_3_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_3_caliber`), ""),
                      IF(wm.`barrel_3_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_3_cart_length`), "")
                    ),
                    CONCAT(
                      IF(wm.`barrel_4_caliber` IS NOT NULL, CONCAT(", ", wm.`barrel_4_caliber`), ""),
                      IF(wm.`barrel_4_cart_length` IS NOT NULL, CONCAT("х", wm.`barrel_4_cart_length`), "")
                    ),
                  ")")
                ELSE
                  ""
              END
            ) as `value`
          FROM
            `spr_weapon_models` as wm
          '.((!empty($where)) ? $where : null).'
        ';
        $result = $db->query($query);
        while ($row = $result->fetch_object()) {
          $json[] = $row->value;
          $group_result[] = $row;
        }
        break;
      // ^^^^^^^^ справочник оружия ^^^^^^^^ //
    }
    
  } catch (Exception $exc) {
    $jsonData = true;
    if ($exc->getMessage() != '')
      $jsonERR[] = $exc->getMessage();
  }
}
// ^^^^^^^^ живой поиск по БД ^^^^^^^^ //


// -------- удаление связей -------- //
if (isset($_POST['del_relation'])) {
  try {
    if (!is_numeric($_POST['del_relation']) or empty($_POST['group']))
      throw new Exception('Че-то херня какая-то... Забаню на фиг!');
    
    switch ($_POST['group']) {
      case 'ammunition':
        $group = 2;
      case 'explosives':
        if (empty($group)) $group = 3;
      case 'steelarms':
        if (empty($group)) $group = 4;
        if (!isset($_SESSION['WeaponAccount']['object']))
          throw new Exception('Нечего удалять... Попробуйте перезагрузить страницу.');
          
        $acc = unserialize($_SESSION['WeaponAccount']['object']);
        if ($acc->unset_weapon($group, $_POST['del_relation'])) {
          $_SESSION['WeaponAccount']['object'] = serialize($acc);
          $updates['fadeOut'][] = '.added_relation[n="'.$_POST['del_relation'].'"]';
          $updates['order_recalc'][] = '#pages .added_relation';
          $cnt = $acc->get_count($group);
          $updates['text']['.item.current .page_title .count'] = (!empty($cnt)) ? ' ('.$cnt.')' : '';
        }
        break;
    }
    $group = null;
  } catch (Exception $exc) {
    $jsonData = true;
    if ($exc->getMessage() != '')
      $jsonERR[] = $exc->getMessage();
  }
}
// ^^^^^^^^ удаление связей ^^^^^^^^ //
$acc = $wpn = null;

// -------- вывод данных -------- //
$res = array_diff($jsonERR, array()); // убираем пустые строки массива ошибок

if (count($res) > 0)
  $resp['error'] = implode(', ', $jsonERR);
  
 
if (!empty($json)) $resp['html'] = $json;
if (!empty($jsonMSG)) $resp['msg'] = implode(', ', $jsonMSG);
if (!empty($group_result)) $resp['group_result'] = $group_result;
if (!empty($updates)) $resp['updates'] = $updates;

if ($jsonData) {  // если есть данные на JSON
  if (!empty($resp)) echo json_encode($resp);
} else {
  if (!empty($resp))
    echo '<script type="text/javascript">window.parent.json_response_handling('.json_encode($resp).')</script>';
  if (!empty($add_function)) echo $add_function;
}
if (!empty($add_function_req)) echo $add_function_req;

// ^^^^^^^^ вывод данных ^^^^^^^^ //
?>
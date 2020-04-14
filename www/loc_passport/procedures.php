<?php
$need_auth = 0;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (!isset($_SESSION['crime']['ais'])) {
  echo json_encode(array('error' => 'Вход в систему не выполнен!'));
  die();
}

$jsonERR = array();

$json = $jsonMSG = '';
$vars = get_defined_vars();

require_once('class.organisation.php');

function get_table_fields($table) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('DESCRIBE `'.$table.'`') or die(mysql_error());
  while ($result = mysql_fetch_assoc($query)) {
    $fields[] = strtolower($result['Field']); // создаем массив полей
  }
  return $fields;
}

function org_types($group, $type = 0) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      s.`id`, s.`type`
    FROM
      `spr_org_types` as s
    WHERE
      s.`owner` = '.$group
  );
  $ret = '<select class="organisation_types_list" name="type">';
  if (mysql_num_rows($query) > 1) $ret .= '<option value=""></option>';
  while($result = mysql_fetch_assoc($query)) {
    $ret .= '<option value="'.$result['id'].'"'.(($type == $result['id']) ? ' selected' : '').'>'.$result['type'].'</option>';
  }
  $ret .='</select>';
  return $ret;
}

function org_type($id) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      s.`id`, s.`type`
    FROM
      `spr_org_types` as s
    WHERE
      s.`id` = '.$id.'
    LIMIT 1
  ');
  $result = mysql_fetch_assoc($query);
  return $result['type'];
}

function get_locality_name($code) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      CASE
        WHEN SUBSTRING(lp.`locality`, 9, 3) <> "000" THEN CONCAT(RTRIM(l.`name`), ", ", RTRIM(ls.`scname`))
        WHEN SUBSTRING(lp.`locality`, 9, 3) = "000" THEN CONCAT(RTRIM(c.`name`), ", ", RTRIM(cs.`scname`))
      END as `name`
    FROM
      `locality_passport` as lp
    LEFT JOIN
      `spr_city` as c ON
        c.`code` = lp.`locality`
      LEFT JOIN
        `spr_socr` as cs ON
          cs.`id` = c.`socr` AND
          cs.`level` = 3
    LEFT JOIN
      `spr_locality` as l ON
        l.`code` = lp.`locality`
      LEFT JOIN
        `spr_socr` as ls ON
          ls.`id` = l.`socr` AND
          ls.`level` = 4
    WHERE
      lp.`locality` = "'.$code.'"
    LIMIT 1
  ');
  $result = mysql_fetch_assoc($query);
  return $result['name'];
}

function full_address($kladr){
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      lp.`id`, lp.`locality` as `code`,
      CONCAT(RTRIM(reg.`name`), " ", RTRIM(sreg.`scname`)) as `region`,
      CONCAT(RTRIM(dist.`name`), " ", RTRIM(sdist.`scname`)) as `district`,
      CONCAT(RTRIM(city.`name`), " ", RTRIM(scity.`scname`)) as `city`,
      CONCAT(RTRIM(loc.`name`), " ", RTRIM(sloc.`scname`)) as `locality`
    FROM
      `locality_passport` as lp
    JOIN
      `spr_region` as reg ON
        SUBSTRING(reg.`code`, 1, 2) LIKE SUBSTRING(lp.`locality`, 1, 2)
      JOIN
        `spr_socr` sreg ON
          sreg.`id` = reg.`socr` AND
          sreg.`level` = 1
    LEFT JOIN
      `spr_district` as dist ON
        SUBSTRING(dist.`code`, 1, 5) LIKE SUBSTRING(lp.`locality`, 1, 5)
      LEFT JOIN
        `spr_socr` sdist ON
          sdist.`id` = dist.`socr` AND
          sdist.`level` = 2
    LEFT JOIN
      `spr_city` as city ON
        SUBSTRING(city.`code`, 1, 8) LIKE SUBSTRING(lp.`locality`, 1, 8)
      LEFT JOIN
        `spr_socr` scity ON
          scity.`id` = city.`socr` AND
          scity.`level` = 3
    LEFT JOIN
      `spr_locality` as loc ON
        SUBSTRING(loc.`code`, 1, 11) LIKE SUBSTRING(lp.`locality`, 1, 11)
      LEFT JOIN
        `spr_socr` sloc ON
          sloc.`id` = loc.`socr` AND
          sloc.`level` = 4
    WHERE
      lp.`locality` = "'.$kladr.'"
    LIMIT 1
  ') or $jsonERR[] = mysql_error();
  $result = mysql_fetch_assoc($query);
  foreach($result as $f => $v) {
    if (in_array($f, array('region', 'district', 'city', 'locality', 'street', 'house', 'flat'))) {
      if ($v != '') $address[] = $v;
    }
  }
  return implode(', ', $address);
}

function codified_address($street) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      reg.`id` as `region`,
      dist.`id` as `district`,
      city.`id` as `city`,
      loc.`id` as `locality`,
      street.`id` as `street`
    FROM
      `spr_street` as street
    LEFT JOIN
      `spr_region` as reg ON
        SUBSTRING(reg.`code`, 1, 2) LIKE SUBSTRING(street.`code`, 1, 2)
    LEFT JOIN
      `spr_district` as dist ON
        SUBSTRING(dist.`code`, 1, 5) LIKE SUBSTRING(street.`code`, 1, 5)
    LEFT JOIN
      `spr_city` as city ON
        SUBSTRING(city.`code`, 1, 8) LIKE SUBSTRING(street.`code`, 1, 8)
    LEFT JOIN
      `spr_locality` as loc ON
        SUBSTRING(loc.`code`, 1, 11) LIKE SUBSTRING(street.`code`, 1, 11)
    WHERE
      street.`code` = "'.$street.'"
  ') or $jsonERR[] = mysql_error();
  $result = mysql_fetch_assoc($query);
  return $result;
}

function local_organisations($locality) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      otg.`id`, otg.`type`,
      COUNT(orgs.`id`) as cnt
    FROM
      `spr_org_types` as otg
    LEFT JOIN
      (SELECT
        org.`id`, org.`type`, ot.`owner`
      FROM
        `o_organisations` as org
      JOIN
        `spr_org_types` as ot ON
          ot.`id` = org.`type`
      LEFT JOIN
        `l_pass_org_relative` as por ON
          por.`organisation` = org.`id`
        JOIN
          `locality_passport` as lp ON
            lp.`id` = por.`locality_passport` AND
            lp.`locality` = "'.$locality.'"
      ) as orgs ON
        orgs.`owner` = otg.`id`
    WHERE
      otg.`owner` IS NULL
    GROUP BY
      otg.`id`
  ');
  $ret = 'Организации, расположенные на территории населенного пункта:';
  $ret .= '<ul class="locality_organisations_list">';
  while($result = mysql_fetch_assoc($query)) {
    $ret .= '<li class="locality_organisations_list_item"><a href="#" id="'.$result['id'].'">'.$result['type'].': '.$result['cnt'].'</a></li>';
  }
  $ret .= '</ul>';
  return $ret;
}

function local_organisations_list($loc, $type) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      org.`id`, org.`title`, ot.`type`, adr.`id` as `address`,
      CONCAT(RTRIM(street.`name`), " ", RTRIM(sstr.`scname`)) as `street`,
      IF(adr.`house` <> 0, CONCAT("д.", IF(adr.`house_lit` <> "0", CONCAT(adr.`house`,"/",adr.`house_lit`), adr.`house`)), NULL) as `house`,
      IF(adr.`flat` <> 0, CONCAT("кв.", IF(adr.`flat_lit` <> "0", CONCAT(adr.`flat`,"/",adr.`flat_lit`), adr.`flat`)), NULL) as `flat`
    FROM
      `o_organisations` as org
    JOIN
      `spr_org_types` as ot ON
        ot.`id` = org.`type`
    LEFT JOIN
      `l_pass_org_relative` as por ON
        por.`organisation` = org.`id`
      JOIN
        `locality_passport` as lp ON
          lp.`id` = por.`locality_passport` AND
          lp.`locality` = "'.$loc.'"
      LEFT JOIN
        `l_relatives` as rel ON
          rel.`to_obj` = org.`id` AND
          rel.`to_obj_type` = 11
        LEFT JOIN
          `o_address` as adr ON
            adr.`id` = rel.`from_obj` AND
            rel.`from_obj_type` = 3
          LEFT JOIN
            `spr_street` as street ON
              street.`id` = adr.`street`
            LEFT JOIN
              `spr_socr` as sstr ON
                sstr.`id` = street.`socr` AND
                sstr.`level` = 5
    WHERE
      ot.`owner` = '.$type.'
    ORDER BY
      `street`, `house`, `flat`
  ');
  $ret = '<div class="section_title">'.org_type($type).'</div>';
  $ret .= '<div class="list_search_box"><input type="text" class="list_search_field"/>';
  $ret .= '<ul class="organisations_list disc_ed_list">';
  while ($result = mysql_fetch_assoc($query)) {
    $address = array();
    foreach($result as $f => $v) {
      if (in_array($f, array('street', 'house', 'flat'))) {
        if ($v != '') $address[] = $v;
      }
    }
    $addr = ((count($address)) ? implode(', ', $address) : 'без адреса');
    $ret .= '<li><a href="popup=org_redaction&org_id='.$result['id'].'&address='.$result['address'].'" class="locality_org_redaction">'.$result['title'].'</a> ('.$result['type'].'), '.$addr.'</li>';
  }
  $ret .= '<li class="skip"><a href="popup=org_add&org_type='.$type.'" class="locality_org_add">Добавить...</a></li>';
  $ret .= '</ul>';
  $ret .= '</div>';
  return $ret;
}

function add_pass_org_relative($loc, $org) {
  $activity_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
  require_once(KERNEL.'connection.php');
  mysql_query('
    INSERT INTO
      l_pass_org_relative(`locality_passport`, `organisation`,
        create_date, create_time, active_id)
    VALUES
      ("'.$loc.'", "'.$org.'", current_date, current_time, "'.$activity_id.'")
  ') or $error = 'Ошибка установления связи: '.mysql_error();
  if (isset($error)) return $error;
}

function check_pass_org_relative($loc, $org) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      COUNT(rel.`id`) as cnt
    FROM
      `l_pass_org_relative` as rel
    WHERE
      rel.`locality_passport` = '.$loc.' AND
      rel.`organisation` = '.$org
  );
  $result = mysql_fetch_assoc($query);
  if ($result['cnt'] > 0) {
    return true;
  } else {
    return false;
  }
}

// -------- сохранение форм -------- //
if (isset($_POST['data_form']) && !isset($_POST['ajsrch']) && !isset($_POST['popup'])) {
  $array = $vars['_REQUEST'];
  $array['ais'] = $_SESSION['crime']['ais'];
  
  switch($array['data_form']) {
    case 'passport':
      if ($array['actual'] == 0 && empty($array['distance'])) {
        $jsonERR[] = 'Не указано расстояние до районного центра!';
        break;
      }
      $id = $array['id'];
      unset($array['id']);
      $jsonERR[] = update_object('locality_passport', $id, $array);
      $jsonMSG = 'Изменения успешно сохранены...';
      break;

    case 'organisation':
      $org = new Organisation;
      
      if (empty($array['type'])) {
        $jsonERR[] = 'Не указан тип организации!';
        break;
      }
      if (empty($array['organisation_text']) || strlen($array['organisation_text']) < 7) {
        $jsonERR[] = 'Не указано наименование организации!';
        break;
      } else {
        $array['title'] = $array['organisation_text'];
        unset($array['organisation_text']);
      }
      if (isset($_POST['org_id'])) $org->set_id($_POST['org_id']);
      if (isset($_POST['address'])) $org->related_address($_POST['address']);
      
      if ($org->org['type'] != $array['type']) $org->set_type($array['type']);
      
      if ($org->req_addr && (empty($array['street']) || empty($array['house']))) {
        $jsonERR[] = 'Не указан адрес организации!';
        break;
      }
      
      if (!empty($array['street']) && empty($array['house'])) {
        $jsonERR[] = 'Не указан номер дома!';
        break;
      }
      
      $org->set_address($array['locality']);
      $org->set_locality($array['locality']);
      
      if ((isset($org->org['id']) && $org->org['title'] != $array['title']) || !isset($org->org['id'])) $org->set_title($array['title']);
      
      
      if (!empty($array['street'])) {
        if (!empty($org->org['id'])) {
          if (
            (
              isset($org->address['kladr']['street']) &&
              (
                $org->address['kladr']['street'] != $array['street'] ||
                $org->address['kladr']['house'] != $array['house'] ||
                $org->address['kladr']['house_lit'] != $array['house_lit'] ||
                $org->address['kladr']['flat'] != $array['flat'] ||
                $org->address['kladr']['flat_lit'] != $array['flat_lit']
              )
            ) || (!isset($org->address['kladr'])) || (!isset($org->address['kladr']['street']))
          ) {
            $org->set_address($array['street']);
            foreach($org->address as $t => $a) {
              $org->address[$t]['house'] = $array['house'];
              $org->address[$t]['house_lit'] = $array['house_lit'];
              $org->address[$t]['flat'] = $array['flat'];
              $org->address[$t]['flat_lit'] = $array['flat_lit'];
            }
          }
        } else {
          $org->set_address($array['street']);
          foreach($org->address as $t => $a) {
            $org->address[$t]['house'] = $array['house'];
            $org->address[$t]['house_lit'] = $array['house_lit'];
            $org->address[$t]['flat'] = $array['flat'];
            $org->address[$t]['flat_lit'] = $array['flat_lit'];
          }
        }
      }
      
      
      /*print_r($org);
      print_r($org);
      die();
      */
      
      $org->save_org();
      
      $json['.locality_organisations'] = local_organisations($_POST['locality']);
      $json['.organisation_list_box'] = local_organisations_list($_POST['locality'], $array['group']);
      break;
  }
}
// ^^^^^^^^ сохранение форм ^^^^^^^^ //


// -------- навигация по районам и населенным пунктам --------- //
if (isset($_POST['district']) && (isset($_POST['navigation']))) {
  require_once(KERNEL.'connection.php');
  if (substr($_POST['district'], 0, 8) == '43000001') {
    $district = '43000001';
  } else {
    $district = substr($_REQUEST['district'], 0, 5);
  }
  $query = mysql_query('
    SELECT
      lp.`locality`,
      IF(LENGTH(lp.`locality`) > 13,
        CASE
          WHEN lp.`locality` = "43000001000001" THEN "Киров, г (Ленинский р-н)"
          WHEN lp.`locality` = "43000001000002" THEN "Киров, г (Октябрьский р-н)"
          WHEN lp.`locality` = "43000001000003" THEN "Киров, г (Первомайский р-н)"
          WHEN lp.`locality` = "43000001000004" THEN "Киров, г (Нововятский р-н)"
        END,
        CASE
          WHEN SUBSTRING(lp.`locality`, 9, 3) <> "000" THEN CONCAT(RTRIM(l.`name`), ", ", RTRIM(ls.`scname`))
          WHEN SUBSTRING(lp.`locality`, 9, 3) = "000" THEN CONCAT(RTRIM(c.`name`), ", ", RTRIM(cs.`scname`))
        END
      ) as `name`
    FROM
      `locality_passport` as lp
    LEFT JOIN
      `spr_city` as c ON
        c.`code` = lp.`locality`
      LEFT JOIN
        `spr_socr` as cs ON
          cs.`id` = c.`socr` AND
          cs.`level` = 3
    LEFT JOIN
      `spr_locality` as l ON
        l.`code` = lp.`locality`
      LEFT JOIN
        `spr_socr` as ls ON
          ls.`id` = l.`socr` AND
          ls.`level` = 4
    WHERE
      lp.`locality` LIKE "'.$district.'%"
    ORDER BY
      lp.`locality`
  ') or $jsonERR[] = mysql_error();
  $json['.locality_block'] = '<div class="section_title list_header">Нас.пункты</div>';
  $json['.locality_block'] .= '<div class="list_search_box"><input type="text" class="list_search_field"/>';
  $json['.locality_block'] .= '<ul class="locality_list disc_ed_list">';
  while ($result = mysql_fetch_assoc($query)) {
    $json['.locality_block'] .= '<li><a href="#" class="locality" id="'.$result['locality'].'">'.$result['name'].'</a></li>';
  }
  $json['.locality_block'] .= '</ul>';
  $json['.locality_block'] .= '</div>';
}

if(isset($_POST['locality']) && isset($_POST['navigation'])) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      lp.`id`, lp.`actual`, lp.`distance`, lp.`house`, lp.`resid_house`, lp.`employable`, lp.`pensioner`, lp.`minor`, lp.`convicted`,
      lp.`mts`, lp.`beeline`, lp.`megafon`, lp.`tele2`
    FROM
      `locality_passport` as lp
    WHERE
      lp.`locality` = "'.$_POST['locality'].'"
    LIMIT 1
  ') or $jsonERR[] = mysql_error();
  $result = mysql_fetch_assoc($query);
  $query = mysql_query('
    SELECT
      otg.`id`, otg.`type`,
      COUNT(org.`id`) as cnt
    FROM
      `spr_org_types` as otg
    JOIN
      `spr_org_types` as ot ON
        ot.`owner` = otg.`id`
    LEFT JOIN
      `o_organisations` as org ON
        org.`type` = ot.`id`
    WHERE
      otg.`owner` IS NULL
    GROUP BY
      otg.`id`
  ') or $jsonERR[] = mysql_error();
  mysql_free_result($query);
  
  $json['.passport_block'] = '<div class="section_title list_header">Паспорт населенного пункта</div>';
  $json['.passport_block'] .= '<div class="organisation_form popup"></div>';
  $json['.passport_block'] .= '<form class="passport_data_form">';
  $json['.passport_block'] .= '<div class="locality_status">';
  $json['.passport_block'] .= '<label><input type="radio" name="actual" value="1" '.(($result['actual']) ? 'checked' : '').'  onclick="$(\'.passport_data.not_actual\').css(\'display\', \'none\'); $(\'.passport_data.actual\').css(\'display\', \'block\')"/> жилой</label>';
  $json['.passport_block'] .= '<label><input type="radio" name="actual" value="0" '.(($result['actual']) ? '' : 'checked').' onclick="$(\'.passport_data.actual\').css(\'display\', \'none\'); $(\'.passport_data.not_actual\').css(\'display\', \'block\')"/> нежилой</label>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '<input type="hidden" name="data_form" value="passport"/>';
  $json['.passport_block'] .= '<input type="hidden" name="id" value="'.$result['id'].'"/>';
  $json['.passport_block'] .= '<input type="hidden" name="locality" value="'.$_POST['locality'].'"/>';
  $json['.passport_block'] .= '<div class="passport_data actual" '.(($result['actual']) ? '' : 'style="display: none;"').'>';
  $json['.passport_block'] .= '<div class="locality_houses">';
  $json['.passport_block'] .= 'Домов: жилых <input type="text" class="quantity" id="house" name="house" value="'.$result['house'].'" autocomplete="off"/>, нежилых <input type="text" class="quantity" name="resid_house" value="'.$result['resid_house'].'"/>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '<div class="locality_population">';
  $json['.passport_block'] .= '<div class="section">Население:</div>';
  $json['.passport_block'] .= '<div class="index">';
  $json['.passport_block'] .= '<div class="row"><div>&mdash; трудоспособные</div><div><input type="text" class="quantity" name="employable" value="'.$result['employable'].'"/></div></div>';
  $json['.passport_block'] .= '<div class="row"><div>&mdash; пенсионеры</div><div><input type="text" class="quantity" name="pensioner" value="'.$result['pensioner'].'" autocomplete="off"/></div></div>';
  $json['.passport_block'] .= '<div class="row"><div>&mdash; малолетние, н/л</div><div><input type="text" class="quantity" name="minor" value="'.$result['minor'].'" autocomplete="off"/></div></div>';
  $json['.passport_block'] .= '<div class="row"><div>&mdash; ранее судимые</div><div><input type="text" class="quantity" name="convicted" value="'.$result['convicted'].'" autocomplete="off"/></div></div>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '<div class="locality_coverage_area">';
  $json['.passport_block'] .= 'Расположен в зоне покрытия операторов сотовой связи:';
  $json['.passport_block'] .= '<div class="operators">';
  $json['.passport_block'] .= '<input type="hidden" name="mts" value="0"/>';
  $json['.passport_block'] .= '<input type="hidden" name="beeline" value="0"/>';
  $json['.passport_block'] .= '<input type="hidden" name="megafon" value="0"/>';
  $json['.passport_block'] .= '<input type="hidden" name="tele2" value="0"/>';
  $json['.passport_block'] .= '<label><input type="checkbox" name="mts" value="1" '.(($result['mts']) ? 'checked' : '').'/> МТС</label>';
  $json['.passport_block'] .= '<label><input type="checkbox" name="beeline" value="1" '.(($result['beeline']) ? 'checked' : '').'/> Билайн</label>';
  $json['.passport_block'] .= '<label><input type="checkbox" name="megafon" value="1" '.(($result['megafon']) ? 'checked' : '').'/> Мегафон</label>';
  $json['.passport_block'] .= '<label><input type="checkbox" name="tele2" value="1" '.(($result['tele2']) ? 'checked' : '').'/> Теле2</label>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '<div class="passport_save_block"><input type="submit" class="passport_save" value="Сохранить"/></div>';
  $json['.passport_block'] .= '<div class="locality_organisations">'.local_organisations($_POST['locality']).'</div>';
  $json['.passport_block'] .= '<div class="organisation_list_box"></div>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '<div class="passport_data not_actual" '.(($result['actual']) ? 'style="display: none;"' : '').'>';
  $json['.passport_block'] .= '<div class="locality_distance">';
  $json['.passport_block'] .= 'Расстояние до районного центра: <input type="text" class="quantity" name="distance" value="'.$result['distance'].'" autocomplete="off"/> км';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '<div class="passport_save_block"><input type="submit" class="passport_save" value="Сохранить"/></div>';
  $json['.passport_block'] .= '</div>';
  $json['.passport_block'] .= '</form>';
}
// ^^^^^^^^ навигация по районам и населенным пунктам ^^^^^^^^ //


if (isset($_POST['popup'])) {
  switch($_POST['popup']) {
    // -------- форма списка организаций --------- //
    case 'org_view':
      $json['.organisation_list_box'] = local_organisations_list($_POST['locality'], $_POST['org_type']);
      break;
    // ^^^^^^^^ форма списка организаций ^^^^^^^^ //
    // -------- всплывающее окно формы организации --------- //
    case 'org_add':
      $json['.organisation_form'] = '<form class="organisation_data_form">';
      $json['.organisation_form'] .= '<input type="hidden" name="data_form" value="organisation"/>';
      $json['.organisation_form'] .= '<input type="hidden" name="org_id" value="'.$_POST['id'].'"/>';
      $json['.organisation_form'] .= '<input type="hidden" name="locality" value="'.$_POST['locality'].'"/>';
      $json['.organisation_form'] .= '<input type="hidden" name="group" value="'.$_POST['org_type'].'"/>';
      $json['.organisation_form'] .= '<div class="section_title list_header">Организация</div>';
      $json['.organisation_form'] .= '<div class="organisation_form_data">';
      $json['.organisation_form'] .= '<div>Тип: '.org_types($_POST['org_type']).'</div>';
      $json['.organisation_form'] .= '<table width="100%" border="0" rules="none">';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td width="50px">Наименование: </td>';
      $json['.organisation_form'] .= '<td>';
      $json['.organisation_form'] .= '<input type="text" name="organisation_text" id="organisation" class="ajax_search" autocomplete="off"/>';
      $json['.organisation_form'] .= '<div class="ajax_search_result"></div>';
      $json['.organisation_form'] .= '<input type="hidden" name="organisation"/>';
      $json['.organisation_form'] .= '</td>';
      $json['.organisation_form'] .= '<td class="wait" width="30px"></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '</table>';
      $json['.organisation_form'] .= '<table width="100%" border="0" rules="none" class="organisation_related_address">';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<th colspan="5" align="center"><i>Расположена по адресу:</i></th>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td colspan="5" align="center">';
      $json['.organisation_form'] .= '<i>'.full_address($_POST['locality']).'</i>';
      $json['.organisation_form'] .= '</td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td width="60px" align="right">Улица:</td>';
      $json['.organisation_form'] .= '<td colspan="3">';
      $json['.organisation_form'] .= '<input type="text" name="street_text" id="street" class="ajax_search" autocomplete="off"/>';
      $json['.organisation_form'] .= '<div class="ajax_search_result"></div>';
      $json['.organisation_form'] .= '<input type="hidden" name="street"/>';
      $json['.organisation_form'] .= '</td>';
      $json['.organisation_form'] .= '<td class="wait" width="30px"></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td align="right">Дом:</td>';
      $json['.organisation_form'] .= '<td><input type="text" name="house" style="width: 30px;" autocomplete="off"/></td>';
      $json['.organisation_form'] .= '<td colspan="2">Литера:<input type="text" name="house_lit" style="width: 30px;" autocomplete="off"/></td>';
      $json['.organisation_form'] .= '<td></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td align="right">Офис/квартира:</td>';
      $json['.organisation_form'] .= '<td width="60px"><input type="text" name="flat" style="width: 30px;" autocomplete="off"/></td>';
      $json['.organisation_form'] .= '<td colspan="2">Литера:<input type="text" name="flat_lit" style="width: 30px;" autocomplete="off"/></td>';
      $json['.organisation_form'] .= '<td></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '</table>';
      $json['.organisation_form'] .= '<div class="organisation_data_format">';
      $json['.organisation_form'] .= 'Формат ввода наименования юридического лица:';
      $json['.organisation_form'] .= '<ul class="organisation_input_format">';
      $json['.organisation_form'] .= '<li>наименование без кавычек</li>';
      $json['.organisation_form'] .= '<li>организация: Олимп, ООО</li>';
      $json['.organisation_form'] .= '<li>индивидуальный перидприниматель: Семенов А.И., ИП</li>';
      $json['.organisation_form'] .= '</ul>';
      $json['.organisation_form'] .= '</div>';
      $json['.organisation_form'] .= '</div>';
      $json['.organisation_form'] .= '<div class="popup_window_button_block">';
      $json['.organisation_form'] .= '<div class="popup_window_button_box save_box"></div>';
      $json['.organisation_form'] .= '<div class="popup_window_button_box close_box"><button type="button" class="popup_window_close">Закрыть</button></div>';
      $json['.organisation_form'] .= '</div>';
      $json['.organisation_form'] .= '</form>';
      break;
      
    case 'org_redaction':
      $org = new Organisation;
      $org->set_id($_POST['org_id']);
      if (isset($org->address['kladr'])) {
        $locality = ((empty($org->address['kladr']['locality'])) ? $org->address['kladr']['locality'] : $org->address['kladr']['city']);
      }
      if (isset($_POST['locality'])) $locality = $_POST['locality'];
      $str = full_address($locality);
      if (!empty($_POST['address'])) {
        $org->related_address($_POST['address']);
        (empty($org->address['kladr']['locality'])) ? $org->set_locality($org->address['kladr']['city']) : $org->set_locality($org->address['kladr']['locality']);
        foreach($org->address['str'] as $f => $v) {
          if (in_array($f, array('region', 'district', 'city', 'locality'))) {
            if ($v != '') $address[] = $v;
          }
        }
        $str = implode(', ', $address);
      }
      
      $json['.organisation_form'] = '<form class="organisation_data_form">';
      $json['.organisation_form'] .= '<input type="hidden" name="data_form" value="organisation"/>';
      $json['.organisation_form'] .= '<input type="hidden" name="org_id" value="'.$org->org['id'].'"/>';
      $json['.organisation_form'] .= '<input type="hidden" name="locality" value="'.$locality.'"/>';
      $json['.organisation_form'] .= '<input type="hidden" name="group" value="'.$org->org['group'].'"/>';
      $json['.organisation_form'] .= '<div class="section_title list_header">Организация</div>';
      $json['.organisation_form'] .= '<div class="organisation_form_data">';
      $json['.organisation_form'] .= '<div>Тип: '.org_types($org->org['group'], $org->org['type']).'</div>';
      $json['.organisation_form'] .= '<table width="100%" border="0" rules="none">';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td width="50px">Наименование: </td>';
      $json['.organisation_form'] .= '<td>';
      $json['.organisation_form'] .= '<input type="text" name="organisation_text" id="organisation" class="ajax_search" autocomplete="off" value="'.$org->org['title'].'"/>';
      $json['.organisation_form'] .= '<div class="ajax_search_result"></div>';
      $json['.organisation_form'] .= '<input type="hidden" name="organisation"/>';
      $json['.organisation_form'] .= '</td>';
      $json['.organisation_form'] .= '<td class="wait" width="30px"></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '</table>';
      $json['.organisation_form'] .= '<table width="100%" border="0" rules="none" class="organisation_related_address">';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<th colspan="5" align="center"><i>Расположена по адресу:</i></th>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td colspan="5" align="center">';
      $json['.organisation_form'] .= '<i>'.$str.'</i>';
      $json['.organisation_form'] .= '</td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td width="60px" align="right">Улица:</td>';
      $json['.organisation_form'] .= '<td colspan="3">';
      $json['.organisation_form'] .= '<input type="text" name="street_text" id="street" class="ajax_search" autocomplete="off"'.((!empty($org->address['str']['street'])) ? ' value="'.$org->address['str']['street'].'"' : '').'/>';
      $json['.organisation_form'] .= '<div class="ajax_search_result"></div>';
      $json['.organisation_form'] .= '<input type="hidden" name="street"'.((!empty($org->address['kladr']['street'])) ? ' value="'.$org->address['kladr']['street'].'"' : '').'/>';
      $json['.organisation_form'] .= '</td>';
      $json['.organisation_form'] .= '<td class="wait" width="30px"></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td align="right">Дом:</td>';
      $json['.organisation_form'] .= '<td><input type="text" name="house" style="width: 30px;" autocomplete="off"'.((!empty($org->address['str']['house'])) ? ' value="'.$org->address['str']['house'].'"' : '').'/></td>';
      $json['.organisation_form'] .= '<td colspan="2">Литера:<input type="text" name="house_lit" style="width: 30px;" autocomplete="off"'.((!empty($org->address['str']['house_lit'])) ? ' value="'.$org->address['str']['house_lit'].'"' : '').'/></td>';
      $json['.organisation_form'] .= '<td></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '<tr>';
      $json['.organisation_form'] .= '<td align="right">Офис/квартира:</td>';
      $json['.organisation_form'] .= '<td width="60px"><input type="text" name="flat" style="width: 30px;" autocomplete="off"'.((!empty($org->address['str']['flat'])) ? ' value="'.$org->address['str']['flat'].'"' : '').'/></td>';
      $json['.organisation_form'] .= '<td colspan="2">Литера:<input type="text" name="flat_lit" style="width: 30px;" autocomplete="off"'.((!empty($org->address['str']['flat_lit'])) ? ' value="'.$org->address['str']['flat_lit'].'"' : '').'/></td>';
      $json['.organisation_form'] .= '<td></td>';
      $json['.organisation_form'] .= '</tr>';
      $json['.organisation_form'] .= '</table>';
      $json['.organisation_form'] .= '<div class="organisation_data_format">';
      $json['.organisation_form'] .= 'Формат ввода наименования юридического лица:';
      $json['.organisation_form'] .= '<ul class="organisation_input_format">';
      $json['.organisation_form'] .= '<li>наименование без кавычек</li>';
      $json['.organisation_form'] .= '<li>организация: Олимп, ООО</li>';
      $json['.organisation_form'] .= '<li>индивидуальный перидприниматель: Семенов А.И., ИП</li>';
      $json['.organisation_form'] .= '</ul>';
      $json['.organisation_form'] .= '</div>';
      $json['.organisation_form'] .= '</div>';
      $json['.organisation_form'] .= '<div class="popup_window_button_block">';
      $json['.organisation_form'] .= '<div class="popup_window_button_box save_box"><button type="submit" class="popup_window_save">Сохранить</button></div>';
      $json['.organisation_form'] .= '<div class="popup_window_button_box close_box"><button type="button" class="popup_window_close">Закрыть</button></div>';
      $json['.organisation_form'] .= '</div>';
      $json['.organisation_form'] .= '</form>';
      break;
    // ^^^^^^^^ всплывающее окно формы организации ^^^^^^^^ //
  }
}


// -------- поиск организации --------- //
if ((isset($_REQUEST['ajsrch']) && $_REQUEST['ajsrch'] == 'organisation') && isset($_REQUEST['organisation_text']) && isset($_REQUEST['locality']) && isset($_REQUEST['type'])) {
  while (true) {
    if (empty($_REQUEST['type'])) {
      $jsonERR[] = 'Выберите тип организации!';
      break;
    }
    $loc = $_REQUEST['locality'];
    require_once(KERNEL.'connection.php');
    $name = mysql_real_escape_string($_REQUEST['organisation_text']);
    $query = mysql_query('
      SELECT
        org.`id`, org.`title`, lp.`locality`
      FROM
        `l_pass_org_relative` as rel
      JOIN
        `o_organisations` as org ON
          rel.`organisation` = org.`id`
      JOIN
        `locality_passport` as lp ON
          lp.`id` = rel.`locality_passport`
      WHERE
        lp.`locality` = "'.$loc.'" AND
        org.`type` = '.$_REQUEST['type'].' AND
        org.`title` LIKE "%'.$name.'%"
    ') or $jsonERR[] = mysql_error();
    if (mysql_num_rows($query) == 0) {
      $result = mysql_fetch_assoc($query);
      if (empty($result['id'])) {
        $json[0] = 'empty';
      } else {
        $json[$result['id']] = $result['title'];
      }
    } else {
      while ($result = mysql_fetch_assoc($query)) {
        $json[$result['id']] = $result['title'];
      }
    }
    break;
  }
}
// ^^^^^^^^ поиск организации ^^^^^^^^ //


// -------- поиск улицы в форме организации --------- //
if (isset($_REQUEST['ajsrch']) && $_REQUEST['ajsrch'] == 'street' && isset($_REQUEST['locality']) && isset($_REQUEST['street_text'])) {
  $code = substr($_REQUEST['locality'], 0, 11);
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
      str.`code` LIKE "'.$code.'%"
  ') or $jsonERR[] = mysql_error();
  while ($result = mysql_fetch_assoc($query)) {
    $json[$result['code']] = $result['name'];
  }
}
// ^^^^^^^^ поиск улицы в форме организации ^^^^^^^^ //


// -------- вывод данных --------- //
$res = array_diff($jsonERR, array(null)); // убираем пустые строки массива ошибок

if (count($res) > 0) { // если ошибки есть
  echo json_encode(array(
    'error' => implode(', ', $jsonERR)
  ));
} else {
  if ($json != '') $resp['html'] = $json;
  if ($jsonMSG != '') $resp['msg'] = $jsonMSG;
  if (isset($resp)) echo json_encode($resp);
}
// ^^^^^^^^ вывод данных ^^^^^^^^ //
?>
<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$page_title = 'Поиск';

$breadcrumbs = array(
  'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела' => 'index.php',
  'Поиск' => ''
);

$s_result = null;
$result_head = '<div class="header"><strong>Результаты поиска</strong> <span>&mdash; __count__</span></div>';

if (!empty($_GET['obj'])) {
  foreach ($_GET as $k => $v) {
    if (in_array($v, array('ст.УК РФ', 'КУСП', 'Рег.№', 'Имя', 'Фамилия', 'Отчество', '__.__.____', 'Организация'))) unset($_GET[$k]);
  }
  $fod = array('type', 'surname', 'name', 'fath_name', 'borth', 'title');
  if ($_GET['obj'] != 2) $_GET['obj'] = 1;
  $s_par = $where = null;
  $get = $_GET;
  
  query_log($_SESSION['activity_id'], urldecode($_SERVER['REQUEST_URI']));
  
  require_once(KERNEL.'connection.php');
  switch($get['obj']) {
    case 1:   // форма поиска По регистрационным данным
      unset($get['obj']);
      $get = array_diff_key($get, array_flip($fod));
      foreach($get as $k => $v) {
        if (!empty($v)) $pars[$k] = mysql_real_escape_string($v);
      }
      if (empty($pars)) break;
      
      if (!empty($pars['date_f']) and empty($pars['date_t'])) {
        $where[] = 'd.`date` = "'.date('Y-m-d', strtotime($pars['date_f'])).'"';
        unset($pars['date_f']);
      } elseif (!empty($pars['date_f']) and !empty($pars['date_t'])) {
        $where[] = 'd.`date` BETWEEN "'.date('Y-m-d', strtotime($pars['date_f'])).'" AND "'.date('Y-m-d', strtotime($pars['date_t'])).'"';
        unset($pars['date_f'], $pars['date_t']);
      } elseif (empty($pars['date_f']) and !empty($pars['date_t'])) {
        unset($pars['date_t']);
      }
      if (!empty($pars['k_kusp'])) {
        $pars['k_kusp'] = preg_replace('/[^\d,]+/', '', $pars['k_kusp']);
        $_t = explode(',', $pars['k_kusp']);
        $_r = null;
        foreach($_t as $kusp) {
          $_r[] = 'k.`kusp` = '.$kusp;
        }
        $where[] = '('.implode(' OR ', $_r).')';
        unset($pars['k_kusp']);
      }
      if (!empty($pars['uk'])) {
        $pars['uk'] = preg_replace('/[^\d,\.]+/', '', $pars['uk']);
        $_t = explode(',', $pars['uk']);
        $_r = null;
        foreach($_t as $uk) {
          $_r[] = 'uuk.`st` LIKE "'.$uk.'%"';
        }
        $where[] = '('.implode(' OR ', $_r).')';
        unset($pars['uk']);
      }
      if (!empty($pars['k_ovd'])) {
        $where[] = 'k.`ovd` = '.$pars['k_ovd'];
        unset($pars['k_ovd']);
      }
      if (!empty($pars['k_date'])) {
        $where[] = 'k.`date` = "'.date('Y-m-d', strtotime($pars['k_date'])).'"';
        unset($pars['k_date']);
      }
      if (!empty($pars['emp_s'])) {
        $where[] = 'd.`emp_s` LIKE "'.$pars['emp_s'].'"';
        unset($pars['emp_s']);
      }
      foreach($pars as $f => $v) {
        $where[] = 'd.`'.$f.'` = '.$v;
      }
      $query = mysql_query('
        SELECT
          d.`id`, 
          IF(d.`status` = 2, CONCAT(d.`reg`, "<br />Доп."), d.`reg`) as `reg`,
          serv.`slujba`, d.`anonymous`, d.`declarer_employeer`,
          DATE_FORMAT(d.`date`, "%d.%m.%Y") as `date`, d.`upk`, d.`is_file`,
          CONCAT(
            SUBSTRING(UPPER(d.`emp_s`), 1, 1), SUBSTRING(LOWER(d.`emp_s`), 2, LENGTH(d.`emp_s`)), " ", 
            SUBSTRING(d.`emp_n`, 1, 1), ".", 
            SUBSTRING(d.`emp_fn`, 1, 1), ".") as `emp`,
          GROUP_CONCAT(
            DISTINCT
              uuk.`st`
            ORDER BY
              uuk.`st`
            SEPARATOR "<br />"
          ) as `uk`,
          GROUP_CONCAT(
            DISTINCT
              IF(k.`ek` IS NOT NULL, CONCAT("<a href=\"/wonc/ek.php?id=", k.`ek`, "\" target=\"_blank\" title=\"Электронный КУСП\">"), ""), kovd.`ovd`, ", КУСП №" , k.`kusp`, " от ", DATE_FORMAT(k.`date`, "%d.%m.%Y"), IF(k.`ek` IS NOT NULL, "</a>", "")
            ORDER BY
              k.`ovd`, k.`date`, k.`kusp`
            SEPARATOR ""
          ) as `kusp`,
          GROUP_CONCAT(
            DISTINCT
              rell.`type`, " - ",
              SUBSTRING(l.`surname`, 1, 1), SUBSTRING(LOWER(l.`surname`), 2, LENGTH(l.`surname`)), " ",
              SUBSTRING(l.`name`, 1, 1), SUBSTRING(LOWER(l.`name`), 2, LENGTH(l.`name`)), " ",
              SUBSTRING(l.`fath_name`, 1, 1), SUBSTRING(LOWER(l.`fath_name`), 2, LENGTH(l.`fath_name`)), " ",
              DATE_FORMAT(l.`borth`, "%d.%m.%Y")
            ORDER BY
              rell.`id`, l.`surname`, l.`name`, l.`fath_name`, l.`borth`
            SEPARATOR "<br />"
          ) as `faces`,
          GROUP_CONCAT(
            DISTINCT
              relo.`type`, " - ", org.`title`
            ORDER BY
              relo.`id`, org.`title`
            SEPARATOR "<br />"
          ) as `orgs`
        FROM
          `l_decisions` as d
        JOIN
          `spr_slujba` as serv ON
            serv.`id_slujba` = d.`service`
        LEFT JOIN
          `l_dec_uk` as du ON
            du.`decision` = d.`id` AND
            du.`deleted` = 0
          LEFT JOIN
            `spr_uk` as uuk ON
              uuk.`id_uk` = du.`uk`
        LEFT JOIN
          `l_dec_kusp` as dk ON
            dk.`decision` = d.`id` AND
            dk.`deleted` = 0
          LEFT JOIN
            `l_kusp` as k ON
              k.`id` = dk.`kusp`
          LEFT JOIN
            `spr_ovd` as kovd ON
              kovd.`id_ovd` = k.`ovd`
        LEFT JOIN
          `l_dec_lico` as dl ON
            dl.`decision` = d.`id` AND
            dl.`deleted` = 0
          LEFT JOIN
            `spr_relatives` as rell ON
              rell.`id` = dl.`type`
          LEFT JOIN
            `o_lico` as l ON
              l.`id` = dl.`face`
        LEFT JOIN
          `l_dec_org` as dorg ON
            dorg.`decision` = d.`id` AND
            dorg.`deleted` = 0
          LEFT JOIN
            `spr_relatives` as relo ON
              relo.`id` = dorg.`type`
          LEFT JOIN
            `o_organisations` as org ON
              org.`id` = dorg.`organisation`
        WHERE
          d.`deleted` = 0 AND
          d.`type` = 1
          '.((!empty($where)) ? ' AND '.implode(' AND ', $where) : '').'
        GROUP BY
          d.`id`
        ORDER BY
          d.`date` DESC, d.`reg` DESC
      ');
      if (mysql_num_rows($query)) {
        $rows = mysql_num_rows($query);
        $result_head = str_replace('__count__', $rows.' '.case_of_word($rows, 'ответ'), $result_head);
        $s_result = '
          '.$result_head.'
          <table class="result_table" border="1" rules="all" cols="7" width="100%" cellpadding="0">
            <tr class="table_head">
              <th height="40px" width="60px">Рег.№</th>
              <th width="120px">Дата решения</th>
              <th width="90px">Служба</th>
              <th width="170px">Сотрудник</th>
              <th width="80px">статья<br>УК</th>
              <th width="40px">п.<br>УПК</th>
              <th colspan="2">КУСП</th>
            </tr>
        ';
        while ($result = mysql_fetch_array($query)) {
          $s_result .= '
            <tr>
              <td colspan="7" class="info_row">
                <table border="0" rules="cols" cols="7" width="100%" cellpadding="5">
                  <tr>
                    <td width="50px" align="center"'.((!empty($result["faces"])) ? ' rowspan="2"' : '').'><b>'.$result["reg"].'</b></td>
                    <td width="110px" align="center"><b>'.$result["date"].'</b></td>
                    <td width="80px" align="center"><b>'.$result["slujba"].'</b></td>
                    <td width="160px"><b>'.$result["emp"].'</b></td>
                    <td width="70px" align="center"><b>'.$result["uk"].'</b></td>
                    <td width="30px" align="center"><b>'.$result["upk"].'</b></td>
                    <td colspan="2">'.$result["kusp"].'</td>
                  </tr>
                  <tr>
                    <td class="face_cell" colspan="7">
                      <div class="info_block">
                        '.((!empty($result["anonymous"])) ? '<i>Анонимное сообщение (заявитель или потерпевший не установлены)</i><br />' : '').'
                        '.((!empty($result["declarer_employeer"])) ? '<i>Рапорт сотрудника/сообщение прокурора</i><br />' : '').'
                        '.((!empty($result["faces"])) ? $result["faces"] : '').'
                        '.((!empty($result["orgs"])) ? $result["orgs"] : '').'
                      </div>
                      <div class="links_block">
                        <a href="download.php?id='.$result["id"].'" target="_blank" class="download_link">
                          <img src="/images/filesave.png" height="20px" alt="Скачать"/>
                          <span class="hint">Скачать</span>
                        </a>
                        <a href="#" class="delete_link" id="'.$result["id"].'">
                          <img src="/images/delete.png" height="20px" alt="Удалить"/>
                          <span class="hint">Удалить</span>
                        </a>
                        <a href="decision.php" class="edit_link" id="'.$result["id"].'" method="edit">
                          <img src="/images/update.png" height="20px" alt="Редактировать"/>
                          <span class="hint">Редактировать</span>
                        </a>
                      </div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          ';
        }
        $s_result .= '</table>';
      } else {
        $s_result = '<span class="result_count">Ответов не получено</span>';
      }
      break;

    case 2:  // форма поиска По установочным данным лица / организации
      unset($get['obj']);
      $get = array_intersect_key($get, array_flip($fod));
      $relation = null;
      foreach($get as $k => $v) {
        if (!empty($v)) $pars[$k] = mysql_real_escape_string(trim($v));
      }
      if (empty($pars)) break;
      if (!empty($pars['title'])) { // поиск по организации
        if (!empty($pars['type'])) {
          $where[] = 'dorg.`type` = "'.$pars['type'].'"';
          $relation = ' AND dorg.`type` = "'.$pars['type'].'"';
          unset($pars['type']);
        }
        foreach($pars as $k => $v) {
          if (!in_array($k, array('title', 'type'))) unset($pars[$k]);
        }
        $where[] = 'o.`title` LIKE "'.$pars['title'].'"';
        $query = mysql_query('
          SELECT
            o.`id`, o.`title`
          FROM
            `o_organisations` as o
          JOIN
            `l_dec_org` dorg ON
              dorg.`organisation` = o.`id`
          WHERE
            dorg.`deleted` = 0
            '.((!empty($where)) ? ' AND '.implode(' AND ', $where) : '').'
          GROUP BY
            o.`title`
          ORDER BY
            `title`
        ');
        if (mysql_num_rows($query)) {
          $rows = mysql_num_rows($query);
          $result_head = str_replace('__count__', $rows.' '.case_of_word($rows, 'ответ'), $result_head);
          $s_result = '
            '.$result_head.'
            <table class="result_table" border="1" rules="all" cols="6" width="100%" cellpadding="0">
              <tr class="table_head">
                <th height="40px" width="150px">Тип</th>
                <th width="60px">Рег.№</th>
                <th width="120px">Дата решения</th>
                <th width="100px">статья<br>УК</th>
                <th width="40px">п.<br>УПК</th>
                <th colspan="2">КУСП</th>
              </tr>
          ';
          while ($result = mysql_fetch_assoc($query)) {
            $s_result .= '
              <tr>
                <td colspan="7">
                  <table border="0" rules="cols" cols="6" width="100%" cellpadding="5">
                    <tr>
                      <td colspan="6">
                        <strong>'.$result['title'].'</strong>
                      </td>
                    </tr>
            ';
            $sub_query = mysql_query('
              SELECT
                relo.`type`, d.`id`, 
                IF(d.`status` = 2, CONCAT(d.`reg`, "<br />Доп."), d.`reg`) as `reg`,
                DATE_FORMAT(d.`date`, "%d.%m.%Y") as `date`, d.`upk`, d.`is_file`,
                GROUP_CONCAT(
                  DISTINCT
                    uuk.`st`
                  ORDER BY
                    uuk.`st`
                  SEPARATOR "<br />"
                ) as `uk`,
                GROUP_CONCAT(
                  DISTINCT
                    IF(k.`ek` IS NOT NULL, CONCAT("<a href=\"/wonc/ek.php?id=", k.`ek`, "\" target=\"_blank\" title=\"Электронный КУСП\">"), ""), kovd.`ovd`, ", КУСП №" , k.`kusp`, " от ", DATE_FORMAT(k.`date`, "%d.%m.%Y"), IF(k.`ek` IS NOT NULL, "</a>", "")
                  ORDER BY
                    k.`ovd`, k.`date`, k.`kusp`
                  SEPARATOR ""
                ) as `kusp`
              FROM
                `l_decisions` as d
              LEFT JOIN
                `l_dec_uk` as du ON
                  du.`decision` = d.`id` AND
                  du.`deleted` = 0
                LEFT JOIN
                  `spr_uk` as uuk ON
                    uuk.`id_uk` = du.`uk`
              LEFT JOIN
                `l_dec_kusp` as dk ON
                  dk.`decision` = d.`id` AND
                  dk.`deleted` = 0
                LEFT JOIN
                  `l_kusp` as k ON
                    k.`id` = dk.`kusp`
                LEFT JOIN
                  `spr_ovd` as kovd ON
                    kovd.`id_ovd` = k.`ovd`
              LEFT JOIN
                `l_dec_org` as dorg ON
                  dorg.`decision` = d.`id` AND
                  dorg.`deleted` = 0
                LEFT JOIN
                  `spr_relatives` as relo ON
                    relo.`id` = dorg.`type`
              WHERE
                d.`deleted` = 0 AND
                d.`type` = 1 AND
                dorg.`organisation` = '.$result['id'].$relation.'
              GROUP BY
                d.`id`
              ORDER BY
                d.`date` DESC, d.`reg` DESC
            ');
            while ($sub_result = mysql_fetch_assoc($sub_query)) {
              $s_result .= '
                  <tr>
                    <td width="140px" align="center">'.$sub_result["type"].'</td>
                    <td width="50px" align="center">'.$sub_result["reg"].'</td>
                    <td width="110px" align="center"><b>'.$sub_result["date"].'</b></td>
                    <td width="90px" align="center"><b>'.$sub_result["uk"].'</b></td>
                    <td width="30px" align="center"><b>'.$sub_result["upk"].'</b></td>
                    <td>
                      '.$sub_result["kusp"].'
                      <div class="links_block">
                        <a href="download.php?id='.$sub_result["id"].'" target="_blank" class="download_link">
                          <img src="/images/filesave.png" height="20px" alt="Скачать" border="none"/>
                          <span class="hint">Скачать</span>
                        </a>
                      </div>
                    </td>
                  </tr>
              ';
            }
            $s_result .= '</table></td></tr>';
          }
          $s_result .= '</table>';
        } else {
          $s_result = '<span class="result_count">Ответов не получено</span>';
        }
      } elseif (!empty($pars['surname']) or !empty($pars['name']) or !empty($pars['fath_name'])) {  // поиск по лицу
        if (!empty($pars['type'])) {
          $where[] = 'dl.`type` = "'.$pars['type'].'"';
          $relation = ' AND dl.`type` = "'.$pars['type'].'"';
          unset($pars['type']);
        }
        if (!empty($pars['borth'])) {
          $where[] = 'l.`borth` = "'.date('Y-m-d', strtotime($pars['borth'])).'"';
          unset($pars['borth']);
        }
        foreach($pars as $f => $v) {
          $where[] = 'l.`'.$f.'` LIKE "'.$v.'"';
        }
        $query = mysql_query('
          SELECT
            l.`id`, 
            CONCAT(SUBSTRING(UPPER(l.`surname`), 1, 1), SUBSTRING(LOWER(l.`surname`), 2, LENGTH(l.`surname`))) as `surname`,
            CONCAT(SUBSTRING(UPPER(l.`name`), 1, 1), SUBSTRING(LOWER(l.`name`), 2, LENGTH(l.`name`))) as `name`,
            CONCAT(SUBSTRING(UPPER(l.`fath_name`), 1, 1), SUBSTRING(LOWER(l.`fath_name`), 2, LENGTH(l.`fath_name`))) as `fath_name`,
            DATE_FORMAT(l.`borth`, "%d.%m.%Y") as `borth`
          FROM
            `o_lico` as l
          JOIN
            `l_dec_lico` as dl ON
              dl.`face` = l.`id`
          WHERE
            dl.`deleted` = 0
            '.((!empty($where)) ? ' AND '.implode(' AND ', $where) : '').'
          GROUP BY
            l.`id`
          ORDER BY
            `surname`, `name`, `fath_name`, `borth`
        ');
            
        if (mysql_num_rows($query)) {
          $rows = mysql_num_rows($query);
          $result_head = str_replace('__count__', $rows.' '.case_of_word($rows, 'ответ'), $result_head);
          $s_result = '
            '.$result_head.'
            <table class="result_table" border="1" rules="all" cols="6" width="100%" cellpadding="0">
              <tr class="table_head">
                <th height="40px" width="150px">Тип</th>
                <th width="60px">Рег.№</th>
                <th width="120px">Дата решения</th>
                <th width="100px">статья<br>УК</th>
                <th width="40px">п.<br>УПК</th>
                <th colspan="2">КУСП</th>
              </tr>
          ';
          while ($result = mysql_fetch_assoc($query)) {
            $s_result .= '
              <tr>
                <td colspan="7">
                  <table border="0" rules="cols" cols="6" width="100%" cellpadding="5">
                    <tr>
                      <td class="face_cell" colspan="6">
                        <strong>'.$result['surname'].' '.$result['name'].' '.$result['fath_name'].' '.$result['borth'].'</strong>
                      </td>
                    </tr>
            ';
            $sub_query = mysql_query('
              SELECT
                rell.`type`, d.`id`, 
                IF(d.`status` = 2, CONCAT(d.`reg`, "<br />Доп."), d.`reg`) as `reg`,
                DATE_FORMAT(d.`date`, "%d.%m.%Y") as `date`, d.`upk`, d.`is_file`,
                GROUP_CONCAT(
                  DISTINCT
                    uuk.`st`
                  ORDER BY
                    uuk.`st`
                  SEPARATOR "<br />"
                ) as `uk`,
                GROUP_CONCAT(
                  DISTINCT
                    IF(k.`ek` IS NOT NULL, CONCAT("<a href=\"/wonc/ek.php?id=", k.`ek`, "\" target=\"_blank\" title=\"Электронный КУСП\">"), ""), kovd.`ovd`, ", КУСП №" , k.`kusp`, " от ", DATE_FORMAT(k.`date`, "%d.%m.%Y"), IF(k.`ek` IS NOT NULL, "</a>", "")
                  ORDER BY
                    k.`ovd`, k.`date`, k.`kusp`
                  SEPARATOR ""
                ) as `kusp`
              FROM
                `l_decisions` as d
              LEFT JOIN
                `l_dec_uk` as du ON
                  du.`decision` = d.`id` AND
                  du.`deleted` = 0
                LEFT JOIN
                  `spr_uk` as uuk ON
                    uuk.`id_uk` = du.`uk`
              LEFT JOIN
                `l_dec_kusp` as dk ON
                  dk.`decision` = d.`id` AND
                  dk.`deleted` = 0
                LEFT JOIN
                  `l_kusp` as k ON
                    k.`id` = dk.`kusp`
                LEFT JOIN
                  `spr_ovd` as kovd ON
                    kovd.`id_ovd` = k.`ovd`
              LEFT JOIN
                `l_dec_lico` as dl ON
                  dl.`decision` = d.`id` AND
                  dl.`deleted` = 0
                LEFT JOIN
                  `spr_relatives` as rell ON
                    rell.`id` = dl.`type`
              WHERE
                d.`deleted` = 0 AND
                d.`type` = 1 AND
                dl.`face` = '.$result['id'].$relation.'
              GROUP BY
                d.`id`
              ORDER BY
                d.`date` DESC, d.`reg` DESC
            ');
            
            while ($sub_result = mysql_fetch_assoc($sub_query)) {
              $s_result .= '
                  <tr>
                    <td width="140px" align="center">'.$sub_result["type"].'</td>
                    <td width="50px" align="center">'.$sub_result["reg"].'</td>
                    <td width="110px" align="center"><b>'.$sub_result["date"].'</b></td>
                    <td width="90px" align="center"><b>'.$sub_result["uk"].'</b></td>
                    <td width="30px" align="center"><b>'.$sub_result["upk"].'</b></td>
                    <td>
                      '.$sub_result["kusp"].'
                      <div class="links_block">
                        <a href="download.php?id='.$sub_result["id"].'" target="_blank" class="download_link">
                          <img src="/images/filesave.png" height="20px" alt="Скачать" border="none"/>
                          <span class="hint">Скачать</span>
                        </a>
                      </div>
                    </td>
                  </tr>
              ';
            }
            $s_result .= '</table></td></tr>';
          }
          $s_result .= '</table>';
        } else {
          $s_result = '<span class="result_count">Ответов не получено</span>';
        }
      }
      break;
  }
}

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>">
  <div class="fieldset search_form">
    <div class="legenda">Поиск:</div>
    <div class="reg_data" <?php if (!empty($_GET['obj']) and $_GET['obj'] == 2) echo 'style="display: none;"' ?>>
      <div class="first_row">
        <div class="field_box">
          <span class="field_name">Рег.№:</span>
          <div class="input_field_block" style="width: 90px;">
            <input type="text" name="reg" autocomplete="off" placeholder="Рег.№" <?php if (!empty($_GET['reg'])) echo 'value="'.$_GET['reg'].'"' ?>/>
          </div>
        </div>
        <div class="fieldset refusal_status">
          <div class="legenda">Статус:</div>
          <label class="row">
            <input type="radio" name="status" value="1" <?php if (!empty($_GET['status']) and $_GET['status'] == 1) echo 'checked' ?>/>Первичный
          </label>
          <label class="row">
            <input type="radio" name="status" value="2" <?php if (!empty($_GET['status']) and $_GET['status'] == 2) echo 'checked' ?>/>По доп.проверке
          </label>
        </div>
        <div class="field_box">
          <span class="field_name">ОВД:</span>
          <?= (empty($_GET['ovd'])) ? my_select('ovd', 'spr_ovd') : my_select('ovd', 'spr_ovd', $_GET['ovd']) ?>
        </div>
        <div class="field_box">
          <span class="field_name">Служба:</span>
          <?= (empty($_GET['service'])) ? my_select('service', 'spr_slujba', null, null, 150) : my_select('service', 'spr_slujba', $_GET['service'], null, 150) ?>
        </div>
      </div>
      <div class="second_row">
        <div class="field_box">
          <span class="field_name">п.</span>
          <?= (empty($_GET['upk'])) ? my_select('upk', 'spr_upk', null, null, 100) : my_select('upk', 'spr_upk', $_GET['upk'], null, 100) ?>
          <span class="field_name">ч.1 ст.24 УПК РФ.</span>
        </div>
        <div class="dec_period">
          <div class="field_box">
            <span class="field_name">Решение:</span>
            <?= (empty($_GET['date_f'])) ? my_date_field('date_f') : my_date_field('date_f', $_GET['date_f']) ?>
          </div>
          <div class="field_box">
            <span class="field_name">&ndash;</span>
            <?= (empty($_GET['date_t'])) ? my_date_field('date_t') : my_date_field('date_t', $_GET['date_t']) ?>
          </div>
        </div>
        <div class="field_box">
          <span class="field_name">Сотрудник:</span>
          <div class="input_field_block">
            <input type="text" name="emp_s" autocomplete="off" placeholder="Фамилия"<?php if (!empty($_GET['emp_s'])) echo ' value="'.$_GET['emp_s'].'"' ?>/>
          </div>
        </div>
      </div>
      <hr color="#C6C6C6" size="0px"/>
      <div class="third_row">
        <div class="field_box">
          <span class="field_name">КУСП: </span>
          <?= (empty($_GET['k_ovd'])) ? my_select('k_ovd', 'spr_ovd') : my_select('k_ovd', 'spr_ovd', $_GET['k_ovd']) ?>
        </div>
        <div class="field_box">
          <?= (empty($_GET['k_date'])) ? my_date_field('k_date') : my_date_field('k_date', $_GET['k_date']) ?>
        </div>
        <div class="field_box">
          <div class="input_field_block">
            <input type="text" name="k_kusp" autocomplete="off" placeholder="КУСП"<?php if (!empty($_GET['k_kusp'])) echo ' value="'.$_GET['k_kusp'].'"' ?>/>
          </div>
        </div>
      </div>
      <hr color="#C6C6C6" size="0px"/>
      <div class="forth_row">
        <div class="field_box">
          <span class="field_name">Ст.УК РФ:</span>
          <div class="input_field_block">
            <input type="text" name="uk" autocomplete="off" placeholder="ст. УК РФ"<?php if (!empty($_GET['uk'])) echo ' value="'.$_GET['uk'].'"' ?>/>
          </div>
        </div>
      </div>
    </div>
    <div class="face_org_data" <?php if (empty($_GET['obj']) or $_GET['obj'] == 1) echo 'style="display: none;"' ?>>
      <div class="relation_block">
        <div class="field_box">
          <span class="field_name">Статус:</span>
          <?= (empty($_GET['type'])) ? my_select('type', 'spr_relatives_decision', null, null, 200) : my_select('type', 'spr_relatives_decision', $_GET['type'], null, 200) ?>
        </div>
      </div>
      <div class="face_block">
        <div class="field_box">
          <span class="field_name">Лицо:</span>
          <div class="input_field_block">
            <input type="text" name="surname" autocomplete="off" placeholder="Фамилия"<?php if (!empty($_GET['surname'])) echo ' value="'.$_GET['surname'].'"' ?>/>
          </div>
        </div>
        <div class="field_box">
          <div class="input_field_block">
            <input type="text" name="name" autocomplete="off" placeholder="Имя"<?php if (!empty($_GET['name'])) echo ' value="'.$_GET['name'].'"' ?>/>
            <div class="ajax_search_result"></div>
          </div>
        </div>
        <div class="field_box">
          <div class="input_field_block">
            <input type="text" name="fath_name" autocomplete="off" placeholder="Отчество"<?php if (!empty($_GET['fath_name'])) echo ' value="'.$_GET['fath_name'].'"' ?>/>
            <div class="ajax_search_result"></div>
          </div>
        </div>
        <div class="field_box">
          <?= (empty($_GET['borth'])) ? my_date_field('borth') : my_date_field('borth', $_GET['borth']) ?>
        </div>
      </div>
      <div class="organisation_block">
        <div class="field_box">
          <span class="field_name">Организация:</span>
          <div class="input_field_block">
            <input type="text" name="title" autocomplete="off" placeholder="Организация"<?php if (!empty($_GET['title'])) echo ' value="'.$_GET['title'].'"' ?>/>
          </div>
        </div>
      </div>
    </div>
    <hr color="#C6C6C6" size="0px"/>
    <div class="bottom_row">
      <div class="switcher_block">
        <label class="reg_data<?php if (empty($_GET['obj']) or $_GET['obj'] == 1) echo ' active' ?>">
          <input type="radio" name="obj" value="1" <?php if (empty($_GET['obj']) or $_GET['obj'] == 1) echo 'checked' ?>/>По регистрационным данным
        </label>
        <label class="face_org_data<?php if (!empty($_GET['obj']) and $_GET['obj'] == 2) echo ' active' ?>">
          <input type="radio" name="obj" value="2" <?php if (!empty($_GET['obj']) and $_GET['obj'] == 2) echo 'checked' ?>/>По установочным данным лица / организации
        </label>
      </div>
      <div class="add_button_box">
        <div class="button_block"><span class="button_name">Искать</span></div>
      </div>
    </div>
    <hr color="#C6C6C6" size="0px"/>
    <div>
      <ul class="prim">
        <li><sup>1</sup> Все поисковые параметры объединяются через условие "И" за исключением лица и организации - поиск осуществляется только по одному из объектов.</li>
        <li><sup>2</sup> Допускается указание нескольких значений КУСП и статей УК через запятую. В этом случаем перечисленные параметры объединяются через условие "ИЛИ".</li>
        <li><sup>3</sup> Знак подстановки для текстовых полей (за исключением поля КУСП) - знак процента "%".</li>
      </ul>
    </div>
  </div>
</form>
<div class="search_results">
  <?= $s_result ?>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
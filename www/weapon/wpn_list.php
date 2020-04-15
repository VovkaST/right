<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['weapon'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Учет "Оружие"' => 'index.php',
  'Единицы оружия' => ''
);
$page_title = 'Учет "Оружие" &ndash; Список единиц оружия';

if ($_SESSION['user']['ovd_id'] != 59) {
  $_GET['ovd'] = $_SESSION['user']['ovd_id'];
}

if (isset($_GET['ovd']) and is_numeric($_GET['ovd'])) {
  $_t = floor(abs($_GET['ovd']));
  $where[] = 'wa.`ovd` = '.$_t;
  $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} else {
  $clause[] = 'Область';
}

if (isset($_GET['year']) and is_numeric($_GET['year'])) {
  $_t = floor(abs($_GET['year']));
  $where[] = 'YEAR(wa.`reg_date`) = '.$_t;
  $clause[] = $_t.' год';
}

if (isset($_GET['ld']) and is_numeric($_GET['ld'])) {
  $_t = floor(abs($_GET['ld']));
  if ($_t == 0) {
    $where[] = 'wd.`decision` IS NULL';
    $clause[] = 'Без решений';
  } else {
    $where[] = 'wd.`decision` = '.$_t;
    $clause[] = get_meaning_from_spr('spr_decision_in_arms', $_t);
  }
}

if (isset($_GET['wtype'])) {
  switch ($_GET['wtype']) {
    case '1':
      $where[] = '(w.`weapon_type` = 1)';
      $clause[] = 'Огнестрельное оружие';
      break;
    
    case '2':
      $where[] = '(w.`weapon_type` = 2)';
      $clause[] = 'Боеприпасы';
      break;
    
    case '3':
      $where[] = '(w.`weapon_type` = 3)';
      $clause[] = 'Взрывные устройства (вещества)';
      break;
    
    case '4':
      $where[] = '(w.`weapon_type` = 4)';
      $clause[] = 'Холодное оружие';
      break;
  }
}

if (isset($_GET['clause'])) {
  $cd = '"'.date('Y-m-d').'"';
  switch ($_GET['clause']) {
    case 'fa':
      $where[] = '(w.`weapon_type` = 1)';
      $clause[] = 'Огнестрельное оружие';
      break;
    
    case 'amm':
      $where[] = '(w.`weapon_type` = 2)';
      $clause[] = 'Боеприпасы';
      break;
    
    case 'exp':
      $where[] = '(w.`weapon_type` = 3)';
      $clause[] = 'Взрывные устройства (вещества)';
      break;
    
    case 'sa':
      $where[] = '(w.`weapon_type` = 4)';
      $clause[] = 'Холодное оружие';
      break;
    
    case 'InOVD':
      $where[] = '(wd.`id` IS NULL OR wd.`decision` = 5)';
      $clause[] = 'Место хранения - ОВД';
      break;
    
    case 'InOVD60':
      $where[] = '(
                   (wd.`id` IS NULL OR wd.`decision` = 5) AND
                   (
                     (wd.`id` IS NULL AND DATEDIFF('.$cd.', wa.`reg_date`) > 60) OR 
                     (wd.`decision` = 5 AND DATEDIFF('.$cd.', wd.`date`) > 60)
                   ) AND wa.`purpose_placing` <> 4
                  )';
      $clause[] = 'Место хранения - ОВД (более 60 суток)';
      break;
      
    case 'InStor':
      $where[] = '(wd.`decision` = 3)';
      $clause[] = 'Место хранения - Склад УМВД';
      break;
    
    case 'InStor1y':
      $where[] = '(wd.`decision` = 3 AND DATEDIFF('.$cd.', wd.`date`) > IF((YEAR('.$cd.')%4 = 0) AND ((YEAR('.$cd.')%100 != 0) OR (YEAR('.$cd.')%400 = 0)), 366, 365))';
      $clause[] = 'Место хранения - Склад УМВД (более 1 года)';
      break;
    
    case 'Util':
      $where[] = '(wd.`decision` = 4)';
      $clause[] = 'Утилизировано';
      break;
    
    case 'issued':
      $where[] = '(wd.`decision` IN (1,2))';
      $clause[] = 'Возвращено владельцу, Затребовано сотрудником';
      break;
    
    case 'lowsuit':
      $where[] = '(wdh.`decision` = 6)';
      $clause[] = 'Направлено исковое заявление в суд';
      break;
    
    case 'notice':
      $where[] = '(wdh.`decision` = 7)';
      $clause[] = 'Направлено извещение собственнику';
      break;
  }
}


$_t = null;

if (empty($clause))
  $clause[] = 'Общий список';

$query = '
  SELECT DISTINCT
    wa.`id` as `waccount`, wa.`reg_number`, DATE_FORMAT(wa.`reg_date`, "%d.%m.%Y") as `reg_date`,
    wt.`id` as `wtype_code`, wt.`name` as `wtype`, wg.`name` as `wgroup`,
    IF(
       ws.`name` = wsg.`name`,
       ws.`name`,
       CONCAT(IF(wsg.`name` IS NOT NULL, CONCAT(wsg.`name`, " - "), ""), ws.`name`)
    ) as `wsort`,
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
    ) as `wmodel`,
    m.`marking` as `add_attributes`,
    w.`caliber`, w.`manufacture_year` as `year`, w.`series`, w.`number`,
    w.`quantity_incoming`, w.`quantity_total`, wd.`id` as `decision_code`,
    IF(
       wd.`id` IS NOT NULL,
       CONCAT(
         swd.`name`, 
         " (",
            IF(wd.`number` IS NOT NULL, CONCAT("№", wd.`number`), ""),
            CONCAT(" от ", DATE_FORMAT(wd.`date`, "%d.%m.%Y")),
            IF(wd.`case` IS NOT NULL, CONCAT(", Дело №", wd.`case`, ", стр.", wd.`page`), ""),
          ")"),
       NULL
    ) as `decision`
  FROM
    `l_weapons` as w
  JOIN
    `l_weapons_account` as wa ON
      wa.`id` = w.`weapons_account`
      AND wa.`deleted` = 0
  LEFT JOIN
    `spr_weapon_types` as wt ON
      wt.`id` = w.`weapon_type`
  LEFT JOIN
    `spr_weapon_groups` as wg ON
      wg.`id` = w.`weapon_group`
  LEFT JOIN
    `spr_weapon_sorts` as ws ON
      ws.`id` = w.`weapon_sort`
    LEFT JOIN 
      `spr_weapon_sorts` as wsg ON 
        ws.`group` = wsg.`id`
  LEFT JOIN
    `spr_weapon_models` as wm ON
      wm.`id` = w.`model`
  LEFT JOIN
    `spr_marking` as m ON
      m.`id` = w.`add_attributes`
  LEFT JOIN
    `l_weapons_decision` as wd ON
      wd.`id` = w.`last_decision`
    LEFT JOIN
      `l_weapons_decision` as wdh ON
        wdh.`weapon` = w.`id`
  LEFT JOIN
    `spr_decision_in_arms` as swd ON
      swd.`id` = wd.`decision`
  WHERE
    w.`deleted` = 0 
    '.((!empty($where)) ? 'AND '.implode(' AND ', $where) : null).'
  ORDER BY
    wa.`reg_date` DESC, wa.`reg_number` DESC, w.`weapon_type`
';
require(KERNEL.'connect.php');

if (!$result = $db->query($query))
  die($db->error.' .Query string: '.$query);

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
$n = 1;
?>
<div class="header_row"><?= implode(', ', $clause) ?></div>
<div class="result_table">
  <div class="result_headers">
    <div class="result_cell order">№<br />п/п</div>
    <div class="result_cell shorttext">Описание</div>
    <div class="result_cell">Информация</div>
  </div>
<?php while ($row = $result->fetch_object()) : ?>
  <div class="result_row">
    <div class="result_cell"><?= $n++ ?>.</div>
    <div class="result_cell text">
      <ul>
        <li><?= $row->wtype ?></li>
        <li><?= $row->wsort ?><?php if (!empty($row->add_attributes)) echo ' ('.$row->add_attributes.')' ?></li>
        <li><?= $row->wgroup ?></li>
      </ul>
    </div>
    <div class="result_cell left-align">
      <ul>
        <li class="right-align"><b>Квитанция <a href="receipt.php?id=<?= $row->waccount ?>">№<?= $row->reg_number ?> от <?= $row->reg_date ?></a></b></li>
        <?php if (!empty($row->wmodel)) echo '<li>'.$row->wmodel.((!empty($row->series)) ? ', <b>сер.'.$row->series.' №'.$row->number.'</b>' : null).'</li>' ?>
        <?php if (!empty($row->caliber)) echo '<li>Калибр '.$row->caliber.' мм</li>' ?>
        <?php 
        if ($row->wtype_code == 2) {
          echo '<li>Принято '.$row->quantity_incoming.' шт.';
          if (!in_array($row->decision_code, array(1, 4)) and $row->quantity_total > 0 and $row->quantity_total != $row->quantity_incoming)
            echo ' (в КХО '.$row->quantity_total.')';
          echo '</li>';
        } ?>
        <?php if (!empty($row->decision)) echo '<li class="right-align"><i>'.$row->decision.'</i></li>' ?>
      </ul>
    </div>
  </div>
<?php endwhile; ?>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
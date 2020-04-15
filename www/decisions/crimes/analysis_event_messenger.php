<?php

$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(2));

$breadcrumbs = array(
  'Главная' => '/index.php',
  'АИС "Наркодилер"' => 'index.php',
  'Анализ...' => ''
);
$page_title = 'АИС "Наркодилер" &ndash; Анализ объекта "Мессенджер"';


require_once(KERNEL.'connect.php');

if (!empty($_GET["dateB"]) and !preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_GET["dateB"]))
  unset($_GET["dateB"]);
if (!empty($_GET["dateE"]) and !preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_GET["dateE"]))
  unset($_GET["dateE"]);

$where = array('e.`ais` = 2');

if (!empty($_GET["dateB"])) {
  $link[] = 'date_from='.$_GET['dateB'];
  if (empty($_GET['dateE'])) {
    $where[] = 'e.`decision_date` BETWEEN STR_TO_DATE("'.date('d.m.Y', strtotime($_GET["dateB"])).'", "%d.%m.%Y") AND 
                                          STR_TO_DATE("'.date('d.m.Y').'", "%d.%m.%Y")';
    $_GET['dateE'] = date('d.m.Y');
  } else {
    $where[] = 'e.`decision_date` BETWEEN STR_TO_DATE("'.$_GET["dateB"].'", "%d.%m.%Y") AND 
                                          STR_TO_DATE("'.$_GET["dateE"].'", "%d.%m.%Y")';
  }
  $link[] = 'date_to = '.$_GET['dateE'];
}

if (isset($_GET['type']) and is_numeric($_GET['type'])) 
  $where[] = 'm.`type` = '.$_GET['type'];

$query = '
  SELECT DISTINCT
    ovd.`ovd`, 
    CONCAT("<a href=\"event.php?event_id=", e.`id`, "\">", "У/д №", e.`decision_number`, " от ", DATE_FORMAT(e.`decision_date`, "%d.%m.%Y"), "</a>") as `case`, 
    CONCAT(sme.`messenger`, ": ", m.`account`, IF(m.`nick` IS NOT NULL AND m.`nick` <> m.`account`, CONCAT(" (", m.`nick`, ")"), "")) as `account`,
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
    CONCAT("д.", IF(a.`house_lit` IS NOT NULL, CONCAT(a.`house`,"/",a.`house_lit`), a.`house`)) as `house`
  FROM
    `o_event` as e
  JOIN
    `spr_ovd` as ovd ON
      ovd.`id_ovd` = e.`ovd_id`
  JOIN
    `l_messenger_obj_rel` as me ON
      me.`object` = e.`id`
      AND me.`object_type` = 2
    JOIN
      `l_messengers` as m ON
        m.`id` = me.`messenger`
      JOIN
        `spr_messenger` as sme ON
          sme.`id` = m.`type`
  LEFT JOIN
    `l_relatives` as rel ON
      rel.`from_obj` = e.`id` AND rel.`from_obj_type` = 2
      AND rel.`to_obj_type` = 3
      AND rel.`type` = 75
    LEFT JOIN
      `o_address` as a ON
        a.`id` = rel.`to_obj`
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
    '.implode(' AND ', $where).'
  ORDER BY
    e.`decision_date` DESC, e.`ovd_id`
';

$result = $db->query($query);

$_addr = array('district', 'city', 'locality', 'street', 'house');

$n = 0;
while ($row = $result->fetch_assoc()) {
  
  $_t = array();
  foreach ($row as $k => $v) {
    if (in_array($k, $_addr)) {
      if (!empty($v))
        $_t[] = $v;
      unset($row[$k]);
      continue;
    }
    $rows[$n][$k] = (!empty($v) ? $v : null);
  }
  $rows[$n]['address'] = implode(', ', $_t);
  $n++;
}
$result->close();



require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row">Преступления с использованием мессенджера</div>

<div class="input_row">
  <form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>" id="date_period_form">
    <div class="field_box noSidePadding">
      <span class="field_name">Период возбуждения: </span>
      <?= my_date_field('dateB', ((!empty($_GET['dateB'])) ? $_GET['dateB'] : null), null, 'onchange="document.getElementById(\'date_period_form\').submit();"') ?>
    </div>
    <div class="field_box noSidePadding">
      <span class="field_name"> &mdash; </span>
      <?= my_date_field('dateE', ((!empty($_GET['dateE'])) ? $_GET['dateE'] : null), null, 'onchange="document.getElementById(\'date_period_form\').submit();"') ?>
    </div>
    <div class="field_box">
      <span class="field_name">Тип: </span>
      <?= my_select('type', 'spr_messenger', ((!empty($_GET['type'])) ? $_GET['type'] : null), 'onchange="document.getElementById(\'date_period_form\').submit();"') ?>
    </div>
  </form>
</div>

<table class="result_table" rules="all" border="1" width="100%" cellpadding="3" id="myTable">
  <tr class="table_head">
    <th>№<br />п/п</th>
    <th>У/д</th>
    <th>Мессенджер</th>
    <th>Место закладки</th>
  </tr>
  <?php foreach ($rows as $n => $cols) : ?>
    <tr>
      <td align="center"><?= ++$n ?>.</td>
      <td align="center"><?= $cols['ovd'] ?><br /><?= $cols['case'] ?></td>
      <td><?= $cols['account'] ?></td>
      <td><?= $cols['address'] ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
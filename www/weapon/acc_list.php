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
  'Квитанции' => ''
);
$page_title = 'Учет "Оружие" &ndash; Список квитанций';

if ($_SESSION['user']['ovd_id'] != 59) {
  $_GET['ovd'] = $_SESSION['user']['ovd_id'];
}

if (isset($_GET['ovd']) and is_numeric($_GET['ovd'])) {
  $_t = floor(abs($_GET['ovd']));
  $where[] = 'AND wa.`ovd` = '.$_t;
  $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} else {
  $clause[] = 'Область';
}

if (isset($_GET['year']) and is_numeric($_GET['year'])) {
  $_t = floor(abs($_GET['year']));
  $where[] = 'AND YEAR(wa.`reg_date`) = '.$_t;
  $clause[] = $_t.' год';
}

if (isset($_GET['pp']) and is_numeric($_GET['pp'])) {
  $_t = floor(abs($_GET['pp']));
  $where[] = 'AND wa.`purpose_placing` = '.$_t;
  $clause[] = 'Основание принятия &ndash; '.get_meaning_from_spr('spr_purpose_placing', $_t);
}

$_t = null;

if (empty($where))
  $where = array();
if (empty($clause))
  $clause[] = 'Общий список';

$query = '
  SELECT
    wa.`id`,
    DATE_FORMAT(wa.`reg_date`, "%d.%m.%Y") as `reg_date`,
    wa.`reg_number`, spp.`name` as `purpose_placing`, sbr.`name` as `base_receiving`,
    CONCAT(
      IF(k.`ek` IS NOT NULL, CONCAT("<a href=\"/wonc/ek.php?id=", k.`ek`, "\" target=\"_blank\" title=\"Электронный КУСП\">"), ""), 
        "КУСП №", k.`kusp`, " от ", DATE_FORMAT(k.`date`, "%d.%m.%Y"), " (", kovd.`ovd`, ")",
      IF(k.`ek` IS NOT NULL, "</a>", "")
    ) as `kusp`,
    CONCAT("№", cc.`crime_case_number`, " от ", DATE_FORMAT(cc.`crim_case_date`, "%d.%m.%Y"), " (", ccovd.`ovd`, ")") as `crime_case`,
    CONCAT("Вх.ДИР №", wa.`incoming_number`, " от ", DATE_FORMAT(wa.`incoming_date`, "%d.%m.%Y")) as `odir`
  FROM
    `l_weapons_account` as wa
  JOIN
    `spr_purpose_placing` as spp ON
      spp.`id` = wa.`purpose_placing`
  JOIN
    `spr_base_receiving_weapons` as sbr ON
      sbr.`id` = wa.`base_receiving`
  LEFT JOIN
    `l_kusp` as k ON
      k.`id` = wa.`kusp`
    LEFT JOIN
      `spr_ovd` as kovd ON
        kovd.`id_ovd` = k.`ovd`
  LEFT JOIN
    `l_crime_cases` as cc ON
      cc.`id` = wa.`crime_case`
    LEFT JOIN
      `spr_ovd` as ccovd ON
        ccovd.`id_ovd` = k.`ovd`
  WHERE
    wa.`deleted` = 0
    '.implode(' ', $where).'
  GROUP BY
    wa.`id`
  ORDER BY
    wa.`reg_date` DESC, wa.`reg_number` DESC
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
    <div class="result_cell shorttext">Квитанция</div>
    <div class="result_cell">Информация</div>
  </div>
<?php while ($row = $result->fetch_object()) : ?>
  <div class="result_row">
    <div class="result_cell order">
      <?= $n++ ?>.
    </div>
    <div class="result_cell text">
      <a href="receipt.php?id=<?= $row->id ?>">№<?= $row->reg_number ?> от <?= $row->reg_date ?></a>
    </div>
    <div class="result_cell">
      <ul class="left-align">
        <li><?= $row->kusp ?></li>
        <li><?php if (!empty($row->crime_case)) echo 'У/д '.$row->crime_case ?></li>
        <li>Основание принятия: <i><?= $row->purpose_placing ?></i></li>
        <li>Цель помещения: <i><?= $row->base_receiving ?></i></li>
        <li><?php if (!empty($row->odir)) echo $row->odir ?></li>
      </ul>
    </div>
  </div>
<?php endwhile; ?>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
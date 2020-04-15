<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Электронный КУСП' => 'e-kusp.php',
  '' => ''
);


$months = array(
  1 => 'Январь',
  2 => 'Февраль',
  3 => 'Март',
  4 => 'Апрель',
  5 => 'Май',
  6 => 'Июнь',
  7 => 'Июль',
  8 => 'Август',
  9 => 'Сентябрь',
  10 => 'Октябрь',
  11 => 'Ноябрь',
  12 => 'Декабрь'
);

$limits = null;
if (!empty($_GET["year"]) and is_numeric($_GET['year']) and (integer)$_GET['year'] >= 2012) {
  $year = (integer)$_GET["year"];
  
  if (!empty($_GET['month']) and is_numeric($_GET['month']) and (integer)$_GET['month'] >= 1 and (integer)$_GET['month'] <= 12) {
    $month = (integer)$_GET['month'];
    $where[] = 'ek.`reg_date` BETWEEN STR_TO_DATE("01.'.$month.'.'.$year.'", "%d.%m.%Y") AND STR_TO_DATE("31.'.$month.'.'.$year.'", "%d.%m.%Y")';
    $limits[] = $months[$month];
  } else {
    $where[] = 'ek.`reg_date` BETWEEN STR_TO_DATE("01.01.'.$year.'", "%d.%m.%Y") AND STR_TO_DATE("31.12.'.$year.'", "%d.%m.%Y")';
  }
  
  $limits[] = $year.' г.';
}

if (isset($_GET['ovd']) and is_numeric($_GET['ovd'])) {
  $ovd = getOvdName($_GET['ovd']);
  $limits[] = $ovd['ovd'];
  $page_title = $ovd['ovd_full'];
  $where[] = 'ek.`ovd` = '.$ovd['id'];
}

require(KERNEL.'connect.php');
$query = '
  SELECT DISTINCT
    ek.`id`, ovd.`ovd`,
    CONCAT(ek.`reg_number`, ", ", 
           DATE_FORMAT(ek.`reg_date`, "%d.%m.%Y"), " ", 
           DATE_FORMAT(ek.`reg_time`, "%H.%i")
           ) as `reg`,
    ek.`story`, ek.`marking`,
    CONCAT(IFNULL(ek.`declarer_person`, ""), IF(ek.`declarer_org` IS NOT NULL, CONCAT(", ", ek.`declarer_org`), "")) as `declarer`,
    IF(ek.`event_code` IS NOT NULL, "ПК \"Легенда\"", "СОДЧ") as `source`,
    GROUP_CONCAT(
      DISTINCT
        IF(kh.`dec_date` IS NOT NULL AND kh.`dec_date` <> "0000-00-00", DATE_FORMAT(kh.`dec_date`, "%d.%m.%Y"), ""), 
        IF(kh.`decision` IS NOT NULL, CONCAT(" ", kh.`decision`), ""),
        IF(kh.`term` IS NOT NULL AND kh.`term` <> "0000-00-00" AND kh.`dec_code` = 23, CONCAT(" до ", DATE_FORMAT(kh.`term`, "%d.%m.%Y")), ""),
        IF(kh.`dec_number` IS NOT NULL AND kh.`dec_number` <> "", CONCAT(", №", kh.`dec_number`), ""),
        IF(kh.`qualification` IS NOT NULL AND kh.`qualification` <> "", CONCAT(", ", kh.`qualification`), "")
      ORDER BY kh.`dec_date` DESC
      SEPARATOR "<br />"
    ) as `decisions`
  FROM
    `ek_kusp` as ek
  JOIN
    `spr_ovd` as ovd ON
      ovd.`id_ovd` = ek.`ovd`
  LEFT JOIN
    `ek_dec_history` as kh ON
      kh.`kusp` = ek.`id` AND
      kh.`error_rec` = 0
  WHERE
    '.implode(' AND ', $where).'
  GROUP BY
    ek.`id`
  ORDER BY
    ek.`reg_date` DESC, ek.`reg_number` DESC, ek.`ovd`
  LIMIT
    15
';

$par_str = null;
foreach ($_GET as $k => $v) {
  $par_str[] = $k.'="'.$v.'"';
}

$result = $db->query($query);


require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row"><?= 'КУСП за '.implode(', ', $limits) ?></div>

<div class="add-list">
  <div class="result_headers">
    <div class="result_cell shorttext">Рег.данные</div>
    <div class="result_cell gianttext">Фабула</div>
    <div class="result_cell longtext">Решения</div>
    <div class="result_cell shorttext">Источник</div>
  </div>
  <?php while ($row = $result->fetch_object()) : ?>
      <div class="result_row">
        <div class="result_cell shorttext">
          <a href="ek.php?id=<?= $row->id ?>"><?= (empty($ovd)) ? $row->ovd.'<br />' : null ?><?= $row->reg ?></a>
        </div>
        <div class="result_cell gianttext">
          <?= ((!empty($row->marking)) ? '<i><b>Окраска</b> &ndash; '.$row->marking.'</i><br />' : null) ?>
          <?= ((!empty($row->declarer)) ? '<i><b>Заявитель</b> &ndash; '.$row->declarer.'</i><br />' : null) ?>
          <?= $row->story ?>
        </div>
        <div class="result_cell longtext left-align"><?= $row->decisions ?></div>
        <div class="result_cell shorttext"><?= $row->source ?></div>
      </div>
  <?php endwhile; ?>
  <div class="responce_place">
    <div id="add-ready" method="ek-list" r="15" t="15" <?= implode(' ', $par_str) ?>></div>
  </div>
</div>

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
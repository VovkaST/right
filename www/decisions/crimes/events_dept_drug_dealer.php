<?php

if (pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) != 'events_dept.php') {
  header('Location: /error/404.php');
  die();
}

$breadcrumbs = array(
  'Главная' => '/index.php',
  'АИС "Наркодилер"' => 'index.php',
  'Невведенные уг.дела' => ''
);
$page_title = 'АИС "Наркодилер" &ndash; Невведенные уг.дела';

if (!isset($_GET["ovd_id"]) || !is_numeric($_GET["ovd_id"])) {
  define("ERROR", 'Недостаточно параметров!');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$ovd = floor(abs($_GET["ovd_id"]));
$ovd = getOvdName($ovd);

require_once(KERNEL.'connect.php');
$query = '
  SELECT
    CONCAT("<a href=\"/decisions/indict/case.php?id=", nc.`f1` ,"\">", nc.`number`, " от ", DATE_FORMAT(nc.`date`, "%d.%m.%Y"), "</a>") as `case`,
    uk.`st`, nc.`employeer`, nc.`story`
  FROM
    `l_nark_crimes` as nc
  JOIN
    `spr_uk` as uk ON
      uk.`id_uk` = nc.`article`
  LEFT JOIN    
    `o_event` as e ON 
      e.`decision_number` = nc.`number` AND
      e.`ais` = 2
  WHERE
    nc.`year` >= 2017 AND
    e.`id` IS NULL AND
    nc.`ovd` = '.$ovd['id'];

$result = $db->query($query);
if (!empty($db->error)) {
  if (!empty($_SRESSION['user']['admin'])) {
    echo 'Ошибка выборки: '.$db->error.'. Query: <pre>'.$query.'</pre>';
  } else {
    echo 'Ошибка выборки. Обратитесь к администратору!';
  }
}

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row"><?= $ovd['ovd'] ?></div>
<?php if ($result->num_rows > 0) : ?>
  <?php $n = 1; ?>
  <div class="result_headers">
    <div class="result_cell number">№<br />п/п</div>
    <div class="result_cell text">У/д</div>
    <div class="result_cell shorttext">Ст.<br />УК РФ</div>
    <div class="result_cell shorttext">Сотрудник</div>
    <div class="result_cell gianttext">Фабула</div>
  </div>
  <?php while ($row = $result->fetch_object()) : ?>
    <div class="result_row">
      <div class="result_cell number"><?= $n++ ?>.</div>
      <div class="result_cell text"><?= $row->case ?></div>
      <div class="result_cell shorttext"><?= $row->st ?></div>
      <div class="result_cell shorttext"><?= $row->employeer ?></div>
      <div class="result_cell gianttext align-left"><?= $row->story ?></div>
    </div>
  <?php endwhile; ?>
  <div class="result_row">
    <a href="event.php?ovd_id=<?= $ovd['id'] ?>">Добавить...</a>
  </div>
<?php else: ?>
  Ничего не найдено!
<?php endif; ?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
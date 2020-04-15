<?php

if (pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) != 'analysis_event_marking.php') {
  header('Location: /error/404.php');
  die();
}

$breadcrumbs = array(
  'Главная' => '/index.php',
  'АИС "Наркодилер"' => 'index.php',
  'Анализ...' => ''
);
$page_title = 'АИС "Наркодилер" &ndash; Невведенные уг.дела';


require_once(KERNEL.'connect.php');
$query = '
  SELECT
    DISTINCT YEAR(e.`decision_date`) as `year`
  FROM
    `o_event` as e
  WHERE
    e.`decision_date` AND
    e.`ais` = 2 AND
    e.`decision` = 1
';
$result = $db->query($query);
while ($row = $result->fetch_object()) {
  $yearList[] = $row->year;
}
$result->close();

$q_years = mysql_query('
  SELECT
    DISTINCT YEAR(e.`decision_date`) as `year`
  FROM
    `o_event` as e
  WHERE
    e.`decision_date` AND
    e.`ais` = 1 AND
    e.`decision` = 1
');
while($r_years = mysql_fetch_assoc($q_years)) {
  $yearList[] = $r_years['year'];
}

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
  $link[] = 'date_to='.$_GET['dateE'];
} else {
  $where[] = 'e.`decision_date` BETWEEN STR_TO_DATE("01.01.'.date('Y').'", "%d.%m.%Y") AND 
                                          STR_TO_DATE("31.12.'.date('Y').'", "%d.%m.%Y")';
}

if (isset($_GET['wo_faces'])) 
  $where[] = 'rel.`id` IS NULL';

$rows = $fields = array();
$total = array('Итого');


$query = '
  SELECT
    ovd.`id_ovd` as `id`, ovd.`ovd`
  FROM
    `spr_ovd` as ovd
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN(5,6,7,10,60,61,62,63,64,65,66)
';
$result = $db->query($query);
while ($row = $result->fetch_object()) {
  $rows[$row->id]['ovd'] = $row->ovd;
}
$result->close();

$query = '
  SELECT
    e.`ovd_id`,
    COUNT(DISTINCT e.`id`) as `total`,
    COUNT(DISTINCT IF(sm.`id` = 12, e.`id`, NULL)) as `zakladka`,
    COUNT(DISTINCT IF(sm.`id` = 13, e.`id`, NULL)) as `nahodka`,
    COUNT(DISTINCT IF(sm.`id` = 14, e.`id`, NULL)) as `iz_ruk`,
    
    COUNT(DISTINCT IF(sm.`id` = 31, e.`id`, NULL)) as `sigaret`,
    COUNT(DISTINCT IF(sm.`id` = 32, e.`id`, NULL)) as `banka`,
    COUNT(DISTINCT IF(sm.`id` = 33, e.`id`, NULL)) as `korobok`,
    COUNT(DISTINCT IF(sm.`id` = 34, e.`id`, NULL)) as `pochta`,
    
    COUNT(DISTINCT IF(sm.`id` = 36, e.`id`, NULL)) as `zip`,
    COUNT(DISTINCT IF(sm.`id` = 37, e.`id`, NULL)) as `bumaga`,
    COUNT(DISTINCT IF(sm.`id` = 38, e.`id`, NULL)) as `paket`
  FROM
    `o_event` as e
  LEFT JOIN
    `l_object_marking` as om ON
      om.`object` = e.`id` AND
      om.`obj_type` = 2
    LEFT JOIN
      `spr_marking` as sm ON
        sm.`id` = om.`marking`
  LEFT JOIN
    `l_relatives` as rel ON
      rel.`from_obj_type` = 1 AND
      rel.`to_obj` = e.`id` AND
      rel.`to_obj_type` = 2
  WHERE
    '.implode(' AND ', $where).'
  GROUP BY
    e.`ovd_id`
';

$result = $db->query($query);

while ($row = $result->fetch_assoc()) {
  foreach ($row as $k => $v) {
    if ($k == 'ovd_id') {
      $_t = $v;
      continue;
    }
    if (count($fields) < ($result->field_count - 1))
      $fields[] = $k;
    $rows[$_t][$k] = (integer)$v;
  }
}
$result->close();

foreach ($rows as $row => $cells) {
  if (empty($cols)) 
    $cols = count($cells);
  if (count($cells) == 1) {
    foreach ($fields as $n => $f) {
      $rows[$row][$f] = 0;
    }
  }
}


require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row">Преступления по окраске</div>

<div class="input_row">
  <form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>" id="date_period_form">
    <div class="field_box noSidePadding">
      <span class="field_name">Период: </span>
      <?= my_date_field('dateB', ((!empty($_GET['dateB'])) ? $_GET['dateB'] : null), null, 'onchange="document.getElementById(\'date_period_form\').submit();"') ?>
    </div>
    <div class="field_box noSidePadding">
      <span class="field_name"> &mdash; </span>
      <?= my_date_field('dateE', ((!empty($_GET['dateE'])) ? $_GET['dateE'] : null), null, 'onchange="document.getElementById(\'date_period_form\').submit();"') ?>
    </div>
    <div class="field_box">
      <label class="field_name">
        <input type="checkbox" name="wo_faces" value="" onchange="document.getElementById('date_period_form').submit();" <?php if (isset($_GET['wo_faces'])) echo 'checked' ?>/>У/д без лиц
      </label>
    </div>
  </form>
</div>

<table class="result_table" rules="all" border="1" width="100%" cellpadding="3" id="myTable">
  <tr class="table_head">
    <th rowspan="3" width="200px">ОВД</th>
    <th rowspan="3">Всего<br />введено</th>
    <th colspan="10">из них</th>
  </tr>
  <tr class="table_head">
    <th colspan="3">по способу совершения</th>
    <th colspan="4">по способу закладки</th>
    <th colspan="3">по виду упаковки</th>
  </tr>
  <tr class="table_head">
    <th>Закладка</th>
    <th>Находка</th>
    <th>Из рук<br />в руки</th>
    <th>Сигар.<br />пачка/<br />оболоч.</th>
    <th>Жестян.<br />банка</th>
    <th>Спич.<br />короб.</th>
    <th>Почт.<br />отпр-е</th>
    <th>Zip-lock</th>
    <th>Бумага</th>
    <th>Полимер.<br />пакет</th>
  </tr>
  <?php foreach ($rows as $ovd => $cols) : ?>
    <tr>
    <?php foreach ($cols as $f => $v) : ?>
      <?php if ($f == 'ovd') : ?>
        <td><?= $v ?></td>
        
      <?php else : ?>
      
        <?php if ($v) : ?>
        
          <?php if ($f == 'total') : ?>
            <td align="center"><a href="events_list.php?ovd_id=<?= $ovd.((!empty($link) ? '&'.implode('&', $link) : null)) ?>"><?= $v ?></a></td>
          <?php else : ?>
            <td align="center"><a href="events_list.php?ovd_id=<?= $ovd ?>&<?= $f.((!empty($link) ? '&'.implode('&', $link) : null)) ?>"><?= $v ?></a></td>
          <?php endif; ?>
            
        <?php else : ?>
          <td align="center">0</td>
        <?php endif; ?>
        
        <?php (array_key_exists($f, $total)) ? $total[$f] += $v : $total[$f] = $v ?>
        
      <?php endif; ?>
      
    <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
  <tr class="table_total_row">
    <?php foreach ($total as $k => $v) : ?>
      <th><?= $v ?></th>
    <?php endforeach; ?>
  </tr>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
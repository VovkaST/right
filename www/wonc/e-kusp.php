<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => 'index.php',
  'Электронный КУСП' => ''
);
$page_title = 'Электронный КУСП';

$lastSwap = lastSwap('legenda');
$yearList = KUSPYearsList();
if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

require(KERNEL.'connect.php');
$query = '
  SELECT SQL_NO_CACHE
    ek.`ovd`,
    COUNT(DISTINCT ek.`id`) as `total`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 1, ek.`id`, NULL)) as `jan`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 2, ek.`id`, NULL)) as `feb`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 3, ek.`id`, NULL)) as `mar`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 4, ek.`id`, NULL)) as `apr`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 5, ek.`id`, NULL)) as `may`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 6, ek.`id`, NULL)) as `jun`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 7, ek.`id`, NULL)) as `jul`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 8, ek.`id`, NULL)) as `aug`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 9, ek.`id`, NULL)) as `sep`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 10, ek.`id`, NULL)) as `oct`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 11, ek.`id`, NULL)) as `nov`,
    COUNT(DISTINCT IF(MONTH(ek.`reg_date`) = 12, ek.`id`, NULL)) as `dec`
  FROM
    `ek_kusp` as ek
  WHERE
    ek.`reg_date` BETWEEN STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-12-31", "%Y-%m-%d")
  GROUP BY
    ek.`ovd`
';
$result = $db->query($query);

$stat = array();
$total = array();
while ($row = $result->fetch_object()) {
  foreach ($row as $f => $v) {
    if ($f == 'ovd') continue;
    $stat[$row->ovd][$f] = $v;
  }
}
$result->close();

$query = '
  SELECT
    ovd.`id_ovd` as `id`, ovd.`ovd`
  FROM
    `spr_ovd` as ovd
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN (9,10,60,61,63,65,66)
';
$result = $db->query($query);

require_once ($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row">Электронный КУСП</div>

<div class="decision_top_block actions_list">
  <div class="left_box">
    <div id="yearList">
      <?php foreach ($yearList as $value) :?>
        <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?year='.$value ?>"><?= $value ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if (!empty($_SESSION['user']['user'])) : ?>
    <div class="search_box">
      <a href="search_ek.php">Поиск</a>
    </div>
  <?php endif; ?>
</div>
<div class="debt_date">
  Сведения на <?= date('G:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
</div>
<table rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable">
  <tr class="table_head">
    <th rowspan="2">ОВД</th>
    <th colspan="13">В <?= $year ?> г. зарегистрировано</th>
  </tr>
  <tr class="table_head">
    <th width="70px">Всего</th>
    <th width="50px">янв</th>
    <th width="50px">фев</th>
    <th width="50px">мар</th>
    <th width="50px">апр</th>
    <th width="50px">май</th>
    <th width="50px">июн</th>
    <th width="50px">июл</th>
    <th width="50px">авг</th>
    <th width="50px">сен</th>
    <th width="50px">окт</th>
    <th width="50px">ноя</th>
    <th width="50px">дек</th>
  </tr>
<?php while ($row = $result->fetch_object()) : 
  $m = 1;
  ?>
  <tr>
    <td><?= $row->ovd ?></td>
    <?php if (array_key_exists($row->id, $stat)) : ?>
      <?php foreach ($stat[$row->id] as $par => $data) : ?>
        <td align="center"><?= ($data) ? '<a href="e-kusp_list.php?ovd='.$row->id.'&year='.$year.''.((!preg_match('/(ovd|total)/ui', $par)) ? '&month='.$m : null).'">'.$data.'</a>' : 0 ?></td>
        <?php if (!preg_match('/(ovd|total)/ui', $par)) $m++; ?>
      <?php (array_key_exists($par, $total)) ? $total[$par] += $data : $total[$par] = $data; ?>
      <?php endforeach; ?>
    <?php else : ?>
      <?php for ($i = 1; $i <= 13; $i++) : ?>
        <td align="center">0</td>
      <?php endfor; ?>
    <?php endif; ?>
  </tr>
<?php endwhile; ?>
  <tr class="table_total_row">
    <th>Итого</th>
    <?php foreach ($total as $k => $data) : ?>
      <th align="center"><?= $data ?></th>
    <?php endforeach; ?>
  </tr>
</table>
<?php $result->close(); ?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
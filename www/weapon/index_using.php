<?php
if (pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) != 'index.php' or empty($_SESSION['user']['admin']))
  header('Location: /error/404.php');

$months = array(
  1 => 'Январь',
  2 => 'Феврль',
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

$d = new DateTime();

for ($i = 1; $i <= 3; $i++) {
  if ($i == 1)
    $d->sub(new DateInterval('P1M'.(date('d')-1).'D'));
  else
    $d->sub(new DateInterval('P1M'));
    
  $dates[$i][] = '01.'.$d->format('m.Y');
  $dates[$i][] = '31.'.$d->format('m.Y');
}

for ($n = 0; $n <= 8; $n++) {
  $total[$n] = null;
}

$query = '
  SELECT
    wa.`ovd` as `id`, ovd.`ovd`, COUNT(DISTINCT wa.`id`) as `total`,
    COUNT(DISTINCT IF(wa.`create_date` BETWEEN STR_TO_DATE("'.$dates[3][0].'", "%d.%m.%Y") AND STR_TO_DATE("'.$dates[3][1].'", "%d.%m.%Y"), wa.`id`, NULL)) as `three_cr`,
    COUNT(DISTINCT IF(wd.`create_date` BETWEEN STR_TO_DATE("'.$dates[3][0].'", "%d.%m.%Y") AND STR_TO_DATE("'.$dates[3][1].'", "%d.%m.%Y"), w.`id`, NULL)) as `three_wpn_h`,
    COUNT(DISTINCT IF(wa.`create_date` BETWEEN STR_TO_DATE("'.$dates[2][0].'", "%d.%m.%Y") AND STR_TO_DATE("'.$dates[2][1].'", "%d.%m.%Y"), wa.`id`, NULL)) as `two_cr`,
    COUNT(DISTINCT IF(wd.`create_date` BETWEEN STR_TO_DATE("'.$dates[2][0].'", "%d.%m.%Y") AND STR_TO_DATE("'.$dates[2][1].'", "%d.%m.%Y"), w.`id`, NULL)) as `two_wpn_h`,
    COUNT(DISTINCT IF(wa.`create_date` BETWEEN STR_TO_DATE("'.$dates[1][0].'", "%d.%m.%Y") AND STR_TO_DATE("'.$dates[1][1].'", "%d.%m.%Y"), wa.`id`, NULL)) as `one_cr`,
    COUNT(DISTINCT IF(wd.`create_date` BETWEEN STR_TO_DATE("'.$dates[1][0].'", "%d.%m.%Y") AND STR_TO_DATE("'.$dates[1][1].'", "%d.%m.%Y"), w.`id`, NULL)) as `one_wpn_h`,
    DATE_FORMAT(MAX(w.`create_date`), "%d.%m.%Y") as `acc_create_max`,
    DATE_FORMAT(MAX(wd.`create_date`), "%d.%m.%Y") as `wpn_dec_create_max`
  FROM
    `l_weapons_account` as wa FORCE INDEX (`deleted`)
  JOIN
    `spr_ovd` as ovd ON
      ovd.`id_ovd` = wa.`ovd`
  LEFT JOIN
    `l_weapons` as w ON
      w.`weapons_account` = wa.`id`
    LEFT JOIN
    `l_weapons_decision` as wd ON
      wd.`id` = w.`last_decision` AND
      wd.`deleted` = 0
  WHERE
    wa.`deleted` = 0
  GROUP BY
    wa.`ovd`
';
require(KERNEL.'connect.php');

if (!$result = $db->query($query))
  die($db->error.' .Query string: '.$query);

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<div class="header_row">Использование сервиса</div>
<div class="actions_block">
  <ul class="actions_list">
    <li class="item">Просмотр:</li>
    <li class="item"><div class="block"><a href="index.php?volume=short">Кратко</a></div></li>
    <li class="item"><div class="block"><a href="index.php?volume=full">Расширенно</a></div></li>
    <li class="item"><div class="block"><a href="index.php?volume=kusp">КУСП/УД</a></div></li>
    <li class="item"><div class="block current">Использование сервиса</div></li>
  </ul>
</div>
<div class="actions_list">
 
</div>
<table rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable">
  <tr class="table_head">
    <th rowspan="2" width="180px">ОВД</th>
    <th rowspan="2" width="65px">Всего<br />квит.</th>
    <th colspan="2"><?= $months[(int)date('m', strtotime($dates[3][0]))] ?></th>
    <th colspan="2"><?= $months[(int)date('m', strtotime($dates[2][0]))] ?></th>
    <th colspan="2"><?= $months[(int)date('m', strtotime($dates[1][0]))] ?></th>
    <th rowspan="2">Послед.<br />квит-я</th>
    <th rowspan="2">Послед.<br />движение</th>
  </tr>
  <tr class="table_head">
    <th width="65px">Квит-ий</th>
    <th width="65px">Движ.</th>
    <th width="65px">Квит-ий</th>
    <th width="65px">Движ.</th>
    <th width="65px">Квит-ий</th>
    <th width="65px">Движ.</th>
  </tr>
  <?php while ($row = $result->fetch_object()) : ?>
    <tr>
      <td><?= $row->ovd ?></td>
      <td align="center">
        <?= (empty($row->total)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'">'.$row->total.'</a>' ?>
        <?php $total[0] += $row->total ?>
      </td>
      <td align="center">
        <?= (empty($row->three_cr)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'">'.$row->three_cr.'</a>' ?>
        <?php $total[1] += $row->three_cr ?>
      </td>
      
      <td align="center">
        <?= (empty($row->three_wpn_h)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=fa">'.$row->three_wpn_h.'</a>' ?>
        <?php $total[2] += $row->three_wpn_h ?>
      </td>
      <td align="center">
        <?= (empty($row->two_cr)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=amm">'.$row->two_cr.'</a>' ?>
        <?php $total[3] += $row->two_cr ?>
      </td>
      <td align="center">
        <?= (empty($row->two_wpn_h)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=exp">'.$row->two_wpn_h.'</a>' ?>
        <?php $total[4] += $row->two_wpn_h ?>
      </td>
      <td align="center">
        <?= (empty($row->one_cr)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=sa">'.$row->one_cr.'</a>' ?>
        <?php $total[5] += $row->one_cr ?>
      </td>
      
      <td align="center">
        <?= (empty($row->one_wpn_h)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD">'.$row->one_wpn_h.'</a>' ?>
        <?php $total[6] += $row->one_wpn_h ?>
      <td align="center">
        <?= (empty($row->acc_create_max)) ? null : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD60">'.$row->acc_create_max.'</a>' ?>
        <?php $total[7] += null ?>
      </td>
      <td align="center">
        <?= (empty($row->wpn_dec_create_max)) ? null : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor">'.$row->wpn_dec_create_max.'</a>' ?>
        <?php $total[8] += null ?>
      </td>
    </tr>
  <?php endwhile; ?>
  <tr class="table_total_row">
    <th>Итого</th>
    <?php foreach ($total as $n => $qnt) : ?>
      <th><?= $qnt ?></th>
    <?php endforeach; ?>
  </tr>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
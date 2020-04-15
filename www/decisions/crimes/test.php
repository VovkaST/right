<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once(KERNEL.'connection.php');

$year = 2017;

$comb = null;
$query = '
  SELECT
    ovd.`id_ovd`, ovd.`ovd`
  FROM
    `spr_ovd` as ovd
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN(5,6,7,10,60,61,62,63,64,65,66)
';
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
  $comb[$row['id_ovd']]['ovd'] = $row['ovd'];
}

$query = '
  SELECT
    nc.`ovd`, COUNT(DISTINCT nc.`id`) as `reg`
  FROM
    `l_nark_crimes` as nc
  WHERE
    nc.`year` = '.$year.'
  GROUP BY
    nc.`ovd`
';
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
  $comb[$row['ovd']]['reg'] = $row['reg'];
}

$query = '
  SELECT
    e.`ovd_id` as `ovd`,
    COUNT(DISTINCT e.`id`) as `cnt_event`
  FROM
    `o_event` as e
  WHERE
    e.`ais` = 2 AND
    YEAR(e.`decision_date`) = '.$year.'
  GROUP BY
    e.`ovd_id`
';
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
  $comb[$row['ovd']]['cnt_event'] = $row['cnt_event'];
}

$query = '
  SELECT
    nc.`ovd`, COUNT(DISTINCT nc.`id`) as `debt`
  FROM
    `l_nark_crimes` as nc
  LEFT JOIN    
    `o_event` as e ON 
      e.`decision_number` = nc.`number` AND
      e.`ais` = 2 AND
      e.`decision_date` BETWEEN STR_TO_DATE("01.01.'.$year.'", "%d.%m.%Y") AND 
                                STR_TO_DATE("31.12.'.$year.'", "%d.%m.%Y")
  WHERE
    nc.`year` = '.$year.' AND
    e.`id` IS NULL
  GROUP BY
    nc.`ovd`
';
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
  $comb[$row['ovd']]['debt'] = $row['debt'];
}

$total = null;
?>

<table rules="all" border="1" cellpadding="3" rules="all" class="total_indicators result_table">
  <tr class="table_head">
    <th width="170px">ОВД</th>
    <th width="50px">Всего<br />зарег.</th>
    <th width="65px">Введено</th>
    <th width="65px">Не<br />введено</th>
  </tr>
  <?php foreach ($comb as $ovd => $row) : ?>
    <tr>
      <td><?= $row['ovd'] ?></td>

      <td align="center">
      <?php if (empty($row['reg'])) : ?>
        <a href="<?= 'event.php?ovd_id='.$ovd ?>">0</a>
      <?php else : ?>
        <a href="<?= 'events_list.php?ovd_id='.$ovd ?>&year=<?= $year ?>"><?= $row['reg'] ?></a>
        <?php (isset($total['reg'])) ? $total['reg'] += $row['reg'] : $total['reg'] = $row['reg'] ?>
      <?php endif; ?>
      </td>

      <td align="center">
      <?php if (empty($row['cnt_event'])) : ?>
        <a href="<?= 'event.php?ovd_id='.$ovd ?>">0</a>
      <?php else : ?>
        <a href="<?= 'events_list.php?ovd_id='.$ovd ?>&year=<?= $year ?>"><?= $row['cnt_event'] ?></a>
        <?php (isset($total['cnt_event'])) ? $total['cnt_event'] += $row['cnt_event'] : $total['cnt_event'] = $row['cnt_event'] ?>
      <?php endif; ?>
      </td>

      <td align="center">
      <?php if (empty($row['debt'])) : ?>
        <a href="<?= 'event.php?ovd_id='.$ovd ?>">0</a>
      <?php else : ?>
        <a href="<?= 'events_list.php?ovd_id='.$ovd ?>&year=<?= $year ?>&type=1"><?= $row['debt'] ?></a>
        <?php (isset($total['debt'])) ? $total['debt'] += $row['debt'] : $total['debt'] = $row['debt'] ?>
      <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <tr class="table_total_row">
    <th>Итого</th>
    <?php foreach ($total as $c => $v) : ?>
      <th align="center"><?= $v ?></th>
    <?php endforeach; ?>
  </tr>
</table>
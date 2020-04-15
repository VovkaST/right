<?php
require_once(KERNEL.'connection.php');

$yearList = null;
$q_years = mysql_query('
  SELECT
    DISTINCT YEAR(e.`decision_date`) as `year`
  FROM
    `o_event` as e
  WHERE
    e.`decision_date` AND
    e.`ais` = 2 AND
    e.`decision` = 2
  
  UNION

  SELECT
    '.date('Y').'
    
  ORDER BY `year` ASC
');
while($r_years = mysql_fetch_assoc($q_years)) {
  $yearList[] = $r_years['year'];
}


if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

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

$q_objects = mysql_query('
  SELECT
    o.`id`, o.`object`,
    CASE
      WHEN o.`id` <> 2 THEN COUNT(DISTINCT objects.`obj_id1`)
      WHEN o.`id` = 2 THEN (SELECT COUNT(`id`) FROM `o_event` WHERE `ais` = 2)
    END as `obj_cnt`,
    COUNT(DISTINCT IF(`objects`.`equal_cnt` > 1 AND `objects`.`type_id1` <> 2, `objects`.`obj_id1`, NULL)) as `equal_cnt`
  FROM
    `spr_objects` as o
  LEFT JOIN
    (SELECT
      CASE
        WHEN o.`id` = r.`from_obj_type` THEN r.`from_obj`
        WHEN o.`id` = r.`to_obj_type` THEN r.`to_obj`
      END as `obj_id1`,
      CASE
        WHEN o.`id` = r.`from_obj_type` THEN r.`from_obj_type`
        WHEN o.`id` = r.`to_obj_type` THEN r.`to_obj_type`
      END as `type_id1`,
      CASE
        WHEN o.`id` = r.`from_obj_type` THEN r.`to_obj_type`
        WHEN o.`id` = r.`to_obj_type` THEN r.`from_obj_type`
      END as `type_id2`,
      COUNT(
        CASE
          WHEN o.`id` = r.`from_obj_type` THEN r.`to_obj_type`
          WHEN o.`id` = r.`to_obj_type` THEN r.`from_obj_type`
        END
      ) as `equal_cnt`
    FROM
      `spr_objects` as o
    LEFT JOIN
      `l_relatives` as r ON
        CASE
          WHEN o.`id` = r.`from_obj_type` THEN o.`id` = r.`from_obj_type`
          WHEN o.`id` = r.`to_obj_type` THEN o.`id` = r.`to_obj_type`
        END
    WHERE
      r.`id` IS NOT NULL AND
      r.`ais` = 2
    GROUP BY
      `obj_id1`, `type_id1`, `type_id2`
    ) as `objects` ON
    objects.`type_id1` = o.`id`
  GROUP BY
    o.`id`
') or die(mysql_error());
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?= $ais ?></title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="css/new_tmp.css">
  <script type="text/javascript">
    function row_show(elem) {
      var row = document.getElementById(elem);
      (row.style.display == 'none') ? (row.style.display = 'table-row') : (row.style.display = 'none');
    }
  </script>
</head>
<style>
</style>
<body>
<?php
$total = null;
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<center><span style="font-size: 1.2em;"><strong><?= $ais ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<div id="yearList">
  <?php foreach ($yearList as $value) :?>
    <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?year='.$value ?>"><?= $value ?></a>
  <?php endforeach; ?>
</div>
  
<table rules="all" border="1" cellpadding="3" rules="all" class="total_indicators result_table">
  <tr class="table_head">
    <th width="170px">ОВД</th>
    <th width="50px">Всего<br />зарег.</th>
    <th width="50px">Введено</th>
    <th width="50px">Не<br />введено</th>
  </tr>
  <?php foreach ($comb as $ovd => $row) : ?>
    <tr>
      <td><?= $row['ovd'] ?></td>

      <td align="center">
      <?php if (empty($row['reg'])) : ?>
        0
      <?php else : ?>
        <?= $row['reg'] ?>
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
        <a href="<?= 'events_dept.php?ovd_id='.$ovd ?>"><?= $row['debt'] ?></a>
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

<table rules="all" border="1" cellpadding="3" rules="all" class="objects_indicators result_table">
      <tr class="table_head">
        <th width="240px">Объект</th>
        <th>Всего</th>
        <th>Совпа-<br/>дения</th>
      </tr>
      <?php 
      $obj_cnt = $equal_cnt = 0;
      while($r_objects = mysql_fetch_assoc($q_objects)) : 
        $obj_cnt += $r_objects['obj_cnt'];
        $equal_cnt += $r_objects['equal_cnt']; ?>
        <tr>
          <td>
            <?= $r_objects['object'] ?>
            <?php if (in_array($r_objects['id'], array(2))): ?>
              <span class="analysis_button" onclick="row_show('row_<?= $r_objects['id'] ?>')">Анализ...</span>
            <?php endif; ?>
          </td>
          <?php if ($r_objects['obj_cnt'] > 0) : ?>
            <td align="center"><a href="objects_list.php?object=<?= $r_objects['id'] ?>"><?= $r_objects['obj_cnt'] ?></a></td>
          <?php else : ?>
            <td align="center"><?= $r_objects['obj_cnt'] ?></td>
          <?php endif; ?>
          <?php if ($r_objects['id'] == 2) : ?>
            <td align="center">-</td>
          <?php elseif ($r_objects['equal_cnt'] == 0) : ?>
            <td align="center"><?= $r_objects['equal_cnt'] ?></td>
          <?php else : ?>
            <td align="center"><a href="equal_list.php?object=<?= $r_objects['id'] ?>"><?= $r_objects['equal_cnt'] ?></a></td>
          <?php endif; ?>
        </tr>
        <?php if ($r_objects['id'] == 2): ?>
          <tr id="row_<?= $r_objects['id'] ?>" style="background-color: rgb(229, 226, 193); display: none;">
            <td colspan="3">
              <ul>
                <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_event_marking.php">по способу совершения</a></li>
                <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_event_messenger.php">с использованием мессенджера</a></li>
              </ul>
            </td>
          </tr>
        <?php endif; ?>
        
      <?php endwhile; ?>
      <tr style="background: #F4F4F4;">
        <th>Итого</th>
        <th align="center"><?= $obj_cnt ?></th>
        <th align="center"><?= $equal_cnt ?></th>
      </tr>
    </table>
<?php
require_once('footer.php');
?>
</body>
</html>
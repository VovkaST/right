<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$page_title = 'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела';

$breadcrumbs = array(
  'Процессуальные документы, вынесенные по результатам расследования УД' => ''
);

$yearList = IndictmentsYearsList();

if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = (integer)$_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

$lastSwap = lastSwap('ic');

require(KERNEL.'connect.php');

$query = '
  SELECT
    ovd.`id_ovd` as `id`, ovd.`ovd`,
    IFNULL(`cnts`.`total`, 0) as `total`, IFNULL(`debts`.`cnt_ovd`, 0) as `cnt_ovd`, IFNULL(`debts`.`cnt_sk`, 0) as `cnt_sk`,
    IFNULL(`cnts`.`jan`, 0) as `jan`, IFNULL(`cnts`.`feb`, 0) as `feb`, IFNULL(`cnts`.`mar`, 0) as `mar`,
    IFNULL(`cnts`.`apr`, 0) as `apr`, IFNULL(`cnts`.`may`, 0) as `may`, IFNULL(`cnts`.`jun`, 0) as `jun`,
    IFNULL(`cnts`.`jul`, 0) as `jul`, IFNULL(`cnts`.`aug`, 0) as `aug`, IFNULL(`cnts`.`sep`, 0) as `sep`,
    IFNULL(`cnts`.`oct`, 0) as `oct`, IFNULL(`cnts`.`nov`, 0) as `nov`, IFNULL(`cnts`.`dec`, 0) as `dec`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    (
      SELECT
        f1.`d01_f10` as `ovd`,
        COUNT(DISTINCT f1.`id`) as `total`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 1, f1.`id`, NULL)
        ) as `jan`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 2, f1.`id`, NULL)
        ) as `feb`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 3, f1.`id`, NULL)
        ) as `mar`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 4, f1.`id`, NULL)
        ) as `apr`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 5, f1.`id`, NULL)
        ) as `may`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 6, f1.`id`, NULL)
        ) as `jun`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 7, f1.`id`, NULL)
        ) as `jul`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 8, f1.`id`, NULL)
        ) as `aug`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 9, f1.`id`, NULL)
        ) as `sep`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 10, f1.`id`, NULL)
        ) as `oct`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 11, f1.`id`, NULL)
        ) as `nov`,
        COUNT(DISTINCT
          IF(MONTH(f.`create_date`) = 12, f1.`id`, NULL)
        ) as `dec`
      FROM
        `ic_f1_f11` as f1
      JOIN
        `ic_f1_files` as ff ON
          ff.`f1` = f1.`id` AND
          ff.`deleted` = 0
        JOIN
          `l_files` as f ON
            f.`id` = ff.`file` AND
            f.`create_date` BETWEEN STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-12-31", "%Y-%m-%d") AND
            f.`active_id` <> 0
      WHERE
        f1.`d04_f10` = 1
      GROUP BY
        f1.`d01_f10`
    ) as `cnts` ON
      `cnts`.`ovd` = ovd.`id_ovd`
  LEFT JOIN
    (
      SELECT DISTINCT
        d.`ovd`,
        COUNT(DISTINCT IF(d.`org` = 1, CONCAT(d.`number`, d.`year`), NULL)) as `cnt_ovd`,
        COUNT(DISTINCT IF(d.`org` = 2, CONCAT(d.`number`, d.`year`), NULL)) as `cnt_sk`
      FROM
        `ic_debts` as d
      LEFT JOIN
        `ic_f1_files` as f ON
          f.`f1` = d.`f1` AND
          f.`deleted` = 0
      WHERE
        f.`id` IS NULL AND
        d.`dec_date` >= STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d")
      GROUP BY
        d.`ovd`
    ) as `debts` ON
      `debts`.`ovd` = ovd.`id_ovd`
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN (5,6,7,59,60,61,62,63,64,65,66,67, 68)
';
$result = $db->query($query);
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>

<div class="decision_top_block actions_list">
  <div class="left_box">
    <div id="yearList">
      <?php foreach ($yearList as $value) :?>
        <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?year='.$value ?>"><?= $value ?></a>
      <?php endforeach; ?>
    </div>
    <div class="debt_date">
      Долги на <?= date('G:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
    </div>
  </div>
  <?php if (!empty($_SESSION['user']['user'])) : ?>
    <div class="search_box">
      <a href="search.php">Поиск</a>
    </div>
  <?php endif; ?>
</div>

<table rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable">
  <tr class="table_head">
    <th rowspan="3">ОВД</th>
    <th colspan="3"><?= $year ?> год</th>
    <th colspan="12">Внесено по месяцам</th>
  </tr>
  <tr class="table_head">
    <th rowspan="2" width="60px">Внесено</th>
    <th colspan="2" width="120px">Долг</th>
    <th rowspan="2" width="40px">янв</th>
    <th rowspan="2" width="40px">фев</th>
    <th rowspan="2" width="40px">мар</th>
    <th rowspan="2" width="40px">апр</th>
    <th rowspan="2" width="40px">май</th>
    <th rowspan="2" width="40px">июн</th>
    <th rowspan="2" width="40px">июл</th>
    <th rowspan="2" width="40px">авг</th>
    <th rowspan="2" width="40px">сен</th>
    <th rowspan="2" width="40px">окт</th>
    <th rowspan="2" width="40px">ноя</th>
    <th rowspan="2" width="40px">дек</th>
  </tr>
  <tr class="table_head">
    <th width="60px">ОВД</th>
    <th width="60px">СК</th>
  </tr>
  <?php
    
      $total = $cnt_ovd = $cnt_sk = $jan = $feb = $mar = $apr = $may = $jun = $jul = $aug = $sep = $oct = $nov = $dec = 0;
      while ($row = $result->fetch_object()) :?>
        <tr>
          <td><?= $row->ovd ?></td>
          <td align="center"><?php if ($row->total) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'">'.$row->total ?></td><?php $total += $row->total; ?>
          <td align="center"><?php if ($row->cnt_ovd) echo '<a href="list.php?type=debt&ovd='.$row->id.'&year='.$year.'">'.$row->cnt_ovd ?></td><?php $cnt_ovd += $row->cnt_ovd; ?></td>
          <td align="center"><?php if ($row->cnt_sk) echo '<a href="list.php?type=debtsk&ovd='.$row->id.'&year='.$year.'">'.$row->cnt_sk ?></td><?php $cnt_sk += $row->cnt_sk; ?></td>
          <td align="center"><?php if ($row->jan) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=01">'.$row->jan.'</a>' ?></td><?php $jan += $row->jan; ?>
          <td align="center"><?php if ($row->feb) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=02">'.$row->feb.'</a>' ?></td><?php $feb += $row->feb; ?>
          <td align="center"><?php if ($row->mar) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=03">'.$row->mar.'</a>' ?></td><?php $mar += $row->mar; ?>
          <td align="center"><?php if ($row->apr) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=04">'.$row->apr.'</a>' ?></td><?php $apr += $row->apr; ?>
          <td align="center"><?php if ($row->may) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=05">'.$row->may.'</a>' ?></td><?php $may += $row->may; ?>
          <td align="center"><?php if ($row->jun) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=06">'.$row->jun.'</a>' ?></td><?php $jun += $row->jun; ?>
          <td align="center"><?php if ($row->jul) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=07">'.$row->jul.'</a>' ?></td><?php $jul += $row->jul; ?>
          <td align="center"><?php if ($row->aug) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=08">'.$row->aug.'</a>' ?></td><?php $aug += $row->aug; ?>
          <td align="center"><?php if ($row->sep) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=09">'.$row->sep.'</a>' ?></td><?php $sep += $row->sep; ?>
          <td align="center"><?php if ($row->oct) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=10">'.$row->oct.'</a>' ?></td><?php $oct += $row->oct; ?>
          <td align="center"><?php if ($row->nov) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=11">'.$row->nov.'</a>' ?></td><?php $nov += $row->nov; ?>
          <td align="center"><?php if ($row->dec) echo '<a href="list.php?type=added&ovd='.$row->id.'&year='.$year.'&month=12">'.$row->dec.'</a>' ?></td><?php $dec += $row->dec; ?>
        </tr>
      <?php endwhile; ?>
      <tr style="background: #F4F4F4;">
        <th>Итого</th>
        <th align="center"><?php if ($total) echo $total ?></th>
        <th align="center"><?php if ($cnt_ovd) echo $cnt_ovd ?></th>
        <th align="center"><?php if ($cnt_sk) echo $cnt_sk ?></th>
        <th align="center"><?php if ($jan) echo $jan ?></th>
        <th align="center"><?php if ($feb) echo $feb ?></th>
        <th align="center"><?php if ($mar) echo $mar ?></th>
        <th align="center"><?php if ($apr) echo $apr ?></th>
        <th align="center"><?php if ($may) echo $may ?></th>
        <th align="center"><?php if ($jun) echo $jun ?></th>
        <th align="center"><?php if ($jul) echo $jul ?></th>
        <th align="center"><?php if ($aug) echo $aug ?></th>
        <th align="center"><?php if ($sep) echo $sep ?></th>
        <th align="center"><?php if ($oct) echo $oct ?></th>
        <th align="center"><?php if ($nov) echo $nov ?></th>
        <th align="center"><?php if ($dec) echo $dec ?></th>
      </tr>
</table>

<?php $result->close(); ?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
<?php $db->close(); ?>
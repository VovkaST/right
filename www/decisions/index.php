<?php
set_time_limit(0);
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$page_title = 'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела';

$breadcrumbs = array(
  'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела' => ''
);

$yearList = decisionYears();
if (!empty($_GET["resultYear"])) {
  if (in_array($_GET["resultYear"], $yearList)) {
    $year = $_GET["resultYear"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}
$lastSwap = lastSwap('legenda');

require(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    ovd.`id_ovd`, ovd.`ovd`,
    COUNT(DISTINCT d.`id`) as `total`, `debts`.`cnt` as `debt`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 01, d.`id`, NULL)) as `jan`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 02, d.`id`, NULL)) as `feb`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 03, d.`id`, NULL)) as `mar`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 04, d.`id`, NULL)) as `apr`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 05, d.`id`, NULL)) as `may`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 06, d.`id`, NULL)) as `jun`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 07, d.`id`, NULL)) as `jul`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 08, d.`id`, NULL)) as `aug`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 09, d.`id`, NULL)) as `sep`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 10, d.`id`, NULL)) as `oct`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 11, d.`id`, NULL)) as `nov`,
    COUNT(DISTINCT IF(MONTH(d.`date`) = 12, d.`id`, NULL)) as `dec`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `l_decisions` as d ON
      d.`ovd` = ovd.`id_ovd` AND
      YEAR(d.`date`) = '.$year.' AND
      d.`type` = 1 AND
      d.`deleted` = 0
  LEFT JOIN
    (
      SELECT
        `ovd`,
        COUNT(IF(`tmp`.`cnt` = 0, 1, 0)) as `cnt`
      FROM
        (
          SELECT
            DISTINCT
            dh.`ovd`,
            dh.`kusp`,
            COUNT(d.`id`) as `cnt`
          FROM
            `ek_dec_refusals` as dh
          LEFT JOIN
            `l_kusp` as k ON
              k.`ek` = dh.`kusp`
            LEFT JOIN
              `l_dec_kusp` as dk ON
                dk.`kusp` = k.`id` AND
                dk.`deleted` = 0
              LEFT JOIN
                `l_decisions` as d ON
                  d.`id` = dk.`decision` AND
                  d.`deleted` = 0
          WHERE
            dh.`dec_date` BETWEEN STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-12-31", "%Y-%m-%d") AND
            dh.`dec_date` >= STR_TO_DATE("2015-01-01", "%Y-%m-%d")
          GROUP BY
            dh.id_SB_RESH
            
        ) as `tmp`
      WHERE
        `tmp`.`cnt` = 0
      GROUP BY
        `tmp`.`ovd`
    ) as `debts` ON
      debts.`ovd` = ovd.`id_ovd`
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN ( 60, 61, 63, 65, 66, 68)
  GROUP BY
    ovd.`id_ovd`
') or print(mysql_error());

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
/*

*/
?>
<div class="decision_top_block actions_list">
  <div class="left_box">
    <div id="yearList">
      <?php foreach ($yearList as $value) :?>
        <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?resultYear='.$value ?>"><?= $value ?></a>
      <?php endforeach; ?>
    </div>
    <div class="debt_date">
      Долги на <?= date('G:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
    </div>
    <div class="debt_date"></div>
  </div>
  <div class="add_box">
    <a href="decision.php" method="add">Добавить новый</a>
  </div>
  <?php if (!empty($_SESSION['decision'])) : ?>
    <div class="continue_box">
      <a href="decision.php" method="continue">Продолжить ввод</a>
    </div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['user']['user'])) : ?>
    <div class="search_box">
      <a href="search.php">Поиск</a>
    </div>
  <?php endif; ?>
</div>

<table rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable">
  <tr class="table_head">
    <th rowspan="2">ОВД</th>
    <th colspan="2"><?= $year ?> год</th>
    <th colspan="12">Внесено по месяцам</th>
  </tr>
  <tr class="table_head">
    <th width="60px">Внесено</th>
    <th width="60px">Долг</th>
    <th width="40px">янв</th>
    <th width="40px">фев</th>
    <th width="40px">мар</th>
    <th width="40px">апр</th>
    <th width="40px">май</th>
    <th width="40px">июн</th>
    <th width="40px">июл</th>
    <th width="40px">авг</th>
    <th width="40px">сен</th>
    <th width="40px">окт</th>
    <th width="40px">ноя</th>
    <th width="40px">дек</th>
  </tr>
  <?php
    if (count($query)) :
      $total = $debt = $jan = $feb = $mar = $apr = $may = $jun = $jul = $aug = $sep = $oct = $nov = $dec = 0;
      while ($result = mysql_fetch_array($query)) :?>
        <tr>
          <td><?= $result['ovd'] ?></td>
          <td align="center"><?php if ($result['total']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'">'.$result['total'] ?></td><?php $total += $result['total']; ?>
          <td align="center"><?php if ($result['debt']) echo '<a href="debts.php?ovd='.$result['id_ovd'].'&year='.$year.'">'.$result['debt'] ?></td><?php $debt += $result['debt']; ?></td>
          <td align="center"><?php if ($result['jan']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=01">'.$result['jan'].'</a>' ?></td><?php $jan += $result['jan']; ?>
          <td align="center"><?php if ($result['feb']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=02">'.$result['feb'].'</a>' ?></td><?php $feb += $result['feb']; ?>
          <td align="center"><?php if ($result['mar']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=03">'.$result['mar'].'</a>' ?></td><?php $mar += $result['mar']; ?>
          <td align="center"><?php if ($result['apr']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=04">'.$result['apr'].'</a>' ?></td><?php $apr += $result['apr']; ?>
          <td align="center"><?php if ($result['may']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=05">'.$result['may'].'</a>' ?></td><?php $may += $result['may']; ?>
          <td align="center"><?php if ($result['jun']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=06">'.$result['jun'].'</a>' ?></td><?php $jun += $result['jun']; ?>
          <td align="center"><?php if ($result['jul']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=07">'.$result['jul'].'</a>' ?></td><?php $jul += $result['jul']; ?>
          <td align="center"><?php if ($result['aug']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=08">'.$result['aug'].'</a>' ?></td><?php $aug += $result['aug']; ?>
          <td align="center"><?php if ($result['sep']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=09">'.$result['sep'].'</a>' ?></td><?php $sep += $result['sep']; ?>
          <td align="center"><?php if ($result['oct']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=10">'.$result['oct'].'</a>' ?></td><?php $oct += $result['oct']; ?>
          <td align="center"><?php if ($result['nov']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=11">'.$result['nov'].'</a>' ?></td><?php $nov += $result['nov']; ?>
          <td align="center"><?php if ($result['dec']) echo '<a href="formation_results.php?ovd='.$result['id_ovd'].'&year='.$year.'&month=12">'.$result['dec'].'</a>' ?></td><?php $dec += $result['dec']; ?>
        </tr>
      <?php endwhile; ?>
      <tr style="background: #F4F4F4;">
        <th>Итого</th>
        <th align="center"><?php if ($total) echo $total ?></th>
        <th align="center"><?php if ($debt) echo $debt ?></th>
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
    <?php endif; ?>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
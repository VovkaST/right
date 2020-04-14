<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$yearList = selectRefuseYears();
if (!empty($_GET["resultYear"])) {
  if (in_array($_GET["resultYear"], $yearList)) {
    $year = $_GET["resultYear"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}
$lastSwap = lastSwap('refuse_sync');
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset=utf-8>
 <title>Долги, недостатки и результаты формирования</title>
 <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
 <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= CSS ?>head.css">
 <link rel="stylesheet" href="<?= CSS ?>main.css">
 <link rel="stylesheet" href="<?= CSS ?>new.css">
 <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
 <script src="<?= JS ?>jquery-1.10.2.js"></script>
 <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
 <script src="<?= JS ?>procedures.js"></script>
 <script src="<?= JS ?>procedures.js"></script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= REFUSAL_VIEW_UPLOAD ?>">Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Долги, недостатки и результаты формирования
</div>
<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <div id="yearList">
    <?php foreach ($yearList as $value) :?>
      <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?resultYear='.$value ?>"><?= $value ?></a>
    <?php endforeach; ?>
  </div>
  <div class="debt_date">
    Долги на <?= date('G:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
  </div>
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
    require(KERNEL.'connection.php');
    $query = mysql_query('
      SELECT
        so.id_ovd,
        so.ovd,
        (
          SELECT
            COUNT(*)
          FROM
            leg_decisions as ld
          LEFT JOIN
            kusp ON
              year(kusp.data) = year(ld.reg_date) AND
              kusp.kusp = ld.reg_num AND
              kusp.ovd = ld.id_ovd
          WHERE
            ld.dec_date > "'.$year.'-01-01" AND
            ld.dec_date < "'.$year.'-12-31" AND
            ld.dec_date > "2015-01-01" AND
            kusp.id IS NULL AND
            ld.id_ovd = so.id_ovd AND
            ld.error_rec = 1
        ) as debt,
        COUNT(DISTINCT o.id) as fullCount,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "01", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "01", o.id, NULL)), null) as `jan`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "02", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "02", o.id, NULL)), NULL) as `feb`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "03", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "03", o.id, NULL)), NULL) as `mar`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "04", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "04", o.id, NULL)), NULL) as `apr`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "05", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "05", o.id, NULL)), NULL) as `may`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "06", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "06", o.id, NULL)), NULL) as `jun`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "07", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "07", o.id, NULL)), NULL) as `jul`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "08", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "08", o.id, NULL)), NULL) as `aug`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "09", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "09", o.id, NULL)), NULL) as `sep`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "10", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "10", o.id, NULL)), NULL) as `okt`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "11", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "11", o.id, NULL)), NULL) as `nov`,
        IF(COUNT(DISTINCT IF(MONTH(data_resh) = "12", o.id, NULL)) > 0, COUNT(DISTINCT IF(MONTH(data_resh) = "12", o.id, NULL)), NULL) as `dec`
      FROM
        spr_ovd as so
      LEFT JOIN
        otkaz as o ON
          o.id_ovd = so.id_ovd AND
          o.deleted = 0 AND
          o.is_file = 1 AND
          year(o.data_resh) = "'.$year.'" 
      WHERE
        so.visuality = 1
      GROUP BY
        so.id_ovd
    ') or print(mysql_error());
    if (count($query)) :
      $fullCount = $jan = $feb = $mar = $apr = $may = $jun = $jul = $aug = $sep = $okt = $nov = $dec = 0;
      while ($result = mysql_fetch_array($query)) :?>
        <tr>
          <td><?= $result['ovd'] ?></td>
          <td align="center"><?php if ($result['fullCount']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'">'.$result['fullCount'] ?></td><?php $fullCount += $result['fullCount']; ?>
          <td align="center"><?php if ($result['debt']) echo '<a href="debt_ovd.php?id_ovd='.$result['id_ovd'].'&debtYear='.$year.'">'.$result['debt'].'</a>' ?></td><?php $debt += $result['debt']; ?>
          <td align="center"><?php if ($result['jan']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=01">'.$result['jan'].'</a>' ?></td><?php $jan += $result['jan']; ?>
          <td align="center"><?php if ($result['feb']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=02">'.$result['feb'].'</a>' ?></td><?php $feb += $result['feb']; ?>
          <td align="center"><?php if ($result['mar']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=03">'.$result['mar'].'</a>' ?></td><?php $mar += $result['mar']; ?>
          <td align="center"><?php if ($result['apr']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=04">'.$result['apr'].'</a>' ?></td><?php $apr += $result['apr']; ?>
          <td align="center"><?php if ($result['may']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=05">'.$result['may'].'</a>' ?></td><?php $may += $result['may']; ?>
          <td align="center"><?php if ($result['jun']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=06">'.$result['jun'].'</a>' ?></td><?php $jun += $result['jun']; ?>
          <td align="center"><?php if ($result['jul']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=07">'.$result['jul'].'</a>' ?></td><?php $jul += $result['jul']; ?>
          <td align="center"><?php if ($result['aug']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=08">'.$result['aug'].'</a>' ?></td><?php $aug += $result['aug']; ?>
          <td align="center"><?php if ($result['sep']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=09">'.$result['sep'].'</a>' ?></td><?php $sep += $result['sep']; ?>
          <td align="center"><?php if ($result['okt']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=10">'.$result['okt'].'</a>' ?></td><?php $okt += $result['okt']; ?>
          <td align="center"><?php if ($result['nov']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=11">'.$result['nov'].'</a>' ?></td><?php $nov += $result['nov']; ?>
          <td align="center"><?php if ($result['dec']) echo '<a href="formation_results_ovd.php?id_ovd='.$result['id_ovd'].'&resultYear='.$year.'&month=12">'.$result['dec'].'</a>' ?></td><?php $dec += $result['dec']; ?>
        </tr>
      <?php endwhile; ?>
        <tr style="background: #F4F4F4;">
          <th>Итого</th>
          <th align="center"><?php if ($fullCount) echo $fullCount ?></th>
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
          <th align="center"><?php if ($okt) echo $okt ?></th>
          <th align="center"><?php if ($nov) echo $nov ?></th>
          <th align="center"><?php if ($dec) echo $dec ?></th>
        </tr>
    <?php endif; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
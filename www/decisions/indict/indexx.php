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

if(empty($_GET['date_f'])) $_GET['date_f'] = date("Y-m-d", strtotime('01.'.date("m").".".date("Y")));
else $_GET['date_f'] = date("Y-m-d", strtotime($_GET['date_f']));
if(empty($_GET['date_t'])) $_GET['date_t'] = date("Y-m-d");
else $_GET['date_t'] = date("Y-m-d", strtotime($_GET['date_t']));
$lastSwap = lastSwap('ic');

require(KERNEL.'connect.php');

$query = "
		SELECT DISTINCT
      d.d01_load as ovd,
      d.vd01_load as ovd_name,
      COUNT(DISTINCT IF(d.d33 <>1, d.rowid, NULL)) as `all`,
      COUNT(DISTINCT IF(d.d33 in (2,3,35,36), d.rowid, NULL)) as `all_sled`,
      COUNT(DISTINCT IF(d.d33 in (5,24,37), d.rowid, NULL)) as `all_dozn`,
      COUNT(DISTINCT IF(d.d33 in (2,3,35,36)  and f.id is not null, d.rowid, NULL)) as `v_sled`,
      COUNT(DISTINCT IF(d.d33 in (5,24,37)  and f.id is not null, d.rowid, NULL)) as `v_dozn`,
      COUNT(DISTINCT IF(d.d33 in (2,3,35,36)  and f.id is null, d.rowid, NULL)) as `debt_sled`,
      COUNT(DISTINCT IF(d.d33 in (5,24,37)  and f.id is null, d.rowid, NULL)) as `debt_dozn`,
      COUNT(DISTINCT IF(d.d33 = 1  and f.id is null, d.rowid, NULL))  as `cnt_sk`
    FROM
      `ic_for_load` as d  LEFT JOIN `ic_f1_files` as f ON f.`f1` = d.id_f1  AND  f.`deleted` = 0 
    WHERE
      d.del = 0 and d.d25_f11 between '".$_GET['date_f']."' and '".$_GET['date_t']."'
    GROUP BY
      d.d01_load
 ";
$result = $db->query($query);
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>


<div>
<form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>">
  
    <div class="dec_period">
          <div class="field_box">
            <span class="field_name">Решение:</span>
            <?= (empty($_GET['date_f'])) ? my_date_field('date_f') : my_date_field('date_f', $_GET['date_f']) ?>
          </div>
          <div class="field_box">
            <span class="field_name">&ndash;</span>
            <?= (empty($_GET['date_t'])) ? my_date_field('date_t') : my_date_field('date_t', $_GET['date_t']) ?>
          </div>
    </div>
    <div class="add_button_box">
        <div class="button_block"><span class="button_name">Искать</span></div>
      </div>
</form>
</div></br></br></br></br></br>

<div>
<table rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable">
  <tr class="table_head">
    <th width="240" height="40">ОВД</th>
    <th width="100">Всего</th>
    <th width="100">Внесено</th>
    <th width="100">Долг</th>
  </tr>
  
  <?php
    
      $total = $total_v = $total_d = 0;
      while ($row = $result->fetch_object()) :?>
        <tr>
          <td style = "font-weight:bold"><?= $row->ovd_name ?></td><?php $total += $row->all; ?>
          <td style = "font-weight:bold" align="center"><?= $row->all_sled + $row->all_dozn ?></td>
          <td style = "font-weight:bold" align="center"><?= $row->v_sled+$row->v_dozn ?></td><?php $total_v += $row->v_sled+$row->v_dozn; ?>
          <td style = "font-weight:bold" align="center"><?= $row->debt_sled+$row->debt_dozn ?></td><?php $total_d += $row->debt_sled+$row->debt_dozn; ?>
        </tr>
        <tr>
          <td>--Дознание</td>
          <td align="center"><?= $row->all_dozn?></td>
          <td align="center"><?= $row->v_dozn?></td>
          <td align="center"><?= $row->debt_dozn?></td>
        </tr>
        <tr>
          <td>--Следствие</td>
          <td align="center"><?= $row->all_sled?></td>
          <td align="center"><?= $row->v_sled?></td>
          <td align="center"><?= $row->debt_sled?></td>
        </tr>
      <?php endwhile; ?>
      <tr style="background: #F4F4F4;">
        <th>Итого</th>
        <th align="center"><?php if ($total) echo $total ?></th>
        <th align="center"><?php if ($total_v) echo $total_v ?></th>
        <th align="center"><?php if ($total_d) echo $total_d ?></th>
       
      </tr>
</table>
</div>
<?php $result->close(); ?>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
<?php $db->close(); ?>
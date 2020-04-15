<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (!empty($_GET["ovd"]) && is_numeric($_GET["ovd"])) {
  $ovd = array_merge(array('id' => intval($_GET["ovd"])), getOvdName($_GET["ovd"]));
} else {
  header('Location: formation_results.php');
}

$page_title = 'Долги по вводу';

$breadcrumbs = array(
  'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела' => 'index.php',
  'Долги по вводу' => ''
);

$yearList = decisionYears();
if (!empty($_GET["year"]) && is_numeric($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}
$lastSwap = OVDlastSwap($ovd['id']);
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
/*
<div class="debt_date">
  Последняя выгрузка до выставления долгов в <?= date('H:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
</div>
*/
?>
<h3><?= $ovd['ovd_full'] ?></h3>
<div class="debt_date"></div>
<?php
$query = mysql_query('
  SELECT
      CONCAT("/wonc/ek.php?id=", dh.`kusp`) as link,
      dh.`id_SB_RESH` as `id`,
      dh.`ovd`,
      dh.`reg_number`,
      DATE_FORMAT(dh.`reg_date`, "%d.%m.%Y") as `reg_date`,
      GROUP_CONCAT(
        DISTINCT
          dh.`dec_number`
        ORDER BY
          dh.`dec_number`
        SEPARATOR
          "<br />"
      ) as `dec_num`,
      GROUP_CONCAT(
        DISTINCT
          DATE_FORMAT(dh.`dec_date`, "%d.%m.%Y")
        ORDER BY
          dh.`dec_date`
        SEPARATOR
          "<br />"
      ) as `dec_date`,
      CONCAT(
        IF(dh.`emp_person` IS NOT NULL, dh.`emp_person`, ""),
        IF(dh.`emp_position` IS NOT NULL, 
           CONCAT(", ", 
                     LOWER(
                       IF(
                        POSITION("," IN dh.`emp_position`) > 0,
                        SUBSTRING(dh.`emp_position`, 1, POSITION("," IN dh.`emp_position`) - 1),
                        dh.`emp_position`)
                     )
                 ),
           "")
      ) as `emp`,
      dh.`proc_par` as `upk`,
      GROUP_CONCAT(
        DISTINCT
          dh.`qualification`
        ORDER BY
          dh.`qualification`
        SEPARATOR
          "<br />"
      ) as `uk`,
      GROUP_CONCAT(
        d.`id`
      ) as `dec_id`
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
      AND dh.`ovd` = '.$ovd['id'].'
    GROUP BY
      dh.`kusp`
    HAVING
      `dec_id` IS NULL
') or die(mysql_error());
$i = 1;
?>
<table rules="all" border="1" cellpadding="5" align="center" class="result_table">
  <tr class="table_head">
    <th>№<br/>п/п</th>
    <th width="80px">Рег.№<br/>КУСП</th>
    <th width="80px">Дата рег.</th>
    <th width="80px">№<br/>решения</th>
    <th width="80px">Дата<br/>решения</th>
    <th>Решение вынес</th>
    <th width="70px">ст.<br/>УК РФ</th>
    <th>пункт<br/>ст.24 УПК</th>
  </tr>
  <?php while ($result = mysql_fetch_assoc($query)): ?>
  <tr>
    <td align="center"><?= $i++ ?>.</td>
    <td align="center"><a href="<?= $result['link'] ?>" target="_blank" title="Электронный КУСП"><?= $result['reg_number'] ?></a></td>
    <td align="center"><?= $result['reg_date'] ?></td>
    <td align="center"><?= $result['dec_num'] ?></td>
    <td align="center"><?= $result['dec_date'] ?></td>
    <td><?= $result['emp'] ?></td>
    <td align="center"><?= $result['uk'] ?></td>
    <td>
      <div class="info_block"><?= $result['upk'] ?></div>
      <div class="links_block">
        <a href="decision.php" id="<?= $result['id'] ?>" method="debt">
          <img src="/images/plus.png" height="25px" border="none" alt="Добавить">
        </a>
      </div>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
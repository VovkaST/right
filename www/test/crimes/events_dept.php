<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET["ovd_id"]) || !is_numeric($_GET["ovd_id"]) || !isset($_GET["type"])) {
  header('Location: index.php');
}
$ovd_id = floor(abs($_GET["ovd_id"]));
$type = floor(abs($_GET["type"]));

require_once(KERNEL.'connection.php');
switch ($type) {
  
  case 1:
    $query_d = mysql_query('
      SELECT
        dc.`ovd` as `ovd_id`, dc.`number`, 
        DATE_FORMAT(dc.`date`, "%d.%m.%Y") as `case_date`,
        dc.`employeer`, dc.`story`, uk.`st`
      FROM
        `l_dist_crimes` as dc
      JOIN
        spr_ovd as ovd ON
          ovd.`ibd_code` = dc.`ovd`
      LEFT JOIN
        `o_event` as e ON
          e.`decision_number` = dc.`number` AND
          YEAR(e.`decision_date`) = YEAR(dc.`date`) AND
          e.`decision` = 2
      LEFT JOIN
        `spr_uk` as uk ON
          uk.`id_uk` = dc.`article_id`
      WHERE
        ovd.`id_ovd` = '.$ovd_id.' AND
        e.`id` IS NULL
    ') or die(mysql_error());
    $debt_type = 'Невведенные уг.дела';
    break;
  
  case 2:
    $query_d = mysql_query('
      SELECT
        ovd.`id_ovd`, e.`decision_number` as `number`,
        DATE_FORMAT(e.`decision_date`, "%d.%m.%Y") as `case_date`,
        e.`employeer`, e.`story`, uk.`st`
      FROM
        `spr_ovd` as ovd
      JOIN
        `o_event` as e ON
          e.`ovd_id` = ovd.`id_ovd`
        LEFT JOIN
          `l_dist_crimes` as dc ON
            dc.`number` = e.`decision_number` AND
            YEAR(e.`decision_date`) = YEAR(dc.`date`)
      LEFT JOIN
        `spr_uk` as uk ON
          uk.`id_uk` = e.`article_id`
      WHERE
        ovd.`id_ovd` = '.$ovd_id.' AND
        dc.`number` IS NULL AND
        e.`decision` = 2
      GROUP BY
        e.`decision_number`, YEAR(e.`decision_date`)
    ') or die(mysql_error());
    $debt_type = 'Невыставленные в ИЦ карточки формы 1.0';
    $lastSwap = lastSwap('dist_crimes');
    break;
  
  case 3:
    $query_d = mysql_query('
      SELECT
        s.`id`, s.`kusp_num`,
        YEAR(s.`reg_date`) as `kusp_year`,
        s.`crim_case`, uk.`st`, s.`story`
      FROM
        `l_dist_crimes_summary` as s
      JOIN
        `spr_ovd` as ovd ON
          ovd.`cronos_code` = s.`ovd_cronos_id`
      LEFT JOIN
        `o_event` as e ON
          e.`kusp_num` = s.`kusp_num` AND
          YEAR(e.`kusp_date`) = YEAR(s.`reg_date`) AND
          ovd.`id_ovd` = e.`ovd_id`
      LEFT JOIN
        `spr_uk` as uk ON
          uk.`id_uk` = s.`article_id`
      WHERE
        ovd.`id_ovd` = '.$ovd_id.' AND
        e.`id` IS NULL
    ') or die(mysql_error());
    $debt_type = 'Невведенные преступления, переданные в сводку ДЧ УМВД';
    $lastSwap = lastSwap('dist_crimes_summary');
    break;
  
  default:
    header('Location: index.php');
    break;
}
$ovd = getOvdName($ovd_id);
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>...</title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <script type="text/javascript" src="<?= JS ?>jquery-1.10.2.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery.inputmask.js"></script>  
  <script type="text/javascript" src="js/procedures.js"></script>
  <script type="text/javascript" src="<?= JS ?>functions.js"></script>
</head>
<style>
</style>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<?= $debt_type ?>
</div>
<center><span style="font-size: 1.2em;"><strong><?= $ovd[1] ?></strong></span></center>
<hr color="#C6C6C6" size="0px"/>

<?php 
$i = 1;
if ($type == 2) : ?>
<div class="debt_date">
  Сведения по состоянию на <?= date('H:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
</div>
<?php endif;
if (in_array($type, array(1, 2))) : ?>
<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th rowspan="2" width="30px">№<br/>п/п</th>
    <th colspan="2">Уг.дело</th>
    <th rowspan="2" width="50px">ст.<br/>УК РФ</th>
    <th rowspan="2" width="90px">Сотрудник</th>
    <th rowspan="2" width="550px">Фабула</th>
  </tr>
  <tr class="table_head">
    <th width="50px">рег.№</th>
    <th width="80px">дата</th>
  </tr>
  <?php
  while ($result = mysql_fetch_assoc($query_d)) : ?>
  <tr>
    <td align="center">
      <?= $i++ ?>.
    </td>
    <td align="center">
      <?= $result["number"] ?>
    </td>
    <td align="center">
      <?= $result["case_date"] ?>
    </td>
    <td align="center">
      <?= $result["st"] ?>
    </td>
    <td align="center">
      <?= mb_convert_case($result["employeer"], MB_CASE_TITLE, "UTF-8") ?>
    </td>
    <td>
      <?= $result["story"] ?>
    </td>
  </tr>
  <?php endwhile; ?>
  <tr>
    <td align="center">
      <?= $i++ ?>.
    </td>
    <td colspan="5">
      <a href="<?= CRIMES ?>event.php?ovd_id=<?= $ovd_id ?>">Добавить...</a>
    </td>
  </tr>
</table>
<?php elseif($type == 3) : ?>
<div class="debt_date">
  Сведения по состоянию на <?= date('H:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
</div>
<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="50px">КУСП</th>
    <th width="50px">год</th>
    <th width="50px">у/д</th>
    <th width="50px">статья<br/>УК РФ</th>
    <th colspan="2" width="550px">Фабула</th>
  </tr>
  <?php
  while ($result = mysql_fetch_assoc($query_d)) : ?>
  <tr>
    <td align="center">
      <?= $i++ ?>.
    </td>
    <td align="center">
      <?= $result["kusp_num"] ?>
    </td>
    <td align="center">
      <?= $result["kusp_year"] ?>
    </td>
    <td align="center">
      <?= $result["crim_case"] ?>
    </td>
    <td align="center">
      <?= $result["st"] ?>
    </td>
    <td style="border-right: none;">
      <?= $result["story"] ?>
    </td>
    <td style="border-left: none;">
      <form action="event.php" method="GET">
      <input type="hidden" name="sum_debt" value="<?= $result["id"] ?>"/>
      <?= save_button() ?>
      </form>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
<?php endif; ?>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
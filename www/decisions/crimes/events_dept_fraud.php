<?php
$need_auth = 1;
if (!isset($_GET["ovd_id"]) || !is_numeric($_GET["ovd_id"]) || !isset($_GET["type"])) {
  header('Location: index.php');
  die();
}

if (!empty($_GET["year"])) {
  //if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }


$ais = is_ais(1);
$ovd_id = floor(abs($_GET["ovd_id"]));
$type = floor(abs($_GET["type"]));

require_once(KERNEL.'connection.php');
switch ($type) {
  
  case 1:
    $query = '
      SELECT `id` FROM `l_dist_crimes`
     WHERE 
    (`number` = 30381 AND `date` = "2015-07-29") OR
    (`number` = 7680 AND `date` = "2016-09-21") OR
    (`number` = 35272 AND `date` = "2016-04-20") OR
    (`number` = 35489 AND `date` = "2016-06-28") or
    (`number` = 19815 AND `date` = "2015-10-14") OR
    (`number` = 45438 AND `date` = "2017-06-03") OR
    (`number` = 35927 AND `date` = "2016-11-28") OR 
    (`number` = 27198 AND `date` = "2017-03-23") OR 
    (`number` = 27215 AND `date` = "2017-03-24") OR 
    (`number` = 27461 AND `date` = "2017-06-23") OR 
    (`number` = 35927 AND `date` = "2016-11-28") OR
    (`number` = 27103 AND `date` = "2017-02-16") or
    (`number` = 44514 AND `date` = "2017-03-14") or
    (`number` = 44517 AND `date` = "2017-03-19") or
    (`number` = 20680 AND `date` = "2016-11-11") or
    (`number` = 48611 AND `date` = "2017-01-23") or 
    (`number` = 12679 AND `date` = "2015-11-18") or
    (`number` = 12688 AND `date` = "2015-11-24") or 
    (`number` = 8062 AND `date` = "2017-02-05") or
    (`number` = 8005 AND `date` = "2017-01-05") or
   
    (`number` = 12303 AND `date` = "2016-03-05") or
    (`number` = 12906 AND `date` = "2016-07-02") or 
    (`number` = 13241 AND `date` = "2016-09-21") or
    (`number` = 13396 AND `date` = "2016-10-29") or 
    (`number` = 5170 AND `date` = "2017-02-14") or
    (`number` = 13653 AND `date` = "2016-12-16") or 
    (`number` = 5465 AND `date` = "2017-04-12") or
    (`number` = 5745 AND `date` = "2017-05-25") or 
    (`number` = 5827 AND `date` = "2017-06-14") or
    (`number` = 1430 AND `date` = "2016-07-22") or
    (`number` = 16665 AND `date` = "2017-12-17") 
    ';
    $result = mysql_query($query);
    while($row = mysql_fetch_assoc($result)) {
      $wout[] = $row['id'];
    }
    
    /*$query_d = mysql_query('
      SELECT
        dc.`ovd` as `ovd_id`, dc.`number`, 
        DATE_FORMAT(dc.`date`, "%d.%m.%Y") as `case_date`,
        dc.`employeer`, dc.`story`, uk.`st`
      FROM
        `l_dist_crimes` as dc
      LEFT JOIN
        `o_event` as e ON
          e.`decision_number` = dc.`number` AND
          YEAR(e.`decision_date`) = YEAR(dc.`date`) AND
          e.`decision` = 2
      LEFT JOIN
        `spr_uk` as uk ON
          uk.`id_uk` = dc.`article_id`
      WHERE
        dc.`ovd` = '.$ovd_id.' AND
        e.`id` IS NULL AND
        dc.`id` NOT IN ('.implode(', ', $wout).')
         
    ') or die(mysql_error());*/
    //$t = ($year==2018)?'ic_dist_crimes_2018':'ic_dist_crimes';
    $query_d = mysql_query("
     select mo.`kod_ovd` as `ovd_id`,
		 mo.`fabula` as `story`,
		 mo.`UD_number` as `number`,
		 DATE_FORMAT(mo.`data_vozbuzhdeniya`, '%d.%m.%Y') as `case_date`,
		 mo.`statya` as `st`,
		 mo.`sledovatel` as `employeer`
    from dist_crimes_unit as mo 
    
    left join 
    (
    SELECT DISTINCT
        e.`id`, e.`kusp_num`,
        DATE_FORMAT(e.`kusp_date`, \"%d.%m.%Y\") as `kusp_date`, 
        e.`decision_number` as decision_number,
        DATE_FORMAT(e.`decision_date`, \"%d.%m.%Y\") as `decision_date`,
        su.`st`, e.`story`,
        e.`ovd_id`
      FROM
        `o_event` as e
      LEFT JOIN
        `spr_uk` as su ON
          su.`id_uk` = e.`article_id`
      LEFT JOIN
        `l_relatives` as rel ON
          rel.`from_obj` = e.`id` AND
          rel.`type` = 12
      LEFT JOIN
        `o_telephone` as t ON
          t.`id` = rel.`to_obj` AND
          rel.`to_obj_type` = 6
      LEFT JOIN
        `l_object_marking` as om ON
          om.`object` = e.`id` AND
          om.`obj_type` = 2
      WHERE
         -- YEAR(e.`decision_date`) = ".$year." and 
         e.`ais` = 1
         
      GROUP BY
       e.`id`
      ORDER BY
        e.`kusp_date`) as ours
        on mo.UD_number = ours.decision_number and year(mo.data_vozbuzhdeniya) = year(str_to_date(ours.decision_date, '%d.%m.%Y')) and mo.kod_ovd = ours.ovd_id
        where mo.kod_ovd = ".$ovd_id." -- and mo.`year` = ".$year." 
        and ours.id is null
        and mo.del is null
        
       and (mo.UD_number<>41 and mo.data_vozbuzhdeniya<>'2019-01-23')
    ") or die(mysql_error());
    
    $debt_type = 'Невведенные уг.дела';
    break;
  
  case 2:
    $query_d = mysql_query('
      SELECT
        ovd.`id_ovd`, e.`decision_number` as `number`,
        DATE_FORMAT(e.`decision_date`, "%d.%m.%Y") as `case_date`,
        e.`employeer`, e.`story`, uk.`st`
      FROM
        `o_event` as e
      JOIN
        `spr_ovd` as ovd ON
          ovd.`ibd_code` = e.`ovd_id`
      LEFT JOIN
        `l_dist_crimes` as dc ON
          dc.`number` = e.`decision_number` AND
          YEAR(e.`decision_date`) = YEAR(dc.`date`)
      JOIN
        `spr_uk` as uk ON
          uk.`id_uk` = e.`article_id`
      WHERE
        dc.`id` IS NULL AND
        e.`decision` = 2 AND
        YEAR(e.`decision_date`) = YEAR(CURRENT_DATE) AND
        e.`ovd_id` = '.$ovd_id.' AND
        e.`ais` = 1
      
      ORDER BY
        e.`decision_number`
    ') or die(mysql_error());
    $debt_type = 'Не заполнен реквизит карточки формы 1.0';
    $lastSwap = lastSwap('ic');
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
          CASE
            WHEN s.`crim_case` IS NULL THEN 
              e.`kusp_num` = s.`kusp_num` AND
              YEAR(e.`kusp_date`) = YEAR(s.`reg_date`)
            ELSE 
              e.`decision_number` = s.`crim_case` AND
              YEAR(e.`decision_date`) = YEAR(s.`reg_date`) AND
              e.`decision` = 2 
          END AND
           
          ovd.`id_ovd` = e.`ovd_id`
      LEFT JOIN
        `spr_uk` as uk ON
          uk.`id_uk` = s.`article_id`
      WHERE
        ovd.`id_ovd` = '.$ovd_id.' AND
       
        e.`id` IS NULL AND
        s.`id` NOT IN( 
          SELECT `id` FROM `l_dist_crimes_summary` WHERE 
            (`ovd_cronos_id` = 2 AND `kusp_num` = 33802 AND `reg_date` = "2015-09-16") OR
            (`ovd_cronos_id` = 2 AND `kusp_num` = 35109 AND `reg_date` = "2015-09-25") OR
            (`ovd_cronos_id` = 42 AND `kusp_num` = 851 AND `reg_date` = "2015-10-01")
        )
    ') or die(mysql_error());
    $debt_type = 'Невведенные преступления, переданные в сводку ДЧ УМВД';
    $lastSwap = lastSwap('dist_crimes_summary');
    break;
  
  case 4:
    $query_d = mysql_query('
      SELECT
        e.`id`, 
        IF(
          e.`decision_number` IS NULL,
          CONCAT("КУСП ", e.`kusp_num`, " от ", DATE_FORMAT(e.`kusp_date`, "%d.%m.%Y")),
          CONCAT(IF(e.`decision` = 1, "Отказной №", "У/д №"), e.`decision_number`, " от ", DATE_FORMAT(e.`decision_date`, "%d.%m.%Y"))
        ) as `decision`,
        IF(e.`emp_range` IS NULL, e.`employeer`, CONCAT(e.`employeer`, " (", e.`emp_range`, ")")) as `employeer`,
        e.`story`
      FROM
        `o_event` as e
      LEFT JOIN
        `l_relatives` as rel ON
          (
           rel.`from_obj` = e.`id` AND
           rel.`from_obj_type` = 2
          )
             OR
          (
           rel.`to_obj` = e.`id` AND
           rel.`to_obj_type` = 2
          )
      WHERE
        e.`ovd_id` = '.$ovd_id.' AND
        e.`ais` = 1 AND
        rel.`id` IS NULL
      ORDER BY
        e.`decision_date` DESC
    ') or die(mysql_error());
    $debt_type = 'У/д, не имеющие связей с объектами';
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
  <title>Отдел оперативно-разыскной информации</title>
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
require_once('head.php');
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
    <td align="center">-</td>
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
<?php elseif($type == 4) : ?>
<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="100px">Материал</th>
    <th width="150px">Сотрудник</th>
    <th colspan="2" width="550px">Фабула</th>
  </tr>
  <?php
  while ($result = mysql_fetch_assoc($query_d)) : ?>
  <tr>
    <td align="center">
      <?= $i++ ?>.
    </td>
    <td align="center">
      <a href="event.php?event_id=<?= $result["id"] ?>"><?= $result["decision"] ?></a>
    </td>
    <td>
      <?= $result["employeer"] ?>
    </td>
    <td>
      <?= $result["story"] ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
<?php endif; ?>
<?php
require_once('footer.php');

?>
</body>
</html>
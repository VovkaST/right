<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once(KERNEL.'connection.php');
$ais = is_ais(array(1, 2));

$yearList = null;
$q_years = mysql_query('
  SELECT
    DISTINCT YEAR(e.`decision_date`) as `year`
  FROM
    `o_event` as e
  WHERE
    e.`decision_date` AND
    e.`ais` = 1 AND
    e.`decision` = 1
  
  UNION

  SELECT
    '.date('Y').'
    
  ORDER BY `year` ASC
');
while($r_years = mysql_fetch_assoc($q_years)) {
  $yearList[] = $r_years['year'];
}
mysql_free_result($q_years);


if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

$query = '
  SELECT `id` FROM `l_dist_crimes` WHERE 
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
    (`number` = 1430 AND `date` = "2016-07-22") or
    (`number` = 16665 AND `date` = "2017-12-17") 
    
    
    
    
';
$result = mysql_query($query);
while($row = mysql_fetch_assoc($result)) {
  $wout[] = $row['id'];
}
mysql_free_result($result);

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
mysql_free_result($result);

if($year < 2018){
$query = '
  SELECT
    dc.`ovd`, COUNT(DISTINCT dc.`id`) as `reg`
  FROM
    `l_dist_crimes` as dc
  WHERE
    dc.`year` = '.$year.'
  GROUP BY
    dc.`ovd`
';}

elseif($year == 2018) {
$query = "
SELECT
  dc.`kod_ovd` as ovd, COUNT(DISTINCT dc.`id`) as `reg`
FROM
  `ic_dist_crimes_2018` as dc
WHERE
  dc.`year` = ".$year."
GROUP BY
  dc.`kod_ovd`
";}

else {
$query = "
SELECT
  dc.`kod_ovd` as ovd, COUNT(DISTINCT dc.`id`) as `reg`
FROM
  `ic_dist_crimes` as dc
WHERE
  dc.`year` = ".$year."
GROUP BY
  dc.`kod_ovd`
";}
$result = mysql_query($query) or die(mysql_error().'<pre>'.$query.'</pre>');
while ($row = mysql_fetch_array($result)) {
  $comb[$row['ovd']]['reg'] = $row['reg'];
}
mysql_free_result($result);


$query = '
  SELECT
    e.`ovd_id` as `ovd`, COUNT(DISTINCT e.`id`) as `added`
  FROM
    `o_event` as e
  WHERE
    e.`ais` = 1 AND
    e.`decision_date` BETWEEN STR_TO_DATE("01.01.'.$year.'", "%d.%m.%Y") AND STR_TO_DATE("31.12.'.$year.'", "%d.%m.%Y")
  GROUP BY
    e.`ovd_id`
';
$result = mysql_query($query) or die(mysql_error().'<pre>'.$query.'</pre>');
while ($row = mysql_fetch_array($result)) {
  $comb[$row['ovd']]['added'] = $row['added'];
}
mysql_free_result($result);

/*if($year < 2018){

$query = '
  SELECT
    dc.`ovd`, COUNT(DISTINCT dc.`id`) as `debt`
  FROM
    `l_dist_crimes` as dc FORCE INDEX (`PRIMARY`)
  LEFT JOIN
    `o_event` as ed USE INDEX (`debts`) ON
      ed.`decision_number` = dc.`number` AND
      YEAR(ed.`decision_date`) = YEAR(dc.`date`) AND
      ed.`decision` = 2 AND
      ed.`ais` = 1
  LEFT JOIN
    `spr_uk` as uk ON
    uk.`id_uk` = dc.`article_id`
  WHERE
    ed.`id` IS NULL
       
    AND dc.`id` NOT IN ('.implode(', ', $wout).')
 
  GROUP BY
    dc.`ovd`';
}*/
/*elseif($year == 2018) {
    $query = "
    select mo.kod_ovd as `ovd`, count(mo.id) as `debt` from ic_dist_crimes_2018 as mo
    left join 
    (
    SELECT DISTINCT
        e.`id`, e.`kusp_num`,
        DATE_FORMAT(e.`kusp_date`, \"%d.%m.%Y\") as `kusp_date`, 
        e.`decision_number`,
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
         YEAR(e.`decision_date`) = ".$year." and e.`ais` = 1
      GROUP BY
       e.`id`
      ORDER BY
        e.`kusp_date`) as ours
        on mo.UD_number = ours.decision_number and year(mo.data_vozbuzhdeniya) = year(str_to_date(ours.decision_date, '%d.%m.%Y')) and mo.kod_ovd = ours.ovd_id
        where  mo.`year` = ".$year." and ours.id is null
        group by mo.kod_ovd";
}*/
//else {
  if($year == 2018) $t = 'ic_dist_crimes_2018'; 
  else $t = 'ic_dist_crimes';
    $query = "
    select mo.kod_ovd as `ovd`, count(mo.id) as `debt` from ".$t." as mo
    left join 
    (
    SELECT DISTINCT
        e.`id`, e.`kusp_num`,
        DATE_FORMAT(e.`kusp_date`, \"%d.%m.%Y\") as `kusp_date`, 
        e.`decision_number`,
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
        where -- mo.`year` = ".$year." and 
        ours.id is null
        and mo.del is null -- del не = 1
        group by mo.kod_ovd";
//}
$result = mysql_query($query) or die(mysql_error().'<pre>'.$query.'</pre>');
while ($row = mysql_fetch_array($result)) {
  $comb[$row['ovd']]['debt'] = $row['debt'];
}
mysql_free_result($result);


$query = '
  SELECT 
    e.`ovd_id` as `ovd`, COUNT(DISTINCT e.`id`) as `debt_ic`
  FROM
    `o_event` as e
  LEFT JOIN
    `l_dist_crimes` as dc ON
      dc.`number` = e.`decision_number` AND
      dc.`year` = YEAR(e.`decision_date`) AND
      dc.`ovd` = e.`ovd_id`
  WHERE
    e.`decision_date` BETWEEN STR_TO_DATE("01.01.'.$year.'", "%d.%m.%Y") AND STR_TO_DATE("31.12.'.$year.'", "%d.%m.%Y") AND
    e.`decision` = 2 AND
    e.`ais` = 1 AND
    dc.`id` IS NULL
  GROUP BY
    e.`ovd_id`
';
$result = mysql_query($query) or die(mysql_error().'<pre>'.$query.'</pre>');
while ($row = mysql_fetch_array($result)) {
  $comb[$row['ovd']]['debt_ic'] = $row['debt_ic'];
}
mysql_free_result($result);


$q_objects = mysql_query('
  SELECT
    o.`id`, o.`object`,
    CASE
      WHEN o.`id` <> 2 THEN COUNT(DISTINCT objects.`obj_id1`)
      WHEN o.`id` = 2 THEN (SELECT COUNT(`id`) FROM `o_event` WHERE `ais` = '.$_SESSION['crime']['ais'].')
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
      r.`ais` = '.$_SESSION['crime']['ais'].'
    GROUP BY
      `obj_id1`, `type_id1`, `type_id2`
    ) as `objects` ON
    objects.`type_id1` = o.`id`
  GROUP BY
    o.`id`
') or die(mysql_error());

$per_days = mysql_query("
            select
            distinct
            count(*) as count
            from (
                    select
                    svt.to_obj as id_t,
                    t.number
                    from o_telephone as t 
                    join l_relatives as svt 
                    on t.id = svt.to_obj and svt.to_obj_type=6 and svt.from_obj_type = 2
                    join spr_relatives as _svt 
                    on _svt.to_obj = 6 and _svt.id = svt.`type`
                    where svt.ais = 1 and svt.create_date between if(DAYOFWEEK(now()) = 7 or DAYOFWEEK(now()) = 1, date_format(date_sub(now(), interval 3 day), '%Y-%m-%d'), date_format(date_sub(now(), interval 1 day), '%Y-%m-%d')) and date_format(now(), '%Y-%m-%d')
                    group by svt.to_obj
                    having count(svt.to_obj)>1
                    order by t.number
                ) as dd 
            left join l_relatives as sv 
            on sv.to_obj = dd.id_t and sv.to_obj_type = 6 and sv.ais = 1 and sv.from_obj_type = 2
            join o_event as k on k.id = sv.from_obj 
            join spr_ovd as ovd on k.ovd_id=ovd.id_ovd
            order by dd.number, k.kusp_date desc, k.decision_date desc
") or die(mysql_error());
$p_days = mysql_fetch_assoc($per_days);
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?= $ais ?></title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="/css/redmond/jquery-ui-1.10.4.custom.css">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="css/new_tmp.css">
  <link rel="stylesheet" href="/css/dist.css">
  <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
  <script type="text/javascript" src="/js/jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript">
    function row_show(elem) {
      var row = document.getElementById(elem);
      (row.style.display == 'none') ? (row.style.display = 'table-row') : (row.style.display = 'none');
    }
  </script>
  <script type="text/javascript" src="/js/jquery.procedures.js"></script>
</head>
<style>
</style>
<body>
<?php
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<center><span style="font-size: 1.2em;"><strong><?= $ais ?></strong></span></center>
<div class="user_message info">
      <h3>Внимание!</h3>
      <p>При выявлении ошибочно закодированных преступлений (дистанционное мошенничество) необходимо провести корректировку кодировки карточки "Форма 1.0"(ИЦ) и направить посредством СЭД соответствующее информационное письмо. </p>
      <span class="close_message">&times;</span>
    </div>
<hr color="#C6C6C6" size="0px"/>
<div id="yearList">
  <?php foreach ($yearList as $value) :?>
    <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?year='.$value ?>"><?= $value ?></a>
  <?php endforeach; ?>
</div>
<table rules="all" border="1" cellpadding="3" rules="all" class="total_indicators result_table">
  <tr class="table_head">
    <th width="140px">ОВД</th>
    <th width="50px">Всего<br/>зарег.<br/>(ИЦ)</th>
    <th width="80px">Всего<br/>введ.</th>
    <th width="100px">Не<br/>введ.</th>
 <!--   <th width="65px">Не заполн.<br/>реквизит<br/>ф.1.0</th> -->
  </tr>
  <?php
  
    $total = $count = $disclosed = $debt = $debt_ic = $sum_depts = 0;
    foreach ($comb as $ovd => $data) : ?>
    <tr>
      <td>
        <?= $data["ovd"] ?>
      </td>
      
      <td align="center">
        <?php if (!isset($data["reg"]) or $data["reg"] == 0) : ?>
          0
        <?php else : ?>
          <?= $data["reg"] ?><?php $total += $data["reg"] ?>
        <?php endif; ?>
      </td>
      
      <td align="center">
      <?php if (!isset($data["added"]) or $data["added"] == 0) : ?>
        <a href="<?= 'event.php?ovd_id='.$data["ovd"] ?>">0</a>
      <?php else : ?>
        <a href="<?= 'events_list.php?ovd_id='.$ovd ?>&year=<?= $year ?>"><?= $data["added"] ?></a><?php $count += $data["added"]; ?>
      <?php endif; ?>
      </td>
      
      <td align="center">
      <?php if (!isset($data["debt"]) or $data["debt"] == 0) : ?>
        0
      <?php else : ?>
        <a href="<?= 'events_dept.php?ovd_id='.$ovd ?>&type=1&year=<?= $year ?>"><?= $data["debt"] ?></a><?php $debt += $data["debt"]; ?>
      <?php endif; ?>
      </td>
 <!--     
      <td align="center">
  0
  пока убрал 
      <?php if (!isset($data["debt_ic"]) or $data["debt_ic"] == 0) : ?>
        0
      <?php else : ?>
        <a href="<?= 'events_dept.php?ovd_id='.$ovd ?>&type=2"><?= $data["debt_ic"] ?></a><?php $debt_ic += $data["debt_ic"]; ?>
      <?php endif; ?>
      -->
      
      </td>
      
    </tr>
    <?php endforeach; ?>
  <tr style="background: #F4F4F4;">
    <th>Итого</th>
    <th align="center"><?= $total ?></th>
    <th align="center"><?= $count ?></th>
    <th align="center"><?= $debt ?></th>
 <!--   <th align="center"><?= $debt_ic ?></th>  -->
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
            <?php switch ($_SESSION['crime']['ais']): // АИС "Мошенник"
              case 1:
                if (in_array($r_objects['id'], array(2, 6, 7, 8, 9))): ?>
                <span class="analysis_button" onclick="row_show('row_<?= $r_objects['id'] ?>')">Анализ...</span>
                <?php endif; ?>
              <?php break; ?>
            <?php endswitch; ?>
          </td>
          <?php if ($r_objects['obj_cnt'] > 0) : ?>
            <td align="center"><a href="objects_list.php?object=<?= $r_objects['id'] ?>"><?= $r_objects['obj_cnt'] ?></a></td>
          <?php else : ?>
            <td align="center"><?= $r_objects['obj_cnt'] ?></td>
          <?php endif; ?>
          <?php if ($r_objects['id'] == 2) : ?>
            <td align="center">-</td>
          <?php elseif ($r_objects['id'] == 14) : ?>
            <!td align="center"><a href="objects_list.php?object=<?= $r_objects['id'] ?>"><?= $p_days['count'] ?></a></td>
          <?php elseif ($r_objects['equal_cnt'] == 0) : ?>
            <td align="center"><?= $r_objects['equal_cnt'] ?></td>
          <?php else : ?>
            <td align="center"><a href="equal_list.php?object=<?= $r_objects['id'] ?>"><?= $r_objects['equal_cnt'] ?></a></td>
          <?php endif; ?>
        </tr>
        <?php switch ($_SESSION['crime']['ais']): // АИС "Мошенник"
          case 1: // формы анализа ?>
            <?php if ($r_objects['id'] == 2): ?>
              <tr id="row_<?= $r_objects['id'] ?>" style="background-color: rgb(229, 226, 193); display: none;">
                <td colspan="3">
                  <ul>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_event_marking.php">по способу совершения</a></li>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_event_crim_proc.php">по периоду ВУД</a></li>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_event_art_serv.php">по статьям УК, службам</a></li>
                  </ul>
                </td>
              </tr>
            <?php endif; ?>
            <?php if ($r_objects['id'] == 6): ?>
              <tr id="row_<?= $r_objects['id'] ?>" style="background-color: rgb(229, 226, 193); display: none;">
                <td colspan="3">
                  <ul>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_telephone_regions.php">по региональной принадлежности</a></li>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="telephones_list.php?owner=null">номера мошенников без владельца</a></li>
                  </ul>
                </td>
              </tr>
            <?php endif; ?>
            <?php if ($r_objects['id'] == 7): ?>
              <tr id="row_<?= $r_objects['id'] ?>" style="background-color: rgb(229, 226, 193); display: none;">
                <td colspan="3">
                  <ul>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_bank_account_requests.php">запросы по банковским счетам</a></li>
                  </ul>
                </td>
              </tr>
            <?php endif; ?>
            <?php if ($r_objects['id'] == 8): ?>
              <tr id="row_<?= $r_objects['id'] ?>" style="background-color: rgb(229, 226, 193); display: none;">
                <td colspan="3">
                  <ul>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_bank_cards_requests.php">запросы по банковским картам</a></li>
                  </ul>
                </td>
              </tr>
            <?php endif; ?>
            <?php if ($r_objects['id'] == 9): ?>
              <tr id="row_<?= $r_objects['id'] ?>" style="background-color: rgb(229, 226, 193); display: none;">
                <td colspan="3">
                  <ul>
                    <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_wallet_QIWI_requests.php">запросы по QIWI-кошелькам</a></li>
                  </ul>
                </td>
              </tr>
            <?php endif; ?>
          <?php break; ?>
        <?php endswitch; ?>
        
      <?php endwhile; ?>
     
      
      <tr style="background: #F4F4F4;">
        <th>Итого</th>
        <th align="center"><?= $obj_cnt ?></th>
        <th align="center"><?= $equal_cnt ?></th>
      </tr>
    </table>

<?php
require_once('footer.php');
/*echo '<pre>';
print_r($comb);
echo '</pre>';*/
?>
</body>
</html>
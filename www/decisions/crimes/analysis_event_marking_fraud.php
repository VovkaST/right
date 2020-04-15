<?php

if (pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) != 'analysis_event_marking.php') {
  header('Location: /error/404.php');
  die();
}

require_once(KERNEL.'connection.php');

$yearList = $period = $link = null;
$q_years = mysql_query('
  SELECT
    DISTINCT YEAR(e.`decision_date`) as `year`
  FROM
    `o_event` as e
  WHERE
    e.`decision_date` AND
    e.`ais` = 1 AND
    e.`decision` = 1
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

if (!empty($_GET['date_from'])) {
  if (date('Y', strtotime($_GET['date_from'])) != $year) unset($_GET['date_from']);
}

if (!empty($_GET['date_to'])) {
  if (date('Y', strtotime($_GET['date_to'])) != $year) unset($_GET['date_to']);
}

switch (true) {
  case (!empty($_GET['date_from']) and empty($_GET['date_to'])):
    $period = 'AND e.`decision_date` >= STR_TO_DATE("'.date('Y-m-d', strtotime($_GET['date_from'])).'", "%Y-%m-%d")';
    $link = '&date_from='.$_GET['date_from'];
    break;

  case (!empty($_GET['date_from']) and !empty($_GET['date_to'])):
    $period = 'AND (e.`decision_date` BETWEEN STR_TO_DATE("'.date('Y-m-d', strtotime($_GET['date_from'])).'", "%Y-%m-%d") AND STR_TO_DATE("'.date('Y-m-d', strtotime($_GET['date_to'])).'", "%Y-%m-%d"))';
    $link = '&date_from='.$_GET['date_from'].'&date_to='.$_GET['date_to'];
    break;
    
  case (empty($_GET['date_from']) and !empty($_GET['date_to'])):
    if (isset($_GET['date_from'])) unset($_GET['date_from']);
    if (isset($_GET['date_to'])) unset($_GET['date_to']);
    break;
}

$rows = array();
$query = mysql_query('
  SELECT
    ovd.`id_ovd`, ovd.`ovd`,
    COUNT(DISTINCT e.`id`) as `total`,
    COUNT(DISTINCT IF(m.`marking` = 1, e.`id`, NULL)) as `accident`,
    COUNT(DISTINCT IF(m.`marking` = 2, e.`id`, NULL)) as `prize`,
    COUNT(DISTINCT IF(m.`marking` = 4, e.`id`, NULL)) as `fine`,
    COUNT(DISTINCT IF(m.`marking` = 5, e.`id`, NULL)) as `SMS`,
    COUNT(DISTINCT IF((m.`marking` = 6) AND (e.`article_id` BETWEEN 134 AND 137), e.`id`, NULL)) as `virus_158`,
    COUNT(DISTINCT IF((m.`marking` = 6) AND (e.`article_id` NOT BETWEEN 134 AND 137), e.`id`, NULL)) as `virus_159`,
    COUNT(DISTINCT IF(m.`marking` = 7, e.`id`, NULL)) as `skim_device`,
    COUNT(DISTINCT IF(m.`marking` = 8, e.`id`, NULL)) as `medical`,
    COUNT(DISTINCT IF(m.`marking` = 9, e.`id`, NULL)) as `card`,
    COUNT(DISTINCT IF(m.`marking` = 10, e.`id`, NULL)) as `advert`,
    COUNT(DISTINCT IF(m.`marking` = 11, e.`id`, NULL)) as `i_shop`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `o_event` as e ON
      e.`ovd_id` = ovd.`id_ovd` AND
      e.`ais` = 1 AND
      YEAR(e.`decision_date`) = '.$year.'
      '.$period.'
    LEFT JOIN
      `l_object_marking` as m ON
        m.`object` = e.`id` AND
        m.`obj_type` = 2
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN(5,6,7,10,60,61,62,63,64,65,66)
  GROUP BY
    ovd.`id_ovd`
') or die(mysql_error());
while ($result = mysql_fetch_assoc($query)) {
  $rows[] = $result;
}
$total = $accident = $prize = $fine = $SMS = $virus_158 = $virus_159 = $skim_device = $medical = $card = $advert = $i_shop = 0;
$totalG = $accidentG = $prizeG = $fineG = $SMSG = $virus_158G = $virus_159G = $skim_deviceG = $medicalG = $cardG = $advertG = $i_shopG = null;
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?= $ais ?></title>
  <link rel="shortcut icon" href="/images/favicon.ico">
  <link rel="icon" href="/images/favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/new.css">
  <link rel="stylesheet" href="/css/redmond/jquery-ui-1.10.4.custom.css">
  <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
  <script type="text/javascript" src="/js/jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript">
    $(function(){
      $('.datepicker').datepicker();
    });
  </script>
</head>
<style>
</style>
<body>
<?php
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
</div>
<center><span style="font-size: 1.2em;"><strong>Преступления по способу совершения</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<div id="yearList">
  <?php foreach ($yearList as $value) :?>
    <a class="yearListStr" <?php if ($value == $year) echo 'id="resultYearBrowse"' ?> href="<?= $_SERVER["PHP_SELF"].'?year='.$value ?>"><?= $value ?></a>
  <?php endforeach; ?>
</div>
<div id="date_period" style="margin: 0 0 10px 20px;">
  <form method="GET" id="date_period_form">
  <input hidden="year" value="<?= $year ?>"/>
  В период с
  <input type="text" class="datepicker" name="date_from"<?php if (!empty($_GET['date_from'])) echo 'value="'.$_GET['date_from'].'"'?> autocomplete="off" onchange="document.getElementById('date_period_form').submit();"/>
  по
  <input type="text" class="datepicker" name="date_to"<?php if (!empty($_GET['date_to'])) echo 'value="'.$_GET['date_to'].'"'?> autocomplete="off" onchange="document.getElementById('date_period_form').submit();"/>
  </form>
</div>
<table rules="all" border="1" cellpadding="3" rules="all" class="result_table">
  <tr class="table_head">
    <th width="160px" rowspan="3">ОВД</th>
    <th width="40px" rowspan="3">Всего</th>
    <th colspan="11">из них:</th>
  </tr>
  <tr class="table_head">
    <th width="60px" rowspan="2">Несчаст.<br/>случай</th>
    <th width="60px" rowspan="2">Розыгр.<br/>призов</th>
    <th width="60px" rowspan="2">Штраф.<br/>операт.</th>
    <th width="60px" rowspan="2">SMS<br/>рассыл.</th>
    <th width="100px" colspan="2">Вред.ПО</th>
    <th width="60px" rowspan="2">Ским.<br/>устр-во</th>
    <th width="60px" rowspan="2">Мед.<br/>приборы</th>
    <th width="60px" rowspan="2">Пробл.с<br/>картой</th>
    <th width="60px" rowspan="2">Объявл.</th>
    <th width="60px" rowspan="2">Интерн.<br/>магазин</th>
  </tr>
  <tr class="table_head">
    <th>158</th>
    <th>159</th>
  </tr>
  <?php for ($i = 0; $i < count($rows); $i++) : ?>
    <tr>
      <?php if (count($totalG) == 4) : ?>
        <td align="right"><b>Итого город:</b></td>
      <?php else : ?>
        <td><?= $rows[$i]['ovd'] ?></td>
      <?php endif; ?>
      
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
          <b><?= array_sum($totalG) ?></b>
        <?php else : ?>
          <?php if ($rows[$i]['total']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>"><?= $rows[$i]['total'] ?></a>
            <?php $total += $rows[$i]['total']; ?>
          <?php else: ?>
            <?= $rows[$i]['total'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($accidentG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['accident']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=1"><?= $rows[$i]['accident'] ?></a>
            <?php $accident += $rows[$i]['accident']; ?>
          <?php else: ?>
            <?= $rows[$i]['accident'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($prizeG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['prize']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=2"><?= $rows[$i]['prize'] ?></a>
            <?php $prize += $rows[$i]['prize']; ?>
          <?php else: ?>
            <?= $rows[$i]['prize'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($fineG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['fine']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=4"><?= $rows[$i]['fine'] ?></a>
            <?php $fine += $rows[$i]['fine']; ?>
          <?php else: ?>
            <?= $rows[$i]['fine'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($SMSG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['SMS']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=5"><?= $rows[$i]['SMS'] ?></a>
            <?php $SMS += $rows[$i]['SMS']; ?>
          <?php else: ?>
            <?= $rows[$i]['SMS'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($virus_158G) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['virus_158']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=6&article=158"><?= $rows[$i]['virus_158'] ?></a>
            <?php $virus_158 += $rows[$i]['virus_158']; ?>
          <?php else: ?>
            <?= $rows[$i]['virus_158'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($virus_159G) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['virus_159']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=6&article=159"><?= $rows[$i]['virus_159'] ?></a>
            <?php $virus_159 += $rows[$i]['virus_159']; ?>
          <?php else: ?>
            <?= $rows[$i]['virus_159'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($skim_deviceG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['skim_device']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=7"><?= $rows[$i]['skim_device'] ?></a>
            <?php $skim_device += $rows[$i]['skim_device']; ?>
          <?php else: ?>
            <?= $rows[$i]['skim_device'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($medicalG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['medical']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=8"><?= $rows[$i]['medical'] ?></a>
            <?php $medical += $rows[$i]['medical']; ?>
          <?php else: ?>
            <?= $rows[$i]['medical'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($cardG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['card']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=9"><?= $rows[$i]['card'] ?></a>
            <?php $card += $rows[$i]['card']; ?>
          <?php else: ?>
            <?= $rows[$i]['card'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($advertG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['advert']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=10"><?= $rows[$i]['advert'] ?></a>
            <?php $advert += $rows[$i]['advert']; ?>
          <?php else: ?>
            <?= $rows[$i]['advert'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if (count($totalG) == 4) : ?>
         <b><?= array_sum($i_shopG) ?></b>
        <?php else : ?>
          <?php if($rows[$i]['i_shop']) : ?>
            <a href="events_list.php?ovd_id=<?= $rows[$i]['id_ovd'] ?>&year=<?= $year.$link ?>&marking_id=11"><?= $rows[$i]['i_shop'] ?></a>
            <?php $i_shop += $rows[$i]['i_shop']; ?>
          <?php else: ?>
            <?= $rows[$i]['i_shop'] ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
    </tr>
    <?php
      if (in_array($rows[$i]['id_ovd'], array(1,2,3,4))) {
        $totalG[] = $rows[$i]['total'];
        $accidentG[] = $rows[$i]['accident'];
        $prizeG[] = $rows[$i]['prize'];
        $fineG[] = $rows[$i]['fine'];
        $SMSG[] = $rows[$i]['SMS'];
        $virus_158G[] = $rows[$i]['virus_158'];
        $virus_159G[] = $rows[$i]['virus_159'];
        $skim_deviceG[] = $rows[$i]['skim_device'];
        $medicalG[] = $rows[$i]['medical'];
        $cardG[] = $rows[$i]['card'];
        $advertG[] = $rows[$i]['advert'];
        $i_shopG[] = $rows[$i]['i_shop'];
      } else {
        if (count($totalG) == 4) {
          $totalG = null;
          $i--;
        }
      }
    ?>
  <?php endfor; ?>
  <tr style="background: #F4F4F4;">
    <th>Итого</th>
    <th align="center"><?= $total ?></th>
    <th align="center"><?= $accident ?></th>
    <th align="center"><?= $prize ?></th>
    <th align="center"><?= $fine ?></th>
    <th align="center"><?= $SMS ?></th>
    <th align="center"><?= $virus_158 ?></th>
    <th align="center"><?= $virus_159 ?></th>
    <th align="center"><?= $skim_device ?></th>
    <th align="center"><?= $medical ?></th>
    <th align="center"><?= $card ?></th>
    <th align="center"><?= $advert ?></th>
    <th align="center"><?= $i_shop ?></th>
  </tr>
</table>
<?php
require_once('footer.php');
?>
</body>
</html>
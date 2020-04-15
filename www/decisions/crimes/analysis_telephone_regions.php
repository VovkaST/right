<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(1);

$yearList = $period = $link = null;
require_once(KERNEL.'connection.php');
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

$query = mysql_query('
  SELECT
    IFNULL(t.`region`, "не установлен") `region`, 
    t.`range`,
    COUNT(DISTINCT t.`id`) `total`,
    COUNT(DISTINCT IF(om.`marking` = 1, t.`id`, NULL)) as `accident`,
    COUNT(DISTINCT IF(om.`marking` = 2, t.`id`, NULL)) as `prize`,
    COUNT(DISTINCT IF(om.`marking` = 4, t.`id`, NULL)) as `fine`,
    COUNT(DISTINCT IF(om.`marking` = 5, t.`id`, NULL)) as `SMS`,
    COUNT(DISTINCT IF((om.`marking` = 6) AND (e.`article_id` BETWEEN 134 AND 137), t.`id`, NULL)) as `virus_158`,
    COUNT(DISTINCT IF((om.`marking` = 6) AND (e.`article_id` BETWEEN 138 AND 164), t.`id`, NULL)) as `virus_159`,
    COUNT(DISTINCT IF(om.`marking` = 7, t.`id`, NULL)) as `skim_device`,
    COUNT(DISTINCT IF(om.`marking` = 8, t.`id`, NULL)) as `medical`,
    COUNT(DISTINCT IF(om.`marking` = 9, t.`id`, NULL)) as `card`,
    COUNT(DISTINCT IF(om.`marking` = 10, t.`id`, NULL)) as `advert`,
    COUNT(DISTINCT IF(om.`marking` = 11, t.`id`, NULL)) as `i_shop`
  FROM
    (
     SELECT
       t.`id`, t.`number`, r.`region`, r.`id` as `range`
     FROM
       `o_telephone` as t
     LEFT JOIN
       `l_operators_ranges` as r ON
         r.`id` = t.`operator_range`
     ) as t
  LEFT JOIN
    `l_relatives` as rel ON
      rel.`to_obj` = t.`id` AND
      rel.`type` = 12 AND
      rel.`ais` = 1
    LEFT JOIN
      `o_event` as e ON
        e.`id` = rel.`from_obj` AND
        rel.`from_obj_type` = 2 AND
        YEAR(e.`decision_date`) = '.$year.' AND
        e.`ais` = 1
        '.$period.'
      LEFT JOIN
        `l_object_marking` as om ON
          om.`object` = e.`id` AND
          om.`obj_type` = 2
  WHERE
    e.`id` IS NOT NULL
  GROUP BY
    t.`region`
  ORDER BY
    `total` DESC
') or die(mysql_error());
$total = $accident = $prize = $fine = $SMS = $virus_158 = $virus_159 = $skim_device = $medical = $card = $advert = $i_shop = 0;
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
  <link rel="stylesheet" href="/css/redmond/jquery-ui-1.10.4.custom.css">
  <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
  <script type="text/javascript" src="/js/jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript">
    $(function(){
      $('.datepicker').datepicker();
    });
  </script>
</head>
<body>
<?php
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
</div>
<center><span style="font-size: 1.2em;"><strong>Региональная принадлежность абонентских номеров мошенников по способу совершения</strong></span></center>
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
    <th width="160px" rowspan="3">Регион</th>
    <th width="40px" rowspan="3">Всего<br/>тел.<br/>&darr;</th>
    <th colspan="11">из них связаны с событиями по способу совершения:</th>
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
  <?php while ($result = mysql_fetch_assoc($query)): ?>
    <tr>
      <td><?= $result['region'] ?></td>
      <td align="center">
        <?php if($result['total']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>"><?= $result['total'] ?></a>
          <?php $total += $result['total']; ?>
        <?php else: ?>
          <?= $result['total'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['accident']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=1"><?= $result['accident'] ?></a>
          <?php $accident += $result['accident']; ?>
        <?php else: ?>
          <?= $result['accident'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['prize']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=2"><?= $result['prize'] ?></a>
          <?php $prize += $result['prize']; ?>
        <?php else: ?>
          <?= $result['prize'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['fine']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=4"><?= $result['fine'] ?></a>
          <?php $fine += $result['fine']; ?>
        <?php else: ?>
          <?= $result['fine'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['SMS']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=5"><?= $result['SMS'] ?></a>
          <?php $SMS += $result['SMS']; ?>
        <?php else: ?>
          <?= $result['SMS'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['virus_158']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=6&article=158"><?= $result['virus_158'] ?></a>
          <?php $virus_158 += $result['virus_158']; ?>
        <?php else: ?>
          <?= $result['virus_158'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['virus_159']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=6&article=159"><?= $result['virus_159'] ?></a>
          <?php $virus_159 += $result['virus_159']; ?>
        <?php else: ?>
          <?= $result['virus_159'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['skim_device']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=7"><?= $result['skim_device'] ?></a>
          <?php $skim_device += $result['skim_device']; ?>
        <?php else: ?>
          <?= $result['skim_device'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['medical']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=8"><?= $result['medical'] ?></a>
          <?php $medical += $result['medical']; ?>
        <?php else: ?>
          <?= $result['medical'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['card']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=9"><?= $result['card'] ?></a>
          <?php $card += $result['card']; ?>
        <?php else: ?>
          <?= $result['card'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['advert']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=10"><?= $result['advert'] ?></a>
          <?php $advert += $result['advert']; ?>
        <?php else: ?>
          <?= $result['advert'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['i_shop']) : ?>
          <a href="telephones_list.php?region=<?= $result['range'] ?>&year=<?= $year.$link ?>&marking_id=11"><?= $result['i_shop'] ?></a>
          <?php $i_shop += $result['i_shop']; ?>
        <?php else: ?>
          <?= $result['i_shop'] ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
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
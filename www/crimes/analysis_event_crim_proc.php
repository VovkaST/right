<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    ovd.`id_ovd`,
    ovd.`ovd`,
    COUNT(DISTINCT e.`id`) as `total`,
    COUNT(DISTINCT IF(DATEDIFF(e.`decision_date`, e.`kusp_date`) <= 3, e.`id`, NULL) ) as `to_3`,
    COUNT(DISTINCT IF(DATEDIFF(e.`decision_date`, e.`kusp_date`) BETWEEN 4 AND 10, e.`id`, NULL) ) as `from_4_to_10`,
    COUNT(DISTINCT IF(DATEDIFF(e.`decision_date`, e.`kusp_date`) BETWEEN 11 AND 30, e.`id`, NULL) ) as `from_11_to_30`,
    COUNT(DISTINCT IF(DATEDIFF(e.`decision_date`, e.`kusp_date`) > 30, e.`id`, NULL) ) as `more_30`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `o_event` as e ON
      e.`ovd_id` = ovd.`id_ovd`
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN(5,6,7,10,60,61,62,63,64,65,66) AND
    e.`decision` = 2
  GROUP BY
    ovd.`id_ovd`
') or die(mysql_error());
// e.`article_id` IN(138,142,146,150,154,157,161) - первые части 159
// e.`article_id` IN(139,143,147,151,155,158,162) - вторые части 159
$total = $to_3 = $from_4_to_10 = $from_11_to_30 = $more_30 = 0;
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
</head>
<style>
</style>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
</div>
<center><span style="font-size: 1.2em;"><strong>Уголовные дела по периоду возбуждения</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<table rules="all" border="1" cellpadding="3" rules="all" class="result_table">
  <tr class="table_head">
    <th width="160px" rowspan="2">ОВД</th>
    <th width="60px" rowspan="2">Всего</th>
    <th colspan="4">из них (сут.):</th>
  </tr>
  <tr class="table_head">
    <th width="80px">до 3</th>
    <th width="80px">от 4 до 10</th>
    <th width="80px">от 11 до 30</th>
    <th width="80px">свыше 30</th>
  </tr>
  <?php while ($result = mysql_fetch_assoc($query)): ?>
    <tr>
      <td><?= $result['ovd'] ?></td>
      <td align="center">
        <?php if($result['total']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>"><?= $result['total'] ?></a>
          <?php $total += $result['total']; ?>
        <?php else: ?>
          <?= $result['total'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['to_3']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&differ=<4"><?= $result['to_3'] ?></a>
          <?php $to_3 += $result['to_3']; ?>
        <?php else: ?>
          <?= $result['to_3'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['from_4_to_10']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&differ=4,10"><?= $result['from_4_to_10'] ?></a>
          <?php $from_4_to_10 += $result['from_4_to_10']; ?>
        <?php else: ?>
          <?= $result['from_4_to_10'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['from_11_to_30']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&differ=11,30"><?= $result['from_11_to_30'] ?></a>
          <?php $from_11_to_30 += $result['from_11_to_30']; ?>
        <?php else: ?>
          <?= $result['from_11_to_30'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['more_30']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&differ=>30"><?= $result['more_30'] ?></a>
          <?php $more_30 += $result['more_30']; ?>
        <?php else: ?>
          <?= $result['more_30'] ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
  <tr style="background: #F4F4F4;">
    <th>Итого</th>
    <th align="center"><?= $total ?></th>
    <th align="center"><?= $to_3 ?></th>
    <th align="center"><?= $from_4_to_10 ?></th>
    <th align="center"><?= $from_11_to_30 ?></th>
    <th align="center"><?= $more_30 ?></th>
  </tr>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
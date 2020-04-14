<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    ovd.`id_ovd`,
    ovd.`ovd`,
    COUNT(e.`id`) as `total`,
    COUNT(IF(e.`marking_id` = 1, 1, NULL)) as `accident`,
    COUNT(IF(e.`marking_id` = 2, 1, NULL)) as `prize`,
    COUNT(IF(e.`marking_id` = 4, 1, NULL)) as `fine`,
    COUNT(IF(e.`marking_id` = 5, 1, NULL)) as `SMS`,
    COUNT(IF((e.`marking_id` = 6) AND (e.`article_id` BETWEEN 134 AND 137), 1, NULL)) as `virus_158`,
    COUNT(IF((e.`marking_id` = 6) AND (e.`article_id` NOT BETWEEN 134 AND 137), 1, NULL)) as `virus_159`,
    COUNT(IF(e.`marking_id` = 7, 1, NULL)) as `skim_device`,
    COUNT(IF(e.`marking_id` = 8, 1, NULL)) as `medical`,
    COUNT(IF(e.`marking_id` = 9, 1, NULL)) as `card`,
    COUNT(IF(e.`marking_id` = 10, 1, NULL)) as `advert`,
    COUNT(IF(e.`marking_id` = 11, 1, NULL)) as `i_shop`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `o_event` as e ON
      e.`ovd_id` = ovd.`id_ovd`
  WHERE
    ovd.`visuality` = 1
  GROUP BY
    e.`ovd_id`
  ORDER BY
    ovd.`id_ovd`
') or die(mysql_error());
$total = $accident = $prize = $fine = $SMS = $virus_158 = $virus_159 = $skim_device = $medical = $card = $advert = $i_shop = 0;
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
<center><span style="font-size: 1.2em;"><strong>Преступления по способу совершения</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
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
        <?php if($result['accident']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=1"><?= $result['accident'] ?></a>
          <?php $accident += $result['accident']; ?>
        <?php else: ?>
          <?= $result['accident'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['prize']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=2"><?= $result['prize'] ?></a>
          <?php $prize += $result['prize']; ?>
        <?php else: ?>
          <?= $result['prize'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['fine']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=4"><?= $result['fine'] ?></a>
          <?php $fine += $result['fine']; ?>
        <?php else: ?>
          <?= $result['fine'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['SMS']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=5"><?= $result['SMS'] ?></a>
          <?php $SMS += $result['SMS']; ?>
        <?php else: ?>
          <?= $result['SMS'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['virus_158']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=6&article=158"><?= $result['virus_158'] ?></a>
          <?php $virus_158 += $result['virus_158']; ?>
        <?php else: ?>
          <?= $result['virus_158'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['virus_159']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=6&article=159"><?= $result['virus_159'] ?></a>
          <?php $virus_159 += $result['virus_159']; ?>
        <?php else: ?>
          <?= $result['virus_159'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['skim_device']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=7"><?= $result['skim_device'] ?></a>
          <?php $skim_device += $result['skim_device']; ?>
        <?php else: ?>
          <?= $result['skim_device'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['medical']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=8"><?= $result['medical'] ?></a>
          <?php $medical += $result['medical']; ?>
        <?php else: ?>
          <?= $result['medical'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['card']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=9"><?= $result['card'] ?></a>
          <?php $card += $result['card']; ?>
        <?php else: ?>
          <?= $result['card'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['advert']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=10"><?= $result['advert'] ?></a>
          <?php $advert += $result['advert']; ?>
        <?php else: ?>
          <?= $result['advert'] ?>
        <?php endif; ?>
      </td>
      <td align="center">
        <?php if($result['i_shop']) : ?>
          <a href="events_list.php?ovd_id=<?= $result['id_ovd'] ?>&marking_id=11"><?= $result['i_shop'] ?></a>
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
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
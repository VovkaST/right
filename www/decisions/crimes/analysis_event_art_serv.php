<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(1);

require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    ovd.`id_ovd`,
    ovd.`ovd`,
    COUNT(e.`id`) as `total`,
    COUNT( DISTINCT IF(uk.`st` LIKE "158%", e.`id`, NULL) ) as `tot_a_158`,
    COUNT( DISTINCT IF(e.`article_id` = 134, e.`id`, NULL) ) as `tot_a_158_doz`,
    COUNT( DISTINCT IF(e.`article_id` BETWEEN 135 AND 137, e.`id`, NULL) ) as `tot_a_158_sled`,
    COUNT( DISTINCT IF(uk.`st` NOT LIKE "158%", e.`id`, NULL) ) as `tot_a_159`,
    COUNT( DISTINCT IF(uk.`st` NOT LIKE "158%" AND uk.`st` LIKE "%ч.1", e.`id`, NULL) ) as `tot_a_159_doz`,
    COUNT( DISTINCT IF(uk.`st` NOT LIKE "158%" AND uk.`st` NOT LIKE "%ч.1", e.`id`, NULL) ) as `tot_a_159_sled`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NOT NULL, e.`id`, NULL) ) as `disclosed`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NOT NULL AND e.`article_id` = 134, e.`id`, NULL) ) as `disclosed_158_doz`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NOT NULL AND e.`article_id` BETWEEN 135 AND 137, e.`id`, NULL) ) as `disclosed_158_sled`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NOT NULL AND uk.`st` NOT LIKE "158%" AND uk.`st` LIKE "%ч.1", e.`id`, NULL) ) as `disclosed_159_doz`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NOT NULL AND uk.`st` NOT LIKE "158%" AND uk.`st` NOT LIKE "%ч.1", e.`id`, NULL) ) as `disclosed_159_sled`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NULL AND e.`decision_date` > DATE_SUB(current_date, INTERVAL 1 MONTH) AND uk.`st` LIKE "%ч.1" AND e.`decision` = 2, e.`id`, NULL) ) as `proc_doz`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NULL AND e.`decision_date` > DATE_SUB(current_date, INTERVAL 1 MONTH) AND e.`article_id` = 134 AND e.`decision` = 2, e.`id`, NULL) ) as `proc_doz_158`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NULL AND e.`decision_date` > DATE_SUB(current_date, INTERVAL 1 MONTH) AND uk.`st` NOT LIKE "158%" AND uk.`st` LIKE "%ч.1" AND e.`decision` = 2, e.`id`, NULL) ) as `proc_doz_159`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NULL AND e.`decision_date` > DATE_SUB(current_date, INTERVAL 2 MONTH) AND uk.`st` NOT LIKE "%ч.1" AND e.`decision` = 2, e.`id`, NULL) ) as `proc_sled`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NULL AND e.`decision_date` > DATE_SUB(current_date, INTERVAL 2 MONTH) AND e.`article_id` BETWEEN 135 AND 137 AND e.`decision` = 2, e.`id`, NULL) ) as `proc_sled_158`,
    COUNT( DISTINCT IF(e.`disclose_date` IS NULL AND e.`decision_date` > DATE_SUB(current_date, INTERVAL 2 MONTH) AND uk.`st` NOT LIKE "158%" AND uk.`st` NOT LIKE "%ч.1" AND e.`decision` = 2, e.`id`, NULL) ) as `proc_sled_159`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `o_event` as e ON
      e.`ovd_id` = ovd.`id_ovd` AND
      e.`ais` = 1
  LEFT JOIN
    `spr_uk` as uk ON
      uk.`id_uk` = e.`article_id`
  WHERE
    ovd.`visuality` = 1
  GROUP BY
    e.`ovd_id`
  ORDER BY
    ovd.`id_ovd`
') or die(mysql_error());
$total = $tot_a_158 = $tot_a_158_doz = $tot_a_158_sled = $tot_a_159 = $tot_a_159_doz = $tot_a_159_sled = $disclosed = $disclosed_158_doz = $disclosed_158_sled = $disclosed_159_doz = $disclosed_159_sled = $proc_doz = $proc_doz_158 = $proc_doz_159 = $proc_sled = $proc_sled_158 = $proc_sled_159 = 0;
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
<center><span style="font-size: 1.2em;"><strong>Преступления по видам и службам</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<table rules="all" border="1" cellpadding="3" rules="all" class="result_table" style="margin-left: -17%; width: 134%">
  <tr class="table_head">
    <th width="160px" rowspan="2">ОВД</th>
    <th width="40px" rowspan="2">Всего<br/>зарег.</th>
    <th colspan="3">ст.158 УК РФ</th>
    <th colspan="3">ст.159 УК РФ</th>
    <th rowspan="2">Раскр.</th>
    <th colspan="4">из них:</th>
    <th colspan="3">В пр-ве (дозн.)</th>
    <th colspan="3">В пр-ве (след.)</th>
  </tr>
  <tr class="table_head">
    <th width="60px">Всего</th>
    <th width="60px">Дозн.</th>
    <th width="60px">След.</th>
    <th width="60px">Всего</th>
    <th width="60px">Дозн.</th>
    <th width="60px">След.</th>
    <th width="60px">158<br/>(дозн.)</th>
    <th width="60px">158<br/>(след.)</th>
    <th width="60px">159<br/>(дозн.)</th>
    <th width="60px">159<br/>(след.)</th>
    <th width="60px">Всего</th>
    <th width="60px">158</th>
    <th width="60px">159</th>
    <th width="60px">Всего</th>
    <th width="60px">158</th>
    <th width="60px">159</th>
  </tr>
  <?php while ($result = mysql_fetch_assoc($query)): ?>
    <tr>
      <td><?= $result["ovd"] ?></td>
      
      <?php if($result['total']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>"><?= $result["total"] ?></a></td>
        <?php $total += $result["total"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["total"] ?></td>
      <?php endif; ?>
      
      <?php if($result['tot_a_158']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&article=158"><?= $result["tot_a_158"] ?></a></td>
        <?php $tot_a_158 += $result["tot_a_158"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["tot_a_158"] ?></td>
      <?php endif; ?>
      
      <?php if($result['tot_a_158_doz']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&service=5&article=158"><?= $result["tot_a_158_doz"] ?></a></td>
        <?php $tot_a_158_doz += $result["tot_a_158_doz"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["tot_a_158_doz"] ?></td>
      <?php endif; ?>
      
      <?php if($result['tot_a_158_sled']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&service=6&article=158"><?= $result["tot_a_158_sled"] ?></a></td>
        <?php $tot_a_158_sled += $result["tot_a_158_sled"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["tot_a_159_sled"] ?></td>
      <?php endif; ?>
      
      <?php if($result['tot_a_159']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&article=159"><?= $result["tot_a_159"] ?></a></td>
        <?php $tot_a_159 += $result["tot_a_159"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["tot_a_159"] ?></td>
      <?php endif; ?>
      
      <?php if($result['tot_a_159_doz']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&service=5&article=159"><?= $result["tot_a_159_doz"] ?></a></td>
        <?php $tot_a_159_doz += $result["tot_a_159_doz"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["tot_a_159_doz"] ?></td>
      <?php endif; ?>
      
      <?php if($result['tot_a_159_sled']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&service=6&article=159"><?= $result["tot_a_159_sled"] ?></a></td>
        <?php $tot_a_159_sled += $result["tot_a_159_sled"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["tot_a_159_sled"] ?></td>
      <?php endif; ?>
      
      <?php if($result['disclosed']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&disclosed=true"><?= $result["disclosed"] ?></a></td>
        <?php $disclosed += $result["disclosed"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["disclosed"] ?></td>
      <?php endif; ?>
      
      <?php if($result['disclosed_158_doz']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&disclosed=true&service=5&article=158"><?= $result["disclosed_158_doz"] ?></a></td>
        <?php $disclosed_158_doz += $result["disclosed_158_doz"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["disclosed_158_doz"] ?></td>
      <?php endif; ?>
      
      <?php if($result['disclosed_158_sled']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&disclosed=true&service=6&article=158"><?= $result["disclosed_158_sled"] ?></a></td>
        <?php $disclosed_158_sled += $result["disclosed_158_sled"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["disclosed_158_sled"] ?></td>
      <?php endif; ?>
      
      <?php if($result['disclosed_159_doz']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&disclosed=true&service=5&article=159"><?= $result["disclosed_159_doz"] ?></a></td>
        <?php $disclosed_159_doz += $result["disclosed_159_doz"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["disclosed_159_doz"] ?></td>
      <?php endif; ?>
      
      <?php if($result['disclosed_159_sled']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&disclosed=true&service=6&article=159"><?= $result["disclosed_159_sled"] ?></a></td>
        <?php $disclosed_159_sled += $result["disclosed_159_sled"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["disclosed_159_sled"] ?></td>
      <?php endif; ?>
      
      <?php if($result['proc_doz']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&procedure=true&service=5"><?= $result["proc_doz"] ?></a></td>
        <?php $proc_doz += $result["proc_doz"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["proc_doz"] ?></td>
      <?php endif; ?>
      
      <?php if($result['proc_doz_158']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&procedure=true&service=5&article=158"><?= $result["proc_doz_158"] ?></a></td>
        <?php $proc_doz_158 += $result["proc_doz_158"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["proc_doz_158"] ?></td>
      <?php endif; ?>
      
      <?php if($result['proc_doz_159']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&procedure=true&service=5&article=159"><?= $result["proc_doz_159"] ?></a></td>
        <?php $proc_doz_159 += $result["proc_doz_159"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["proc_doz_159"] ?></td>
      <?php endif; ?>
      
      <?php if($result['proc_sled']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&procedure=true&service=6"><?= $result["proc_sled"] ?></a></td>
        <?php $proc_sled += $result["proc_sled"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["proc_sled"] ?></td>
      <?php endif; ?>
      
      <?php if($result['proc_sled_158']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&procedure=true&service=6&article=158"><?= $result["proc_sled_158"] ?></a></td>
        <?php $proc_sled_158 += $result["proc_sled_158"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["proc_sled_158"] ?></td>
      <?php endif; ?>
      
      <?php if($result['proc_sled_159']) : ?>
        <td align="center"><a href="events_list.php?ovd_id=<?= $result["id_ovd"] ?>&procedure=true&service=6&article=159"><?= $result["proc_sled_159"] ?></a></td>
        <?php $proc_sled_159 += $result["proc_sled_159"]; ?>
      <?php else: ?>
        <td align="center"><?= $result["proc_sled_159"] ?></td>
      <?php endif; ?>
      
    </tr>
  <?php endwhile; ?>
  <tr style="background: #F4F4F4;">
    <th>Итого</th>
    <th align="center"><?= $total ?></th>
    <th align="center"><?= $tot_a_158 ?></th>
    <th align="center"><?= $tot_a_158_doz ?></th>
    <th align="center"><?= $tot_a_158_sled ?></th>
    <th align="center"><?= $tot_a_159 ?></th>
    <th align="center"><?= $tot_a_159_doz ?></th>
    <th align="center"><?= $tot_a_159_sled ?></th>
    <th align="center"><?= $disclosed ?></th>
    <th align="center"><?= $disclosed_158_doz ?></th>
    <th align="center"><?= $disclosed_158_sled ?></th>
    <th align="center"><?= $disclosed_159_doz ?></th>
    <th align="center"><?= $disclosed_159_sled ?></th>
    <th align="center"><?= $proc_doz ?></th>
    <th align="center"><?= $proc_doz_158 ?></th>
    <th align="center"><?= $proc_doz_159 ?></th>
    <th align="center"><?= $proc_sled ?></th>
    <th align="center"><?= $proc_sled_158 ?></th>
    <th align="center"><?= $proc_sled_159 ?></th>
  </tr>
</table>
<?php
require_once('footer.php');
?>
</body>
</html>
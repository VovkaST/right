<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once(KERNEL.'connection.php');
$q_distr = mysql_query('
  SELECT
    ovd.`id_ovd`,
    ovd.`ovd`,
    count(DISTINCT e.`id`) as `cnt_event`,
    count(DISTINCT (IF(e.`disclose_date` IS NOT NULL, e.`id`, NULL))) as `cnt_disclosed`,
    IFNULL(l_depts.`debt`, "-") as `debt`,
    IFNULL(ic_depts.`debt_ic`, "-") as `ic_depts`,
    IFNULL(sum_depts.`sum_debt`, "-") as `sum_depts`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `o_event` as e ON
      e.`ovd_id` = ovd.`id_ovd`
  LEFT JOIN
    (
      SELECT
        ovd.`id_ovd`,
        COUNT(dc.`id`) as `debt`
      FROM
        `spr_ovd` as ovd
      JOIN
        `l_dist_crimes` as dc ON
          dc.`ovd` = ovd.`ibd_code`
        LEFT JOIN
          `o_event` as ed ON
            ed.`decision_number` = dc.`number` AND
            YEAR(ed.`decision_date`) = YEAR(dc.`date`) AND
            ed.`decision` = 2
      WHERE
        ed.`id` IS NULL
      GROUP BY
        ovd.`id_ovd`
    ) as l_depts ON
      l_depts.`id_ovd` = ovd.`id_ovd`
  LEFT JOIN
    (
     SELECT
       ovd.`id_ovd`,
       COUNT(DISTINCT e.`decision_number`) as `debt_ic`
     FROM
       `spr_ovd` as ovd
     JOIN
       `o_event` as e ON
         e.`ovd_id` = ovd.`id_ovd`
       LEFT JOIN
         `l_dist_crimes` as dc ON
           dc.`number` = e.`decision_number` AND
           YEAR(e.`decision_date`) = YEAR(dc.`date`)
     WHERE
       dc.`number` IS NULL AND
       e.`decision` = 2
     GROUP BY
       ovd.`ibd_code`
    ) as `ic_depts` ON 
      ic_depts.`id_ovd` = ovd.`id_ovd`
  LEFT JOIN
    (
     SELECT
       ovd.`id_ovd`,
       COUNT(s.`id`) as `sum_debt`
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
     WHERE
       e.`id` IS NULL
     GROUP BY
       ovd.`id_ovd`
    ) as `sum_depts` ON
     `sum_depts`.`id_ovd` = ovd.`id_ovd`
  WHERE
    ovd.`visuality` = 1 AND
    ovd.`id_ovd` NOT IN(5,6,7,10,60,61,62,63,64,65,66)
  GROUP BY
    ovd.`id_ovd`') or die(mysql_error());

$q_objects = mysql_query('
  SELECT
    o.`id`,
    o.`object`,
    CASE o.`id`
      WHEN (1) THEN (SELECT COUNT(`id`) FROM `o_lico`)
      WHEN (2) THEN (SELECT COUNT(`id`) FROM `o_event`)
      WHEN (3) THEN (SELECT COUNT(`id`) FROM `o_address`)
      WHEN (4) THEN (SELECT COUNT(`id`) FROM `o_documents`)
      WHEN (5) THEN (SELECT COUNT(`id`) FROM `o_mobile_device`)
      WHEN (6) THEN (SELECT COUNT(`id`) FROM `o_telephone`)
      WHEN (7) THEN (SELECT COUNT(`id`) FROM `o_bank_account`)
      WHEN (8) THEN (SELECT COUNT(`id`) FROM `o_bank_card`)
      WHEN (9) THEN (SELECT COUNT(`id`) FROM `o_wallet`)
      WHEN (10) THEN (SELECT COUNT(`id`) FROM `o_mail`)
    END as obj_cnt,
    COUNT(DISTINCT `equals`.`obj_id1`) equal_cnt
  FROM
    `spr_objects` as o
  LEFT JOIN
    (
      SELECT
        r.`id` as `rel_id`,
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
            WHEN o.`id` = r.`from_obj_type` THEN r.`to_obj`
            WHEN o.`id` = r.`to_obj_type` THEN r.`from_obj`
          END
        ) as cnt
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
        o.`id` <> 2
      GROUP BY
        `obj_id1`, `type_id1`, `type_id2`
      HAVING
        cnt > 1
      ORDER BY
        `obj_id1`
    ) as `equals` ON
      `equals`.`type_id1` = o.`id`
  GROUP BY
    o.`id`,
    `equals`.`type_id1`
  ORDER BY
    o.`id`
') or die(mysql_error());
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
  <link rel="stylesheet" href="css/new_tmp.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <script type="text/javascript">
    function row_show(elem) {
      var row = document.getElementById(elem);
      (row.style.display == 'none') ? (row.style.display = 'table-row') : (row.style.display = 'none');
    }
  </script>
</head>
<style>
</style>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<center><span style="font-size: 1.2em;"><strong>АИС "Мошенник"</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<div style="border: 1px solid rgb(232, 101, 101); background-color: rgb(245, 206, 121); color: rgb(100, 90, 67); margin: 10px 0; padding: 10px; text-align: center; font-size: 10pt;">
С 12.10.2015 доступ к АИС "Мошенник" осуществляется <b>только</b> для авторизованных пользователей (через ИБД-Р).<br/>
По вопросу регистрации в системе ИБД-Р обращаться в ....
</div>
<div style="margin: 0 auto; height: 1100px;">
  <table rules="all" border="1" cellpadding="3" rules="all" class="r_distr_table result_table" style="float: left; margin-right: 10px;" >
    <tr class="table_head">
      <th width="170px">ОВД</th>
      <th width="50px">Всего<br/>зарег.</th>
      <th width="65px">Раскр.</th>
      <th width="65px">Не<br/>введено</th>
      <th width="65px">Не заполн.<br/>реквизит<br/>ф.1.0</th>
      <th width="65px">Не введ.<br/>из сводки</th>
    </tr>
    <?php
    if (mysql_num_rows($q_distr)) :
      $count = $disclosed = $debt = $ic_depts = $sum_depts = 0;
      while ($r_distr = mysql_fetch_assoc($q_distr)) : ?>
      <tr>
        <td>
          <?= $r_distr["ovd"] ?>
        </td>
        <td align="center">
        <?php if ($r_distr["cnt_event"] == 0) : ?>
          <a href="<?= 'event.php?ovd_id='.$r_distr["id_ovd"] ?>"><?= $r_distr["cnt_event"] ?></a>
        <?php else : ?>
          <a href="<?= 'events_list.php?ovd_id='.$r_distr["id_ovd"] ?>"><?= $r_distr["cnt_event"] ?></a><?php $count += $r_distr["cnt_event"]; ?>
        <?php endif; ?>
        </td>
        
        <td align="center">
        <?php if ($r_distr["cnt_disclosed"] == 0) : ?>
          <?= $r_distr["cnt_disclosed"] ?>
        <?php else : ?>
          <a href="<?= 'events_list.php?ovd_id='.$r_distr["id_ovd"] ?>&disclosed=true"><?= $r_distr["cnt_disclosed"] ?></a><?php $disclosed += $r_distr["cnt_disclosed"]; ?>
        <?php endif; ?>
        </td>
        
        <td align="center">
        <?php if ($r_distr["debt"] == 0) : ?>
          <?= $r_distr["debt"] ?>
        <?php else : ?>
          <a href="<?= 'events_dept.php?ovd_id='.$r_distr["id_ovd"] ?>&type=1"><?= $r_distr["debt"] ?></a><?php $debt += $r_distr["debt"]; ?>
        <?php endif; ?>
        </td>
        
        <td align="center">
        <?php if ($r_distr["ic_depts"] == 0) : ?>
          <?= $r_distr["ic_depts"] ?>
        <?php else : ?>
          <a href="<?= 'events_dept.php?ovd_id='.$r_distr["id_ovd"] ?>&type=2"><?= $r_distr["ic_depts"] ?></a><?php $ic_depts += $r_distr["ic_depts"]; ?>
        <?php endif; ?>
        </td>
        
        <td align="center">
        <?php if ($r_distr["sum_depts"] == 0) : ?>
          <?= $r_distr["sum_depts"] ?>
        <?php else : ?>
          <a href="<?= 'events_dept.php?ovd_id='.$r_distr["id_ovd"] ?>&type=3"><?= $r_distr["sum_depts"] ?></a><?php $sum_depts += $r_distr["sum_depts"]; ?>
        <?php endif; ?>
        </td>
      </tr>
      <?php endwhile;
    endif; ?>
    <tr style="background: #F4F4F4;">
      <th>Итого</th>
      <th align="center"><?= $count ?></th>
      <th align="center"><?= $disclosed ?></th>
      <th align="center"><?= $debt ?></th>
      <th align="center"><?= $ic_depts ?></th>
      <th align="center"><?= $sum_depts ?></th>
    </tr>
  </table>
  
  <table rules="all" border="1" cellpadding="3" rules="all" style="float: left;" class="result_table">
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
          <?php if (in_array($r_objects['id'], array(2, 6, 9))): ?>
            <span class="analysis_button" onclick="row_show('row_<?= $r_objects['id'] ?>')">Анализ...</span>
          <?php endif; ?>
        </td>
        <?php if ($r_objects['obj_cnt'] > 0) : ?>
          <td align="center"><a href="objects_list.php?object=<?= $r_objects['id'] ?>"><?= $r_objects['obj_cnt'] ?></a></td>
        <?php else : ?>
          <td align="center"><?= $r_objects['obj_cnt'] ?></td>
        <?php endif; ?>
        <?php if ($r_objects['id'] == 2) : ?>
          <td align="center">-</td>
        <?php elseif ($r_objects['equal_cnt'] == 0) : ?>
          <td align="center"><?= $r_objects['equal_cnt'] ?></td>
        <?php else : ?>
          <td align="center"><a href="equal_list.php?object=<?= $r_objects['id'] ?>"><?= $r_objects['equal_cnt'] ?></a></td>
        <?php endif; ?>
      </tr>
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
      <?php if ($r_objects['id'] == 9): ?>
        <tr id="row_<?= $r_objects['id'] ?>" style="background-color: rgb(229, 226, 193); display: none;">
          <td colspan="3">
            <ul>
              <li>&nbsp;&nbsp;&nbsp;&mdash; <a href="analysis_wallet_QIWI_requests.php">запросы по QIWI-кошелькам</a></li>
            </ul>
          </td>
        </tr>
      <?php endif; ?>
    <?php endwhile; ?>
    <tr style="background: #F4F4F4;">
      <th>Итого</th>
      <th align="center"><?= $obj_cnt ?></th>
      <th align="center"><?= $equal_cnt ?></th>
    </tr>
  </table>
  
</div>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
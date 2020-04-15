<?php
if (pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) != 'index.php')
  header('Location: /error/404.php');

$yearList = WeaponAccountYearsList();
if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}

for ($n = 0; $n <= 13; $n++) {
  $total[$n] = 0;
}


 $query = '
  SELECT
    ovd.`id_ovd` as `id`, ovd.`ovd`,
    COUNT(DISTINCT wa.`id`) as `total_qnt`,
    COUNT(DISTINCT IF(wa.`purpose_placing` = 1, wa.`id`, NULL)) as `util_qnt`,
    COUNT(DISTINCT IF(wa.`purpose_placing` = 2, wa.`id`, NULL)) as `stor_qnt`,
    COUNT(DISTINCT IF(wa.`purpose_placing` = 3, wa.`id`, NULL)) as `admi_qnt`,
    COUNT(DISTINCT IF(wa.`purpose_placing` = 4, wa.`id`, NULL)) as `crim_qnt`,
    COUNT(DISTINCT IF(wa.`purpose_placing` = 5, wa.`id`, NULL)) as `kusp_qnt`,
    COUNT(DISTINCT IF(wa.`purpose_placing` = 6, wa.`id`, NULL)) as `deat_qnt`,
    
    COUNT(DISTINCT w.`id`) as `total_wpn_qnt`,
    COUNT(DISTINCT IF(wd.`decision` = 1, w.`id`, NULL)) as `retu_wpn_qnt`,
    COUNT(DISTINCT IF(wd.`decision` = 2, w.`id`, NULL)) as `empl_wpn_qnt`,
    COUNT(DISTINCT IF(wd.`decision` = 3, w.`id`, NULL)) as `stor_wpn_qnt`,
    COUNT(DISTINCT IF(wd.`decision` = 4, w.`id`, NULL)) as `util_wpn_qnt`,
    COUNT(DISTINCT IF(wd.`decision` = 5, w.`id`, NULL)) as `rets_wpn_qnt`,
    COUNT(DISTINCT IF(wd.`decision` IS NULL, w.`id`, NULL)) as `wode_wpn_qnt`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `l_weapons_account` as wa ON
      wa.`ovd` = ovd.`id_ovd` 
      AND wa.`deleted` = 0
      AND YEAR(wa.`reg_date`) = '.$year.'
    LEFT JOIN
      `l_weapons` as w ON
        w.`weapons_account` = wa.`id` AND
        w.`deleted` = 0
      LEFT JOIN
        `l_weapons_decision` as wd ON
          wd.`id` = w.`last_decision` AND
          wd.`deleted` = 0
  WHERE
    ovd.`id_ovd` NOT IN (5,6,7,9,10,13,14,30,37,38,41,59,60,61,62,63,64,65,66,67,68)
    '.((!empty($where)) ? 'AND '.implode(' AND ', $where) : null).'
  GROUP BY
    ovd.`id_ovd`
';
require(KERNEL.'connect.php');

if (!$result = $db->query($query))
  die($db->error.' .Query string: '.$query);

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');


?>
<div class="header_row"><?= $_SESSION['user']['ovd'] ?></div>
<div class="actions_block">
  <ul class="actions_list">
    <li class="item">Просмотр:</li>
    <li class="item"><div class="block current">Кратко</div></li>
    <li class="item"><div class="block"><a href="index.php?volume=full">Расширенно</a></div></li>
    <li class="item"><div class="block"><a href="index.php?volume=kusp">КУСП/УД</a></div></li>
    <?php if (!empty($_SESSION['user']['admin'])) : ?>
      <li class="item"><div class="block"><a href="index.php?volume=using">Использование сервиса</a></div></li>
    <?php endif; ?>
  </ul>
</div>
<div class="input_row">
  <form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>">
    <div class="field_box noSidePadding">
      <span class="field_name">Период: </span>
      <?= my_date_field('dateB', ((!empty($kusp)) ? $kusp->get_date() : null)) ?>
    </div>
    <div class="field_box noSidePadding">
      <span class="field_name"> &mdash; </span>
      <?= my_date_field('dateE', ((!empty($kusp)) ? $kusp->get_date() : null)) ?>
    </div>
  </form>
</div>
<div class="actions_list">
  <?php if ($_SESSION['user']['admin'] == 1 or $_SESSION['user']['ovd_id'] != 59) : ?>
    <div class="add_box">
      <a href="receipt.php" method="add">Добавить</a>
    </div>
    <?php if (!empty($_SESSION['WeaponAccount']['object'])) : ?>
      <div class="continue_box">
        <a href="receipt.php" method="continue">Продолжить ввод</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<table rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable">
  <tr class="table_head">
    <th rowspan="2">ОВД</th>
    <th rowspan="2" title="Всего квитанций">Всего</th>
    <th colspan="6">Цели помещения</th>
    <th colspan="7">Единицы вооружения</th>
  </tr>
  <tr class="table_head">
    <th width="40px" title="Цель помещения - На утилизацию">Утил.</th>
    <th width="40px" title="Цель помещения - На хранение">Хран.</th>
    <th width="40px" title="Цель помещения - Адм.правонарушение">Адм.</th>
    <th width="40px" title="Цель помещения - По уголовному делу">У/д</th>
    <th width="40px" title="Цель помещения - По материалам проверки (КУСП)">КУСП</th>
    <th width="40px" title="Цель помещения - Смерть владельца">Смерть</th>
    
    <th width="40px" title="Всего принято единиц вооружения">Всего</th>
    <th width="40px" title="Возвращено владельцу">Возвр.</th>
    <th width="40px" title="Затребовано сотрудником">Сотр.</th>
    <th width="40px" title="Направлено на склад УМВД (Хранение)">Склад</th>
    <th width="40px" title="Направлено на склад УМВД (Утилизация)">Утил.</th>
    <th width="40px" title="Возвращено в КХО">Возвр.<br />в КХО</th>
    <th width="40px" title="Не принято решений">Без<br />решен.</th>
  </tr>
  <?php while ($row = $result->fetch_object()) : ?>
    <tr>
      <td><?= $row->ovd ?></td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Всего квитанций">
        <?= (empty($row->total_qnt)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'&year='.$year.'">'.$row->total_qnt.'</a>' ?>
        <?php $total[0] += $row->total_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Цель помещения - На утилизацию">
        <?= (empty($row->util_qnt)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'&year='.$year.'&pp=1">'.$row->util_qnt.'</a>' ?>
        <?php $total[1] += $row->util_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Цель помещения - На хранение">
        <?= (empty($row->stor_qnt)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'&year='.$year.'&pp=2">'.$row->stor_qnt.'</a>' ?>
        <?php $total[2] += $row->stor_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Цель помещения - Адм.правонарушение">
        <?= (empty($row->admi_qnt)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'&year='.$year.'&pp=3">'.$row->admi_qnt.'</a>' ?>
        <?php $total[3] += $row->admi_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Цель помещения - По уголовному делу">
        <?= (empty($row->crim_qnt)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'&year='.$year.'&pp=4">'.$row->crim_qnt.'</a>' ?>
        <?php $total[4] += $row->crim_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Цель помещения - По материалам проверки (КУСП)">
        <?= (empty($row->kusp_qnt)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'&year='.$year.'&pp=5">'.$row->kusp_qnt.'</a>' ?>
        <?php $total[5] += $row->kusp_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Цель помещения - Смерть владельца">
        <?= (empty($row->deat_qnt)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'&year='.$year.'&pp=6">'.$row->deat_qnt.'</a>' ?>
        <?php $total[6] += $row->deat_qnt ?>
      </td>
      
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Всего принято единиц вооружения">
        <?= (empty($row->total_wpn_qnt)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&year='.$year.'">'.$row->total_wpn_qnt.'</a>' ?>
        <?php $total[7] += $row->total_wpn_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Возвращено владельцу">
        <?= (empty($row->retu_wpn_qnt)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&year='.$year.'&ld=1">'.$row->retu_wpn_qnt.'</a>' ?>
        <?php $total[8] += $row->retu_wpn_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Затребовано сотрудником">
        <?= (empty($row->empl_wpn_qnt)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&year='.$year.'&ld=2">'.$row->empl_wpn_qnt.'</a>' ?>
        <?php $total[9] += $row->empl_wpn_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Направлено на склад УМВД (Хранение)">
        <?= (empty($row->stor_wpn_qnt)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&year='.$year.'&ld=3">'.$row->stor_wpn_qnt.'</a>' ?>
        <?php $total[10] += $row->stor_wpn_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Направлено на склад УМВД (Утилизация)">
        <?= (empty($row->util_wpn_qnt)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&year='.$year.'&ld=4">'.$row->util_wpn_qnt.'</a>' ?>
        <?php $total[11] += $row->util_wpn_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Возвращено в КХО">
        <?= (empty($row->rets_wpn_qnt)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&year='.$year.'&ld=5">'.$row->rets_wpn_qnt.'</a>' ?>
        <?php $total[12] += $row->rets_wpn_qnt ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, <?= $year ?>, Не принято решений">
        <?= (empty($row->wode_wpn_qnt)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&year='.$year.'&ld=0">'.$row->wode_wpn_qnt.'</a>' ?>
        <?php $total[13] += $row->wode_wpn_qnt ?>
      </td>
    </tr>
  <?php endwhile; ?>
  <tr class="table_total_row">
    <th>Итого</th>
    <?php foreach ($total as $n => $qnt) : ?>
      <th><?= $qnt ?></th>
    <?php endforeach; ?>
  </tr>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
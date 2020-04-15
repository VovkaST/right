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

for ($n = 0; $n <= 28; $n++) {
  $total[$n] = 0;
}

$cd = '"'.date('Y-m-d').'"';
$query = '
  SELECT
    ovd.`id_ovd` as `id`, ovd.`ovd`,
    COUNT(DISTINCT wa.`id`) as `accounts`,
    COUNT(DISTINCT w.`id`) as `weapons`,
    COUNT(DISTINCT IF(w.`weapon_type` = 1, w.`id`, NULL)) as `firearms`,
    COUNT(DISTINCT IF(w.`weapon_type` = 2, w.`id`, NULL)) as `ammunition`,
    COUNT(DISTINCT IF(w.`weapon_type` = 3, w.`id`, NULL)) as `explosives`,
    COUNT(DISTINCT IF(w.`weapon_type` = 4, w.`id`, NULL)) as `steelarms`,
    COUNT(DISTINCT 
          IF(
            wd.`id` IS NULL OR wd.`decision` = 5,
            w.`id`, NULL)
    ) as `in_ovd`,
    COUNT(DISTINCT 
          IF(
            (wd.`id` IS NULL OR wd.`decision` = 5) AND w.`weapon_type` = 1,
            w.`id`, NULL)
    ) as `in_ovd_firearms`,
    COUNT(DISTINCT 
          IF(
            (wd.`id` IS NULL OR wd.`decision` = 5) AND w.`weapon_type` = 2,
            w.`id`, NULL)
    ) as `in_ovd_ammunition`,
    COUNT(DISTINCT 
          IF(
            (wd.`id` IS NULL OR wd.`decision` = 5) AND w.`weapon_type` = 3,
            w.`id`, NULL)
    ) as `in_ovd_explosives`,
    COUNT(DISTINCT 
          IF(
            (wd.`id` IS NULL OR wd.`decision` = 5) AND w.`weapon_type` = 4,
            w.`id`, NULL)
    ) as `in_ovd_steelarms`,
    COUNT(DISTINCT 
          IF(
            ((wd.`id` IS NULL AND DATEDIFF('.$cd.', wa.`reg_date`) > 60) OR
             (wd.`decision` = 5 AND DATEDIFF('.$cd.', wd.`date`) > 60)
            ) AND wa.`purpose_placing` <> 4,
            w.`id`, NULL)
    ) as `in_ovd_60`,
    COUNT(DISTINCT 
          IF(
            ((wd.`id` IS NULL AND DATEDIFF('.$cd.', wa.`reg_date`) > 60) OR
             (wd.`decision` = 5 AND DATEDIFF('.$cd.', wd.`date`) > 60)
            ) AND wa.`purpose_placing` <> 4 AND w.`weapon_type` = 1,
            w.`id`, NULL)
    ) as `in_ovd_60_firearms`,
    COUNT(DISTINCT 
          IF(
            ((wd.`id` IS NULL AND DATEDIFF('.$cd.', wa.`reg_date`) > 60) OR
             (wd.`decision` = 5 AND DATEDIFF('.$cd.', wd.`date`) > 60)
            ) AND wa.`purpose_placing` <> 4 AND w.`weapon_type` = 2,
            w.`id`, NULL)
    ) as `in_ovd_60_ammunition`,
    COUNT(DISTINCT 
          IF(
            ((wd.`id` IS NULL AND DATEDIFF('.$cd.', wa.`reg_date`) > 60) OR
             (wd.`decision` = 5 AND DATEDIFF('.$cd.', wd.`date`) > 60)
            ) AND wa.`purpose_placing` <> 4 AND w.`weapon_type` = 3,
            w.`id`, NULL)
    ) as `in_ovd_60_explosives`,
    COUNT(DISTINCT 
          IF(
            ((wd.`id` IS NULL AND DATEDIFF('.$cd.', wa.`reg_date`) > 60) OR
             (wd.`decision` = 5 AND DATEDIFF('.$cd.', wd.`date`) > 60)
            ) AND wa.`purpose_placing` <> 4 AND w.`weapon_type` = 4,
            w.`id`, NULL)
    ) as `in_ovd_60_steelarms`,
    COUNT(DISTINCT IF(wd.`decision` = 3, w.`id`, NULL)) as `in_storage`,
    COUNT(DISTINCT IF(wd.`decision` = 3 AND w.`weapon_type` = 1, w.`id`, NULL)) as `in_storage_firearms`,
    COUNT(DISTINCT IF(wd.`decision` = 3 AND w.`weapon_type` = 2, w.`id`, NULL)) as `in_storage_ammunition`,
    COUNT(DISTINCT IF(wd.`decision` = 3 AND w.`weapon_type` = 3, w.`id`, NULL)) as `in_storage_explosives`,
    COUNT(DISTINCT IF(
             wd.`decision` = 3 AND 
             DATEDIFF('.$cd.', wd.`date`) > 
                IF((YEAR('.$cd.')%4 = 0) AND ((YEAR('.$cd.')%100 != 0) OR (YEAR('.$cd.')%400 = 0)), 366, 365),
             w.`id`, NULL
            )
    ) as `in_storage_1y`,
    COUNT(DISTINCT IF(
             wd.`decision` = 3 AND 
             DATEDIFF('.$cd.', wd.`date`) > 
                IF((YEAR('.$cd.')%4 = 0) AND ((YEAR('.$cd.')%100 != 0) OR (YEAR('.$cd.')%400 = 0)), 366, 365)
		         AND w.`weapon_type` = 1,
             w.`id`, NULL
            )
    ) as `in_storage_1y_firearms`,
    COUNT(DISTINCT IF(
             wd.`decision` = 3 AND 
             DATEDIFF('.$cd.', wd.`date`) > 
                IF((YEAR('.$cd.')%4 = 0) AND ((YEAR('.$cd.')%100 != 0) OR (YEAR('.$cd.')%400 = 0)), 366, 365)
			 AND w.`weapon_type` = 2,
             w.`id`, NULL
            )
    ) as `in_storage_1y_ammunition`,
    COUNT(DISTINCT IF(
             wd.`decision` = 3 AND 
             DATEDIFF('.$cd.', wd.`date`) > 
                IF((YEAR('.$cd.')%4 = 0) AND ((YEAR('.$cd.')%100 != 0) OR (YEAR('.$cd.')%400 = 0)), 366, 365)
			       AND w.`weapon_type` = 3,
             w.`id`, NULL
            )
    ) as `in_storage_1y_explosives`,
    COUNT(DISTINCT IF(
             wd.`decision` = 3 AND 
             DATEDIFF('.$cd.', wd.`date`) > 
                IF((YEAR('.$cd.')%4 = 0) AND ((YEAR('.$cd.')%100 != 0) OR (YEAR('.$cd.')%400 = 0)), 366, 365)
			       AND w.`weapon_type` = 4,
             w.`id`, NULL
            )
    ) as `in_storage_1y_steelarms`,
    COUNT(DISTINCT IF(wd.`decision` IN (1,2), w.`id`, NULL)) as `issued`,
    COUNT(DISTINCT IF(wd.`decision` = 4, w.`id`, NULL)) as `util`,
    COUNT(DISTINCT IF(wdh.`decision` = 6, w.`id`, NULL)) as `lawsuit`,
    COUNT(DISTINCT IF(wdh.`decision` = 7, w.`id`, NULL)) as `notice`
  FROM
    `spr_ovd` as ovd
  LEFT JOIN
    `l_weapons_account` as wa ON
      wa.`ovd` = ovd.`id_ovd` AND
      wa.`deleted` = 0
    LEFT JOIN
      `l_weapons` as w ON
        w.`weapons_account` = wa.`id` AND
        w.`deleted` = 0
      LEFT JOIN
        `l_weapons_decision` as wd ON
          wd.`id` = w.`last_decision` AND
          wd.`deleted` = 0
      LEFT JOIN
        `l_weapons_decision` as wdh ON
          wdh.`weapon` = w.`id`
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
    <li class="item"><div class="block"><a href="index.php?volume=short">Кратко</a></div></li>
    <li class="item"><div class="block current">Расширенно</div></li>
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
<table width="150%" rules="all" border="1" cellpadding="3" align="center" class="result_table" id="myTable">
  <tr class="table_head">
    <th rowspan="3" width="180px">ОВД</th>
    <th rowspan="3" width="65px" title="Всего зарегистрировано квитанций">Всего<br />квит.</th>
    <th rowspan="3" width="65px" title="Всего зарегистрировано квитанций">Всего<br />единиц<br />оружия</th>
    <th colspan="4" title="Место хранения">из них</th>
    <th colspan="19" title="Место хранения">Место хранения</th>
    <th rowspan="3" width="55px" title="Утилизировано">Утил.</th>
    <th rowspan="3" width="55px" title="Возвращено владельцу, Затребовано сотрудником">Выдано</th>
    <th rowspan="3" width="55px" title="Направлено исковое заявление в суд">Иск</th>
    <th rowspan="3" width="55px" title="Направлено извещение собственнику">Извещ.</th>
  </tr>
  <tr class="table_head">
    <th width="65px" rowspan="2" title="Огнестрельное оружие">Огнестр.</th>
    <th width="65px" rowspan="2" title="Боеприпасы">Боеприп.</th>
    <th width="65px" rowspan="2" title="Взрывные устройства (вещества)">ВВ</th>
    <th width="65px" rowspan="2" title="Холодное оружие">Холод.<br />оружие</th>
    <th colspan="5" width="65px" title="Место хранения &ndash; в ОВД">в ОВД</th>
    <th colspan="5" width="65px" title="Место хранения &ndash; в ОВД более 60 суток">более 60 суток</th>
    <th colspan="4" width="65px" title="Место хранения &ndash; на складе УМВД">в УМВД</th>
    <th colspan="5" width="65px" title="Место хранения &ndash; на складе УМВД более 1 года">более 1 года</th>
  </tr>
  <tr class="table_head">
    <th width="60px" title="Огнестрельное оружие">Всего</th>
    <th width="40px" title="Огнестрельное оружие">ОО</th>
    <th width="40px" title="Боеприпасы">БП</th>
    <th width="40px" title="Взрывные устройства (вещества)">ВВ</th>
    <th width="40px" title="Холодное оружие">ХО</th>
    <th width="60px" title="Огнестрельное оружие">Всего</th>
    <th width="40px" title="Огнестрельное оружие">ОО</th>
    <th width="40px" title="Боеприпасы">БП</th>
    <th width="40px" title="Взрывные устройства (вещества)">ВВ</th>
    <th width="40px" title="Холодное оружие">ХО</th>
    <th width="60px" title="Огнестрельное оружие">Всего</th>
    <th width="40px" title="Огнестрельное оружие">ОО</th>
    <th width="40px" title="Боеприпасы">БП</th>
    <th width="40px" title="Взрывные устройства (вещества)">ВВ</th>
    <th width="60px" title="Огнестрельное оружие">Всего</th>
    <th width="40px" title="Огнестрельное оружие">ОО</th>
    <th width="40px" title="Боеприпасы">БП</th>
    <th width="40px" title="Взрывные устройства (вещества)">ВВ</th>
    <th width="40px" title="Холодное оружие">ХО</th>
  </tr>
  <?php while ($row = $result->fetch_object()) : ?>
    <tr>
      <td><?= $row->ovd ?></td>
      <td align="center" title="<?= $row->ovd ?>, Всего зарегистрировано квитанций">
        <?= (empty($row->accounts)) ? 0 : '<a href="acc_list.php?ovd='.$row->id.'">'.$row->accounts.'</a>' ?>
        <?php $total[0] += $row->accounts ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Всего единиц оружия">
        <?= (empty($row->weapons)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'">'.$row->weapons.'</a>' ?>
        <?php $total[1] += $row->weapons ?>
      </td>
      
      <td align="center" title="<?= $row->ovd ?>, Всего единиц оружия (Огнестрельное оружие)">
        <?= (empty($row->firearms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=fa">'.$row->firearms.'</a>' ?>
        <?php $total[2] += $row->firearms ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Всего единиц оружия (Боеприпасы)">
        <?= (empty($row->ammunition)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=amm">'.$row->ammunition.'</a>' ?>
        <?php $total[3] += $row->ammunition ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Всего единиц оружия (Взрывные устройства (вещества))">
        <?= (empty($row->explosives)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=exp">'.$row->explosives.'</a>' ?>
        <?php $total[4] += $row->explosives ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Всего единиц оружия (Холодное оружие)">
        <?= (empty($row->steelarms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=sa">'.$row->steelarms.'</a>' ?>
        <?php $total[5] += $row->steelarms ?>
      </td>
      
      
      <td align="center" title="<?= $row->ovd ?>, Место хранения &ndash; в ОВД (Всего)">
        <?= (empty($row->in_ovd)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD">'.$row->in_ovd.'</a>' ?>
        <?php $total[6] += $row->in_ovd ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Огнестрельное оружие, Место хранения &ndash; в ОВД">
        <?= (empty($row->in_ovd_firearms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=1">'.$row->in_ovd_firearms.'</a>' ?>
        <?php $total[7] += $row->in_ovd_firearms ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Боеприпасы, Место хранения &ndash; в ОВД">
        <?= (empty($row->in_ovd_ammunition)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=2">'.$row->in_ovd_ammunition.'</a>' ?>
        <?php $total[8] += $row->in_ovd_ammunition ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Взрывные устройства (вещества), Место хранения &ndash; в ОВД">
        <?= (empty($row->in_ovd_explosives)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=3">'.$row->in_ovd_explosives.'</a>' ?>
        <?php $total[9] += $row->in_ovd_explosives ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Холодное оружие, Место хранения &ndash; в ОВД">
        <?= (empty($row->in_ovd_steelarms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=4">'.$row->in_ovd_steelarms.'</a>' ?>
        <?php $total[10] += $row->in_ovd_steelarms ?>
      </td>
      
      <td align="center" title="<?= $row->ovd ?>, Место хранения &ndash; в ОВД более 60 суток (Всего)">
        <?= (empty($row->in_ovd_60)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD60">'.$row->in_ovd_60.'</a>' ?>
        <?php $total[11] += $row->in_ovd_60 ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Огнестрельное оружие, Место хранения &ndash; в ОВД более 60 суток">
        <?= (empty($row->in_ovd_60_firearms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=1">'.$row->in_ovd_60_firearms.'</a>' ?>
        <?php $total[12] += $row->in_ovd_60_firearms ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Боеприпасы, Место хранения &ndash; в ОВД более 60 суток">
        <?= (empty($row->in_ovd_60_ammunition)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=2">'.$row->in_ovd_60_ammunition.'</a>' ?>
        <?php $total[13] += $row->in_ovd_60_ammunition ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Взрывные устройства (вещества), Место хранения &ndash; в ОВД более 60 суток">
        <?= (empty($row->in_ovd_60_explosives)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=3">'.$row->in_ovd_60_explosives.'</a>' ?>
        <?php $total[14] += $row->in_ovd_60_explosives ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Холодное оружие, Место хранения &ndash; в ОВД более 60 суток">
        <?= (empty($row->in_ovd_60_steelarms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InOVD&wtype=4">'.$row->in_ovd_60_steelarms.'</a>' ?>
        <?php $total[15] += $row->in_ovd_60_steelarms ?>
      </td>
      
      <td align="center" title="<?= $row->ovd ?>, Место хранения &ndash; на складе УМВД (Всего)">
        <?= (empty($row->in_storage)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor">'.$row->in_storage.'</a>' ?>
        <?php $total[16] += $row->in_storage ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Огнестрельное оружие, Место хранения &ndash; на складе УМВД">
        <?= (empty($row->in_storage_firearms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor&wtype=1">'.$row->in_storage_firearms.'</a>' ?>
        <?php $total[17] += $row->in_storage_firearms ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Боеприпасы, Огнестрельное оружие, Место хранения &ndash; на складе УМВД">
        <?= (empty($row->in_storage_ammunition)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor&wtype=2">'.$row->in_storage_ammunition.'</a>' ?>
        <?php $total[18] += $row->in_storage_ammunition ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Взрывные устройства (вещества), Место хранения &ndash; на складе УМВД">
        <?= (empty($row->in_storage_explosives)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor&wtype=3">'.$row->in_storage_explosives.'</a>' ?>
        <?php $total[19] += $row->in_storage_explosives ?>
      </td>
      
      <td align="center" title="<?= $row->ovd ?>, Место хранения &ndash; на складе УМВД более 1 года">
        <?= (empty($row->in_storage_1y)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor1y">'.$row->in_storage_1y.'</a>' ?>
        <?php $total[20] += $row->in_storage_1y ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Боеприпасы, Место хранения &ndash; на складе УМВД более 1 года">
        <?= (empty($row->in_storage_1y_firearms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor1y&wtype=1">'.$row->in_storage_1y_firearms.'</a>' ?>
        <?php $total[21] += $row->in_storage_1y_firearms ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Взрывные устройства (вещества), Место хранения &ndash; на складе УМВД более 1 года">
        <?= (empty($row->in_storage_1y_ammunition)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor1y&wtype=2">'.$row->in_storage_1y_ammunition.'</a>' ?>
        <?php $total[22] += $row->in_storage_1y_ammunition ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Место хранения &ndash; на складе УМВД более 1 года">
        <?= (empty($row->in_storage_1y_explosives)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor1y&wtype=3">'.$row->in_storage_1y_explosives.'</a>' ?>
        <?php $total[23] += $row->in_storage_1y_explosives ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Место хранения &ndash; на складе УМВД более 1 года">
        <?= (empty($row->in_storage_1y_steelarms)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=InStor1y&wtype=4">'.$row->in_storage_1y_steelarms.'</a>' ?>
        <?php $total[24] += $row->in_storage_1y_steelarms ?>
      </td>
      
      <td align="center" title="<?= $row->ovd ?>, Утилизировано">
        <?= (empty($row->util)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=Util">'.$row->util.'</a>' ?>
        <?php $total[25] += $row->util ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Возвращено владельцу, Затребовано сотрудником">
        <?= (empty($row->issued)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=issued">'.$row->issued.'</a>' ?>
        <?php $total[26] += $row->issued ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Направлено исковое заявление в суд">
        <?= (empty($row->lawsuit)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=lowsuit">'.$row->lawsuit.'</a>' ?>
        <?php $total[27] += $row->lawsuit ?>
      </td>
      <td align="center" title="<?= $row->ovd ?>, Направлено извещение собственнику">
        <?= (empty($row->notice)) ? 0 : '<a href="wpn_list.php?ovd='.$row->id.'&clause=notice">'.$row->notice.'</a>' ?>
        <?php $total[28] += $row->notice ?>
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
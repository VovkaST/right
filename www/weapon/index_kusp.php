<?PHP
  if (pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) != 'index.php')
  header('Location: /error/404.php');
  
  $yearList = IndictmentsYearsListForWeapons();
  if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
  } else {
    $year = date('Y');
  }
  
  for ($n = 0; $n <= 5; $n++) {
  $total[$n][0] = 0;
  $total[$n][1] = 0;
  $total[$n][2] = 0;
}



//$cd = '"'.date('Y-m-d').'"';
/*$query = '
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
    and (wa.kusp is not null or wa.crime_case is not null)
    and year(wa.`reg_date`) = '.$year.' 
    '.((!empty($where)) ? 'AND '.implode(' AND ', $where) : null).'
  GROUP BY
    ovd.`id_ovd`
';*/

$query = "
     select 
		o.id_ovd,
		o.ovd,
		count(distinct k.id) as accounts,
		count(distinct if(k.kusp is not null and k.crime_case is null, k.id, null)) as accounts_kusp, 
		count(distinct if(k.crime_case is not null, k.id, null)) as accounts_ud, 
		count(distinct e.id) as weapons,
		count(distinct if(k.kusp is not null and k.crime_case is null, e.id, null)) as weapons_kusp, 
		count(distinct if(k.crime_case is not null, e.id, null)) as weapons_ud, 
		COUNT(DISTINCT IF(e.weapon_type = 1, e.id, NULL)) as firearms,
		count(distinct if(e.weapon_type = 1 and k.kusp is not null and k.crime_case is null, e.id, NULL)) as firearms_kusp, 
		count(distinct if(e.weapon_type = 1 and k.crime_case is not null, e.id, NULL)) as firearms_ud, 
		COUNT(DISTINCT IF(e.weapon_type = 2, e.id, NULL)) as ammunition,
		count(distinct if(e.weapon_type = 2 and k.kusp is not null and k.crime_case is null, e.id, NULL)) as ammunition_kusp, 
		count(distinct if(e.weapon_type = 2 and k.crime_case is not null, e.id, NULL)) as ammunition_ud, 
		COUNT(DISTINCT IF(e.weapon_type = 3, e.id, NULL)) as explosives,
		count(distinct if(e.weapon_type = 3 and k.kusp is not null and k.crime_case is null, e.id, NULL)) as explosives_kusp, 
		count(distinct if(e.weapon_type = 3 and k.crime_case is not null, e.id, NULL)) as explosives_ud, 
		COUNT(DISTINCT IF(e.weapon_type = 4, e.id, NULL)) as steelarms,
		count(distinct if(e.weapon_type = 4 and k.kusp is not null and k.crime_case is null, e.id, NULL)) as steelarms_kusp, 
		count(distinct if(e.weapon_type = 4 and k.crime_case is not null, e.id, NULL)) as steelarms_ud  
		from l_weapons_account as k join l_weapons as e on e.weapons_account = k.id  
									left join spr_ovd as o on o.id_ovd=k.ovd 
		where (k.kusp is not null or k.crime_case is not null) and
			   e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 ".$ovd."
			   
		group by o.ovd
		order by o.id_ovd
";
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
    <li class="item"><div class="block"><a href="index.php?volume=full">Расширенно</a></div></li>
    <li class="item"><div class="block current">КУСП/УД</div></li>
    <?php if (!empty($_SESSION['user']['admin'])) : ?>
      <li class="item"><div class="block"><a href="index.php?volume=using">Использование сервиса</a></div></li>
    <?php endif; ?>
  </ul>
</div>

<div class="decision_top_block actions_list">
  
</div>
<table width="80%" rules="all" border="1" cellpadding="3" align="center" class="result_table_a" id="myTable">
  <tr class="table_head">
    <th rowspan="2" width="180px">ОВД</th>
    <th rowspan="2" width="65px" title="Всего зарегистрировано квитанций">Всего<br />квит.</th>
    <th rowspan="2" width="65px" title="Всего зарегистрировано оружия">Всего<br />единиц<br />оружия</th>
    <th colspan="4" title="Место хранения">из них</th>
    <!--th colspan="19" title="Место хранения">Место хранения</th-->
  </tr>
  <tr class="table_head">
    <th width="65px" title="Огнестрельное оружие">Огнестр.</th>
    <th width="65px" title="Боеприпасы">Боеприп.</th>
    <th width="65px" title="Взрывные устройства (вещества)">ВВ</th>
    <th width="65px" title="Холодное оружие">Холод.<br />оружие</th>
  </tr>
  <!--tr class="table_head">
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
  </tr-->
  <?php while ($row = $result->fetch_object()) : ?>
    <tr>
      <td><?= $row->ovd ?></td>
      <td align="center" >
        <?= (empty($row->accounts)) ? '': '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=accounts" title= "Всего зарегистрировано квитанций">'.$row->accounts.'</a>' ?>
		(<?= (empty($row->accounts_kusp)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=accounts_kusp" title="Принято по КУСП">'.$row->accounts_kusp.'</a>' ?>/<?= (empty($row->accounts_ud)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=accounts_ud" title="Принято по уг.делу">'.$row->accounts_ud.'</a>' ?>)
        <?php $total[0][0] += $row->accounts; $total[0][1] += $row->accounts_kusp; $total[0][2] += $row->accounts_ud ?>
      </td>
	  <td align="center" >
  	     <?= (empty($row->weapons)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=weapons" title= "Всего зарегистрировано квитанций">'.$row->weapons.'</a>' ?>
		(<?= (empty($row->weapons_kusp)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=weapons_kusp" title="Принято по КУСП">'.$row->weapons_kusp.'</a>' ?>/<?= (empty($row->weapons_ud)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=weapons_ud" title="Принято по уг.делу">'.$row->weapons_ud.'</a>' ?>)
        <?php $total[1][0] += $row->weapons; $total[1][1] += $row->weapons_kusp; $total[1][2] += $row->weapons_ud ?>
      </td>
      <td align="center" >
  	     <?= (empty($row->firearms)) ? '': '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=firearms" title= "Всего зарегистрировано квитанций">'.$row->firearms.'</a>' ?>
		(<?= (empty($row->firearms_kusp)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=firearms_kusp" title="Принято по КУСП">'.$row->firearms_kusp.'</a>' ?>/<?= (empty($row->firearms_ud)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=firearms_ud" title="Принято по уг.делу">'.$row->firearms_ud.'</a>' ?>)
        <?php $total[2][0] += $row->firearms; $total[2][1] += $row->firearms_kusp; $total[2][2] += $row->firearms_ud ?>
	  </td>
      <td align="center" >
  	     <?= (empty($row->ammunition)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=ammunition" title= "Всего зарегистрировано квитанций">'.$row->ammunition.'</a>' ?>
		(<?= (empty($row->ammunition_kusp)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=ammunition_kusp" title="Принято по КУСП">'.$row->ammunition_kusp.'</a>' ?>/<?= (empty($row->ammunition_ud)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=ammunition_ud" title="Принято по уг.делу">'.$row->ammunition_ud.'</a>' ?>)
        <?php $total[3][0] += $row->ammunition; $total[3][1] += $row->ammunition_kusp; $total[3][2] += $row->ammunition_ud ?>
	  </td>
      <td align="center" >
  	     <?= (empty($row->explosives)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=explosives" title= "Всего зарегистрировано квитанций">'.$row->explosives.'</a>' ?>
		(<?= (empty($row->explosives_kusp)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=explosives_kusp" title="Принято по КУСП">'.$row->explosives_kusp.'</a>' ?>/<?= (empty($row->explosives_ud)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=explosives_ud" title="Принято по уг.делу">'.$row->explosives_ud.'</a>' ?>)
        <?php $total[4][0] += $row->explosives; $total[4][1] += $row->explosives_kusp; $total[4][2] += $row->explosives_ud ?>
	   </td>
	  <td align="center" >
         <?= (empty($row->steelarms)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=steelarms" title= "Всего зарегистрировано квитанций">'.$row->steelarms.'</a>' ?>
		(<?= (empty($row->steelarms_kusp)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=steelarms_kusp" title="Принято по КУСП">'.$row->steelarms_kusp.'</a>' ?>/<?= (empty($row->steelarms_ud)) ? '' : '<a href="wpn_list_kusp_ud.php?ovd='.$row->id_ovd.'&clause=steelarms_ud" title="Принято по уг.делу">'.$row->steelarms_ud.'</a>' ?>)
          <?php $total[5][0] += $row->steelarms; $total[5][1] += $row->steelarms_kusp; $total[5][2] += $row->steelarms_ud ?>
	  </td>
    </tr>
  <?php endwhile; ?>
  
  <tr class="table_total_row">
    <th>Итого</th>
    <?php foreach ($total as $n => $qnt) : ?>
	  
	  
      <th><?= $qnt[0].' ('.$qnt[1].'/'.$qnt[2].')'?></th>
    <?php endforeach; ?>
  </tr>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
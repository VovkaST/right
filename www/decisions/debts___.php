<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (!empty($_GET["ovd"]) && is_numeric($_GET["ovd"])) {
  $ovd = array_merge(array('id' => intval($_GET["ovd"])), getOvdName($_GET["ovd"]));
} else {
  header('Location: formation_results.php');
}

$page_title = 'Долги по вводу';

$breadcrumbs = array(
  'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела' => 'index.php',
  'Долги по вводу' => ''
);

$yearList = decisionYears();
if (!empty($_GET["year"]) && is_numeric($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}
$lastSwap = OVDlastSwap($ovd['id']);
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
/*
<div class="debt_date">
  Последняя выгрузка до выставления долгов в <?= date('H:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?>
</div>
*/
?>
<h3><?= $ovd['ovd_full'] ?></h3>
<div class="debt_date"></div>
<?php
if($year < 2018){
$query = mysql_query('
  SELECT
      CONCAT("/wonc/ek.php?id=", dh.`kusp`) as link,
      dh.`id_SB_RESH` as `id`,
      dh.`ovd`,
      dh.`reg_number`,
      DATE_FORMAT(dh.`reg_date`, "%d.%m.%Y") as `reg_date`,
      GROUP_CONCAT(
        DISTINCT
          dh.`dec_number`
        ORDER BY
          dh.`dec_number`
        SEPARATOR
          "<br />"
      ) as `dec_num`,
      GROUP_CONCAT(
        DISTINCT
          DATE_FORMAT(dh.`dec_date`, "%d.%m.%Y")
        ORDER BY
          dh.`dec_date`
        SEPARATOR
          "<br />"
      ) as `dec_date`,
      CONCAT(
        IF(dh.`emp_person` IS NOT NULL, dh.`emp_person`, ""),
        IF(dh.`emp_position` IS NOT NULL, 
           CONCAT(", ", 
                     LOWER(
                       IF(
                        POSITION("," IN dh.`emp_position`) > 0,
                        SUBSTRING(dh.`emp_position`, 1, POSITION("," IN dh.`emp_position`) - 1),
                        dh.`emp_position`)
                     )
                 ),
           "")
      ) as `emp`,
      dh.`proc_par` as `upk`,
      GROUP_CONCAT(
        DISTINCT
          dh.`qualification`
        ORDER BY
          dh.`qualification`
        SEPARATOR
          "<br />"
      ) as `uk`,
      GROUP_CONCAT(
        d.`id`
      ) as `dec_id`
    FROM
      `ek_dec_refusals` as dh
    LEFT JOIN
      `l_kusp` as k ON
        k.`ek` = dh.`kusp`
      LEFT JOIN
        `l_dec_kusp` as dk ON
          dk.`kusp` = k.`id` AND
          dk.`deleted` = 0
        LEFT JOIN
          `l_decisions` as d ON
            d.`id` = dk.`decision` AND
            d.`deleted` = 0
    WHERE
      dh.`dec_date` BETWEEN STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-12-31", "%Y-%m-%d") AND
      dh.`dec_date` >= STR_TO_DATE("2015-01-01", "%Y-%m-%d")
      AND dh.`ovd` = '.$ovd['id'].'
    GROUP BY
      dh.`kusp`
    HAVING
      `dec_id` IS NULL
') or die(mysql_error());
}
else 
{/*
  $query = mysql_query('SELECT
      
      CONCAT("/wonc/ek___.php?id=", dh.`id`) as link,
      dh.ovd_kod as ovd,
      dh.`kusp` as reg_number,
      DATE_FORMAT(dh.`Data_i_vremya_registracii`, "%d.%m.%Y") as `reg_date`,
      DATE_FORMAT(dh.`reshenie_date`, "%d.%m.%Y")	as `dec_date`,
      
      "0" as uk,
      "0" as upk,
      "0" as dec_num,
      "0" as id,
      dh.Rezultat_rassmotreniya as `emp`,
      dh.`Ispolnitel` as `isp`
	 FROM
      `sodch_kusp` as dh
    LEFT JOIN
      `l_kusp` as k ON
        k.`kusp` = dh.`kusp` and year(k.`date`) = year(dh.Data_i_vremya_registracii) and k.ovd = dh.ovd_kod
      
    WHERE
      dh.`reshenie_date` BETWEEN STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-12-31", "%Y-%m-%d") 

      and dh.`ovd_kod` = '.$ovd['id'].'
      and k.id is null
      and dh.Rezultat_rassmotreniya like "%отказано в возбуждении уголов%"
      group by reg_number, reg_date, ovd
 ') or die(mysql_error());
  $query = mysql_query("
  SELECT
      
      CONCAT('/wonc/ek___.php?id=', xxx.`id`) as link,
      xxx.ovd_kod as ovd,
      xxx.`kusp` as reg_number,
      DATE_FORMAT(xxx.`Data_i_vremya_registracii`, '%d.%m.%Y') as `reg_date`,
      DATE_FORMAT(xxx.`reshenie_date`, '%d.%m.%Y')	as `dec_date`,
      
      '0' as uk,
      '0' as upk,
      '0' as dec_num,
      '0' as id,
      xxx.Rezultat_rassmotreniya as `emp`,
      xxx.`Ispolnitel` as `isp`
      from 
        (select kn.id, kn.ovd_kod, kn.KUSP, kn.Data_i_vremya_registracii, d.reshenie_date, d.Rezultat_rassmotreniya, kn.Ispolnitel 
         from sodch_kusp_new as kn
         join
          (
            select s1.id_kusp as kuspid, s1.reshenie_date, s1.rezultat_rassmotreniya -- s1.rezultat_rassmotreniya, s1.id_kusp 
            from sodch_decision s1
            left join sodch_decision s2
            on
            s1.id_kusp = s2.id_kusp and s1.reshenie_date >= s2.reshenie_date
            where s1.reshenie_date between '{$year}-01-01' and '{$year}-12-31'
            and s1.id_kod_rech = 4
            group by s1.id_kusp
          ) as d
          on kn.id = d.kuspid

          left join l_kusp as lk
          on lk.`kusp` = kn.`kusp` and year(lk.`date`) = year(kn.Data_i_vremya_registracii) and lk.ovd = kn.ovd_kod
          where lk.id is null and kn.ovd_kod = {$ovd['id']}
        ) as xxx
	 	group by reg_number, reg_date
 ") or die(mysql_error());*/
  $query = mysql_query("
    
SELECT      
      CONCAT('/wonc/ek___.php?id=', xxx.`id`) as link,
      xxx.ovd_kod as ovd,
      xxx.`kusp` as reg_number,
      DATE_FORMAT(xxx.`Data_i_vremya_registracii`, '%d.%m.%Y') as `reg_date`,
      DATE_FORMAT(xxx.`reshenie_date`, '%d.%m.%Y')	as `dec_date`,
      
      '0' as uk,
      '0' as upk,
      '0' as dec_num,
      '0' as id,
      xxx.Rezultat_rassmotreniya as `emp`,
      xxx.`Ispolnitel` as `isp`
      from 
        (select kn.id, kn.ovd_kod, kn.KUSP, kn.Data_i_vremya_registracii, d.reshenie_date, d.Rezultat_rassmotreniya, kn.Ispolnitel 
         from sodch_kusp_new as kn
         join
          (select sdg.kuspid, sd.reshenie_date, sd.Rezultat_rassmotreniya from sodch_decision as sd
          join (
              select t.id_kusp as kuspid, max(t.reshenie_date) as maxdate, max(t.inp_date) as inpd
              from sodch_decision as t
              where t.reshenie_date between '{$year}-01-01' and '{$year}-12-31'
              group by t.id_kusp
          ) as sdg 
          on sd.id_kusp = sdg.kuspid and sd.reshenie_date = sdg.maxdate and sd.inp_date = sdg.inpd
          where 
            year(sd.reshenie_date) = {$year}
            and sd.id_kod_rech = 4) as d
          on kn.id = d.kuspid

          left join l_kusp as lk
          on lk.`kusp` = kn.`kusp` and year(lk.`date`) = year(kn.Data_i_vremya_registracii) and lk.ovd = kn.ovd_kod
          where lk.id is null and kn.ovd_kod = {$ovd['id']}
        ) as xxx
	 	group by reg_number, reg_date
 ") or die(mysql_error());
}
$i = 1;
?>
<table rules="all" border="1" cellpadding="5" align="center" class="result_table">
  <tr class="table_head">
    <th>№<br/>п/п</th>
    <th width="80px">Рег.№<br/>КУСП</th>
    <th width="80px">Дата рег.</th>
    <?php if($year<2018): ?><th width="80px">№<br/>решения</th><?php endif;?>
    <?php if($year<2018): ?><th width="80px">Дата<br/>решения</th><?php endif;?>
    <?php if($year<2018): ?><th>Решение вынес</th><?php else: ?><th>Решение</th><?php endif; ?>
    <?php if($year<2018): ?><th width="70px">ст.<br/>УК РФ</th><?php endif;?>
    <?php if($year<2018): ?><th>пункт<br/>ст.24 УПК</th><?php endif;?>
    <?php if($year>=2018): ?><th>Кому<br/>поручено</th><?php endif;?>
  </tr>
  <?php while ($result = mysql_fetch_assoc($query)): ?>
  <tr>
    <td align="center"><?= $i++ ?>.</td>
    <?php if($year>=2018): ?><td align="center"><a href="<?= $result['link'] ?>&year=<?= $year ?>" target="_blank" title="Электронный КУСП"><?= $result['reg_number'] ?></a></td>
    <?php else: ?><td align="center"><a href="<?= $result['link'] ?>" target="_blank" title="Электронный КУСП"><?= $result['reg_number'] ?></a></td><?php endif; ?>
    <td align="center"><?= $result['reg_date'] ?></td>
    <?php if($year<2018): ?><td align="center"><?= $result['dec_num'] ?></td><?php endif;?>
    <?php if($year<2018): ?><td align="center"><?= $result['dec_date'] ?></td><?php endif;?>
    <td><?= $result['emp'] ?></td>
    <?php if($year>=2018): ?><td align="center"><?= $result['isp'] ?></td><?php endif;?>
    <?php if($year<2018): ?><td align="center"><?= $result['uk'] ?></td><?php endif;?>
    <?php if($year<2018): ?><td>
      <div class="info_block"><?= $result['upk'] ?></div>
      <div class="links_block">
        <a href="decision.php" id="<?= $result['id'] ?>" method="debt">
          <img src="/images/plus.png" height="25px" border="none" alt="Добавить">
        </a>
      </div>
    <?php endif;?></td>
  </tr>
  <?php endwhile; ?>
</table>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
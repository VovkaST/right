<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));
if ((!isset($_GET['object'])) || (isset($_GET['object']) && !is_numeric($_GET['object']))) {
  header('Location: index.php');
}
$object = floor(abs($_GET['object']));
if ($object > 10 and $object <> 144 and $object <> 145) header('Location: index.php'); //144, 145 - для ежедневноых совпадений
$rel = '
  LEFT JOIN
    `l_relatives` as rel ON
      CASE
        WHEN rel.`from_obj_type` = '.$object.' THEN rel.`from_obj`
        WHEN rel.`to_obj_type` = '.$object.' THEN rel.`to_obj`
      END = o.`id`
  WHERE
    rel.`id` IS NOT NULL AND
    rel.`ais` = '.$_SESSION['crime']['ais']
    
;
require_once(KERNEL.'connection.php');
switch ($object) {
  case 1:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`surname`, o.`name`, o.`fath_name`,
        DATE_FORMAT(o.`borth`, "%d.%m.%Y") as `borth`
      FROM
        `o_lico` as o
        '.$rel.'
      ORDER BY
        o.`surname`, o.`name`, o.`fath_name`, o.`borth`
    ') or die(mysql_error());
    break;
  
  case 2:
    $query = mysql_query('
      SELECT
        so.`ovd`, o.`ovd_id`,
        o.`id`, o.`kusp_num`,
        DATE_FORMAT(o.`kusp_date`, "%d.%m.%Y") as `kusp_date`,
        o.`decision_number`,
        DATE_FORMAT(o.`decision_date`, "%d.%m.%Y") as `decision_date`,
        su.`st`
      FROM
        `o_event` as o
      LEFT JOIN
        `spr_ovd` as so ON
          so.`id_ovd` = o.`ovd_id`
      LEFT JOIN
        `spr_uk` as su ON
          su.`id_uk` = o.`article_id`
      WHERE
        o.`ais` = '.$_SESSION['crime']['ais'].'
      ORDER BY
        o.`kusp_date` DESC
    ') or die(mysql_error());
    break;
    
  case 3:
    $query = mysql_query('
      SELECT
        DISTINCT o.id,
        CONCAT(
          RTRIM(reg.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = reg.`socr` AND `level` = 1)
        ) as `region`,
        CONCAT(
          RTRIM(dist.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = dist.`socr` AND `level` = 2)
        ) as `district`,
        CONCAT(
          RTRIM(city.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = city.`socr` AND `level` = 3)
        ) as `city`,
        CONCAT(
          RTRIM(loc.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = loc.`socr` AND `level` = 4)
        ) as `locality`,
        CONCAT(
          RTRIM(str.`name`), " ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = str.`socr` AND `level` = 5)
        ) as `street`,
        CONCAT("д.", IF(o.`house_lit` IS NOT NULL, CONCAT(o.`house`,"/",o.`house_lit`), o.`house`)) as `house`,
        CONCAT("кв.", IF(o.`flat_lit` IS NOT NULL, CONCAT(o.`flat`,"/",o.`flat_lit`), o.`flat`)) as `flat`
      FROM
        `o_address` as o
      LEFT JOIN
        `spr_region` as reg ON
          reg.`id` = o.`region`
      LEFT JOIN
        `spr_district` as dist ON
          dist.`id` = o.`district`
      LEFT JOIN
        `spr_city` as city ON
          city.`id` = o.`city`
      LEFT JOIN
        `spr_locality` as loc ON
          loc.`id` = o.`locality`
      LEFT JOIN
        `spr_street` as str ON
          str.`id` = o.`street`
      '.$rel.'
      ORDER BY
        o.`region`, o.`district`, o.`city`, o.`locality`, o.`street`, o.`house`, o.`house_lit`, o.`flat`, o.`flat_lit`
    ') or die(mysql_error());
    break;

  case 4:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`number`, o.`serial`,
        sd.`type`
      FROM
        `o_documents` as o
      LEFT JOIN
        `spr_documents` as sd ON
          sd.`id` = o.`type`
      '.$rel.'
    ') or die(mysql_error());
    break;
  
  case 5:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`IMEI`, o.`model`
      FROM
        `o_mobile_device` as o
      '.$rel.'
    ') or die(mysql_error());
    break;
  
  case 6:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`number`, op.`operator`, opr.`region`
      FROM
        `o_telephone` as o
      LEFT JOIN
        `l_operators_ranges` as opr ON
          opr.`id` = o.`operator_range`
      LEFT JOIN
        `l_operators` as op ON
          op.`id` = o.`operator`
      '.$rel.'
    ') or die(mysql_error());
    break;
  
  case 7:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`number`, o.`bank`
      FROM
        `o_bank_account` as o
      '.$rel.'
    ') or die(mysql_error());
    break;

  case 8:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`number`, o.`bank`
      FROM
        `o_bank_card` as o
      '.$rel.'
    ') or die(mysql_error());
    break;
    
  case 9:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`number`,
        sp.`system`
      FROM
        `o_wallet` as o
      LEFT JOIN
        `spr_payment` as sp ON
          sp.`id` = o.`payment_system`
      '.$rel.'
    ') or die(mysql_error());
    break;
  
  case 10:
    $query = mysql_query('
      SELECT
        DISTINCT o.`id`, o.`name`
      FROM
        `o_mail` as o
      '.$rel.'
    ') or die(mysql_error());
    break;
  case 144:
    $query = mysql_query("
				select
				distinct
				dd.number,
				k.id,
				ovd.ovd,
				k.kusp_num,
				date_format(k.kusp_date, '%d.%m.%Y') as kusp_date,
				k.decision_number,
				date_format(k.decision_date, '%d.%m.%Y') as decision_date,
				date_format(k.create_date, '%d.%m.%Y') as create_date,
				concat(replace(replace(substring(k.story, 1, 100), char(10), ''),char(13), ''),'...') as fab 
				from (
						select
						svt.to_obj as id_t,
						t.number
						from o_telephone as t join l_relatives as svt on t.id = svt.to_obj and svt.to_obj_type=6 and svt.from_obj_type = 2
											  join spr_relatives as _svt on _svt.to_obj = 6 and _svt.id = svt.`type`
											  join o_event as e on e.id = svt.from_obj and svt.from_obj_type = 2
						where svt.ais = 1 and svt.create_date between if(DAYOFWEEK(now()) = 7 or DAYOFWEEK(now()) = 1, date_format(date_sub(now(), interval 4 day), '%Y-%m-%d'), date_format(date_sub(now(), interval 2 day), '%Y-%m-%d')) and date_format(now(), '%Y-%m-%d')
							  and e.kusp_num is not null
						group by svt.to_obj
						having count(svt.to_obj)>1
						order by t.number) as dd left join l_relatives as sv on sv.to_obj = dd.id_t and sv.to_obj_type = 6 and sv.ais = 1 and sv.from_obj_type = 2
													  join o_event as k on k.id = sv.from_obj 
													  join spr_ovd as ovd on k.ovd_id=ovd.id_ovd

				order by dd.number, k.kusp_date desc, k.decision_date desc

    ") or die(mysql_error());
  break;
  case  145: //IMEI
    $query = mysql_query("
				select
				distinct
				dd.IMEI as number,
				k.id,
				ovd.ovd,
				k.kusp_num,
				date_format(k.kusp_date, '%d.%m.%Y') as kusp_date,
				k.decision_number,
				date_format(k.decision_date, '%d.%m.%Y') as decision_date,
				date_format(k.create_date, '%d.%m.%Y') as create_date,
				concat(replace(replace(substring(k.story, 1, 100), char(10), ''),char(13), ''),'...') as fab 
				from (
						select
						svt.to_obj as id_t,
						t.IMEI
						from o_mobile_device as t join l_relatives as svt on t.id = svt.to_obj and svt.to_obj_type=5 and svt.from_obj_type = 2
											  join spr_relatives as _svt on _svt.to_obj = 5 and _svt.id = svt.`type`
											  join o_event as e on e.id = svt.from_obj and svt.from_obj_type = 2
						where svt.ais = 1 and svt.create_date between if(DAYOFWEEK(now()) = 7 or DAYOFWEEK(now()) = 1, date_format(date_sub(now(), interval 4 day), '%Y-%m-%d'), date_format(date_sub(now(), interval 2 day), '%Y-%m-%d')) and date_format(now(), '%Y-%m-%d')
							  and e.kusp_num is not null
						group by svt.to_obj
						having count(svt.to_obj)>1
						order by t.IMEI) as dd left join l_relatives as sv on sv.to_obj = dd.id_t and sv.to_obj_type = 5 and sv.ais = 1 and sv.from_obj_type = 2
													  join o_event as k on k.id = sv.from_obj 
													  join spr_ovd as ovd on k.ovd_id=ovd.id_ovd

				order by dd.IMEI, k.kusp_date desc, k.decision_date desc

    ") or die(mysql_error());
    break;
}
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
  <script type="text/javascript" src="js/procedures.js"></script> 
</head>
<body>
<?php
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
</div>
<center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?> (список)</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
<?php
$i = 1;
if ($object == 1) : // лица ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th>ФИО, д.р.</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center"><?= $i++ ?>.</td>
      <td>
        <a href="face.php?face_id=<?= $result['id'] ?>"><?= mb_convert_case($result['surname'].' '.$result['name'].' '.$result['fath_name'], MB_CASE_TITLE, "UTF-8").', '.$result['borth'] ?> г.р.</a>
      </td>
    </tr>
  <?php endwhile; ?>

<?php elseif ($object == 2) : // преступление ?>
  <tr class="table_head">
    <th rowspan="2">№<br/>п/п</th>
    <th rowspan="2" width="250px">ОВД</th>
    <th colspan="2">КУСП</th>
    <th colspan="2">уг.дело</th>
    <th rowspan="2">ст.<br/>УК РФ</th>
  </tr>
  <tr class="table_head">
    <th width="70px">рег.№</th>
    <th width="100px">&darr; дата рег.</th>
    <th width="70px">рег.№</th>
    <th width="100px">дата рег.</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="events_list.php?ovd_id=<?= $result["ovd_id"] ?>"><?= $result["ovd"] ?></a>
      </td>
      <td align="center">
        <a href="event.php?event_id=<?= $result["id"] ?>"><?= $result["kusp_num"] ?></a>
      </td>
      <td align="center">
        <?= $result["kusp_date"] ?>
      </td>
      <td align="center">
        <a href="event.php?event_id=<?= $result["id"] ?>"><?= $result["decision_number"] ?></a>
      </td>
      <td align="center">
        <?= $result["decision_date"] ?>
      </td>
      <td align="center">
        <?= $result["st"] ?>
      </td>
    </tr>
  <?php endwhile; ?>
  
<?php elseif ($object == 3) : // адрес ?>
  <tr class="table_head">
    <th width="40px">№<br/>п/п</th>
    <th width="600px">Адрес</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : 
    $address = array();
    foreach($result as $f => $v) {
      if (in_array($f, array('region', 'district', 'city', 'locality', 'street', 'house', 'flat'))) {
        if ($v != '') $address[] = $v;
      }
    }?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="address.php?addr_id=<?= $result["id"] ?>"><?= implode(', ', $address) ?></a></li>
      </td>
    </tr>
  <?php endwhile; ?>
  
<?php elseif ($object == 4) : // документ ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="100px">Тип</th>
    <th width="150px">Серия - номер</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <?= $result["type"] ?>
      </td>
      <td align="center">
        <a href="document.php?doc_id=<?= $result["id"] ?>"><?= $result["serial"].' - '.$result["number"] ?></a>
      </td>
    </tr>
  <?php endwhile; ?>
  
<?php elseif ($object == 5) : // устройство ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="150px">IMEI</th>
    <th width="150px">Модель</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="device.php?dev_id=<?= $result["id"] ?>"><?= $result["IMEI"] ?></a>
      </td>
      <td align="center">
        <?= $result["model"] ?>
      </td>
    </tr>
  <?php endwhile; ?>

<?php elseif ($object == 6) : // телефонный номер ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th>Абонентский номер</th>
    <th style="min-width: 300px;">Оператор</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="telephone.php?tel_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
      </td>
      <td>
        <?php if ($result["operator"] != '') echo $result["operator"].', '.$result["region"] ?>
      </td>
    </tr>
  <?php endwhile; ?>
  
<?php elseif ($object == 7) : // банковский счет ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="150px">№ счета</th>
    <th style="min-width: 150px;">Банк</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="bank_account.php?acc_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
      </td>
      <td align="center">
        <?= $result["bank"] ?>
      </td>
    </tr>
  <?php endwhile; ?>

<?php elseif ($object == 8) : // банковская карта ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="200px">№ карты</th>
    <th width="130px">Банк</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="bank_card.php?card_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
      </td>
      <td align="center">
        <?= $result["bank"] ?>
      </td>
    </tr>
  <?php endwhile; ?>
  
<?php elseif ($object == 9) : // Электронный кошелек" ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="200px">№ кошелька</th>
    <th width="130px">Система</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="e-wallet.php?wall_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
      </td>
      <td align="center">
        <?= $result["system"] ?>
      </td>
    </tr>
  <?php endwhile; ?>

<?php elseif ($object == 10) : // Электронная почта ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="250px">Имя ящика</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>
      </td>
      <td align="center">
        <a href="e-mail.php?mail_id=<?= $result["id"] ?>"><?= strtolower($result["name"]) ?></a>
      </td>
    </tr>
  <?php endwhile; ?>

<?php elseif ($object == 144 or $object == 145) : // Совпадения за сутки ?>
  <tr class="table_head">
    <th width="30px">№<br/>п/п</th>
    <th width="150px"><?php if($object == 144) echo 'Телефон'; else echo 'IMEI' ?></th>
    <th>ОВД</th>
    <th>Номер КУСП</th>
    <th>Рег.КУСП</th>
    <th>Номер УД</th>
    <th>Дата УД</th>
	<th>Дата ввода</th>
    <th width="350px">Фабула</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= $i++ ?>.
      </td>
      <td align="center">
        <?= $result["number"] ?>
      </td>
      <td align="center">
        <?= $result["ovd"] ?>
      </td>
      <td align="center">
        <a href="event.php?event_id=<?= $result["id"] ?>"><?= $result["kusp_num"] ?></a>
      </td>
      <td align="center">
        <?= $result["kusp_date"] ?>
      </td>
      <td align="center">
        <a href="event.php?event_id=<?= $result["id"] ?>"><?= $result["decision_number"] ?></a>
      </td>
      <td align="center">
        <?= $result["decision_date"] ?>
	  </td>
	  <td align="center">
        <?= $result["create_date"] ?>
	  </td>
	   <td align="center">
        <?= $result["fab"] ?>
      </td>
    </tr>
  <?php endwhile; ?>
  
<?php endif;
?>
</table>
<?php
require_once('footer.php');
?>
</body>
</html>
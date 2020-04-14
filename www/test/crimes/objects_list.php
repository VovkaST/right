<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if ((!isset($_GET['object'])) || (isset($_GET['object']) && !is_numeric($_GET['object']))) {
  header('Location: index.php');
}
$object = floor(abs($_GET['object']));
if ($object > 10) header('Location: index.php');
require_once(KERNEL.'connection.php');
switch ($object) {
  case 1:
    $query = mysql_query('
      SELECT
        `id`, `surname`, `name`, `fath_name`, DATE_FORMAT(`borth`, "%d.%m.%Y") as `borth`
      FROM
        `o_lico`
      ORDER BY
        `surname`, `name`, `fath_name`, `borth`
    ') or die(mysql_error());
    break;
  
  case 2:
    $query = mysql_query('
      SELECT
        so.`ovd`, e.`ovd_id`,
        e.`id`, e.`kusp_num`,
        DATE_FORMAT(e.`kusp_date`, "%d.%m.%Y") as `kusp_date`, 
        e.`decision_number`,
        DATE_FORMAT(e.`decision_date`, "%d.%m.%Y") as `decision_date`,
        su.`st`
      FROM
        `o_event` as e
      LEFT JOIN
        `spr_ovd` as so ON
          so.`id_ovd` = e.`ovd_id`
      LEFT JOIN
        `spr_uk` as su ON
          su.`id_uk` = e.`article_id`
      ORDER BY
        e.`kusp_date` DESC
    ') or die(mysql_error());
    break;
    
  case 3:
    $query = mysql_query('
      SELECT
        a.id,
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
        CONCAT("д.", IF(a.`house_lit` IS NOT NULL, CONCAT(a.`house`,"/",a.`house_lit`), a.`house`)) as `house`,
        CONCAT("кв.", IF(a.`flat_lit` IS NOT NULL, CONCAT(a.`flat`,"/",a.`flat_lit`), a.`flat`)) as `flat`
      FROM
        o_address as a
      LEFT JOIN
        `spr_region` as reg ON
          reg.`id` = a.`region`
      LEFT JOIN
        `spr_district` as dist ON
          dist.`id` = a.`district`
      LEFT JOIN
        `spr_city` as city ON
          city.`id` = a.`city`
      LEFT JOIN
        `spr_locality` as loc ON
          loc.`id` = a.`locality`
      LEFT JOIN
        `spr_street` as str ON
          str.`id` = a.`street`
      ORDER BY
        a.`region`, a.`district`, a.`city`, a.`locality`, a.`street`, a.`house`, a.`house_lit`, a.`flat`, a.`flat_lit`
    ') or die(mysql_error());
    break;

  case 4:
    $query = mysql_query('
      SELECT
        d.`id`, d.`number`, d.`serial`,
        sd.`type`
      FROM
        `o_documents` as d
      LEFT JOIN
        `spr_documents` as sd ON
          sd.`id` = d.`type`
    ') or die(mysql_error());
    break;
  
  case 5:
    $query = mysql_query('
      SELECT
        d.`id`, d.`IMEI`, d.`model`
      FROM
        `o_mobile_device` as d
    ') or die(mysql_error());
    break;
  
  case 6:
    $query = mysql_query('
      SELECT
        t.`id`, t.`number`, o.`operator`, opr.`region`
      FROM
        `o_telephone` as t
      LEFT JOIN
        `l_operators_ranges` as opr ON
          opr.`id` = t.`operator_range`
      LEFT JOIN
        `l_operators` as o ON
          o.`id` = t.`operator`
    ') or die(mysql_error());
    break;
  
  case 7:
    $query = mysql_query('
      SELECT
        `id`, `number`, `bank`
      FROM
        `o_bank_account`
    ') or die(mysql_error());
    break;

  case 8:
    $query = mysql_query('
      SELECT
        c.`id`, c.`number`, c.`bank`
      FROM
        `o_bank_card` as c
    ') or die(mysql_error());
    break;
    
  case 9:
    $query = mysql_query('
      SELECT
        w.`id`, w.`number`,
        sp.`system`
      FROM
        `o_wallet` as w
      LEFT JOIN
        `spr_payment` as sp ON
          sp.`id` = w.`payment_system`
    ') or die(mysql_error());
    break;
  
  case 10:
    $query = mysql_query('
      SELECT
        m.`id`, m.`name`
      FROM
        `o_mail` as m
    ') or die(mysql_error());
    break;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Отдел оперативно-разыскной информации</title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <script type="text/javascript" src="<?= JS ?>jquery-1.10.2.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="js/procedures.js"></script> 
</head>
<body>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
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
        <?= $i++ ?>.
      </td>
      <td align="center">
        <a href="e-mail.php?mail_id=<?= $result["id"] ?>"><?= strtolower($result["name"]) ?></a>
      </td>
    </tr>
  <?php endwhile; ?>

<?php endif;
?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
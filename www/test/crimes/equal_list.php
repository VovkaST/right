<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if ((!isset($_GET['object'])) || (isset($_GET['object']) && !is_numeric($_GET['object']))) {
  header('Location: index.php');
}
$object = floor(abs($_GET['object']));
if ($object > 10) header('Location: index.php');
require_once(KERNEL.'connection.php');
$temp_tbl = '
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
';
switch ($object) {
  case 1:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`,
        l.`surname`, l.`name`, l.`fath_name`, DATE_FORMAT(l.`borth`, "%d.%m.%Y") as `borth`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_lico` as l ON
          l.`id` = `equals`.`obj_id1`
      WHERE
        `type_id1` = 1
      GROUP BY
        l.`id`
    ') or die(mysql_error());
    break;

  case 4:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`, d.`number`, d.`serial`, sd.`type`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_documents` as d ON
          d.`id` = `equals`.`obj_id1`
      LEFT JOIN
        `spr_documents` as sd ON
          sd.`id` = d.`type`     
      WHERE
        `type_id1` = 4
      GROUP BY
        d.`id`
    ') or die(mysql_error());
    break;
  
  case 5:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`, d.`IMEI`, d.`model`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_mobile_device` as d ON
          d.`id` = `equals`.`obj_id1`
      WHERE
        `type_id1` = 5
      GROUP BY
        d.`id`
    ') or die(mysql_error());
    break;
  
  case 6:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`, t.`number`, o.`operator`, opr.`region`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_telephone` as t ON
          t.`id` = `equals`.`obj_id1`
      LEFT JOIN
        `l_operators_ranges` as opr ON
          opr.`id` = t.`operator_range`
      LEFT JOIN
        `l_operators` as o ON
          o.`id` = t.`operator`
      WHERE
        `type_id1` = 6
      GROUP BY
        t.`id`
    ') or die(mysql_error());
    break;
  
  case 7:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`, b.`number`, b.`bank`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_bank_account` as b ON
          b.`id` = `equals`.`obj_id1`
      WHERE
        `type_id1` = 7
      GROUP BY
        b.`id`
    ') or die(mysql_error());
    break;

  case 8:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`, c.`number`, c.`bank`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_bank_card` as c ON
          c.`id` = `equals`.`obj_id1`
      WHERE
        `type_id1` = 8
      GROUP BY
        c.`id`
    ') or die(mysql_error());
    break;
    
  case 9:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`, w.`number`,
        sp.`system`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_wallet` as w ON
          w.`id` = `equals`.`obj_id1`
      LEFT JOIN
        `spr_payment` as sp ON
          sp.`id` = w.`payment_system`
      WHERE
        `type_id1` = 9
      GROUP BY
        w.`id`
    ') or die(mysql_error());
    break;
  
  case 10:
    $query = mysql_query('
      SELECT
        `equals`.`obj_id1` as `id`, m.`name`
      FROM
        ('.$temp_tbl.') as `equals`
      JOIN
        `o_mail` as m ON
          m.`id` = `equals`.`obj_id1`
      WHERE
        `type_id1` = 10
      GROUP BY
        m.`id`
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
<center><span style="font-size: 1.2em;"><strong>Список объектов "<?= current_object($object) ?>", имеющих более 1 связи с другими объектами</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
<?php
$i = 1;
if ($object == 1) : // лица ?>
  <tr class="table_head">
    <th>№<br/>п/п</th>
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
        <a href="event.php?event_id=<?= $result["id"] ?>"><?= $result["crim_case"] ?></a>
      </td>
      <td align="center">
        <?= $result["crim_case_date"] ?>
      </td>
      <td align="center">
        <?= $result["st"] ?>
      </td>
    </tr>
  <?php endwhile; ?>
  
<?php elseif ($object == 4) : // документ ?>
  <tr class="table_head">
    <th>№<br/>п/п</th>
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
    <th>№<br/>п/п</th>
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
    <th>№<br/>п/п</th>
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
    <th>№<br/>п/п</th>
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
    <th>№<br/>п/п</th>
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
    <th>№<br/>п/п</th>
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
    <th>№<br/>п/п</th>
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
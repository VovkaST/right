<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require_once(KERNEL.'connection.php');
$query_1 = mysql_query('
  SELECT
    w.`id`, w.`number`,
    sp.`system`,
    r.`request_number`,
    DATE_FORMAT(r.`request_date`, "%d.%m.%Y") as `request_date`
  FROM
    `o_wallet` as w
  LEFT JOIN
    `spr_payment` as sp ON
      sp.`id` = w.`payment_system`
  LEFT JOIN
    `l_requests` as r on
      r.`obj_id` = w.`id` AND
      r.`obj_type` = 9
  WHERE
    w.`payment_system` = 13 AND
    (r.`request_number` IS NULL OR r.`request_date` IS NULL)
') or die(mysql_error()); // кошельки с ненаправленными запросами

$query_2 = mysql_query('
  SELECT
    w.`id`, w.`number`,
    sp.`system`,
    r.`request_number`,
    DATE_FORMAT(r.`request_date`, "%d.%m.%Y") as `request_date`,
    r.`response_number_in`, r.`response_number_out`,
    DATE_FORMAT(r.`response_date`, "%d.%m.%Y") as `response_date`
  FROM
    `o_wallet` as w
  LEFT JOIN
    `spr_payment` as sp ON
      sp.`id` = w.`payment_system`
  LEFT JOIN
    `l_requests` as r on
      r.`obj_id` = w.`id` AND
      r.`obj_type` = 9
  WHERE
    w.`payment_system` = 13 AND
    (r.`request_number` IS NOT NULL OR r.`request_date` IS NOT NULL) AND
    ((r.`response_number_in` IS NULL OR r.`response_number_out` IS NULL) OR r.`response_date` IS NULL)
') or die(mysql_error()); // кошельки без ответов
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
  <link rel="stylesheet" href="css/new_tmp.css">
  <script type="text/javascript" src="<?= JS ?>jquery-1.10.2.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="<?= JS ?>functions.js"></script>
  <script type="text/javascript" src="js/procedures.js"></script>
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
<center><span style="font-size: 1.2em;"><strong>Запросы по QIWI-кошелькам</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<?php if (mysql_num_rows($query_1) > 0) : ?>
<form class="QIWI_request">
  <input type="hidden" name="data_form" value="form_request"/>
  <center><h4>Ненаправлены запросы</h4></center>
  <table rules="all" border="1" cellpadding="3" rules="all" class="result_table">
    <tr class="table_head">
      <th width="40px">№<br/>п/п</th>
      <th width="110px">№ кошелька</th>
      <th width="120px" colspan="2">Система</th>
    </tr>
    <?php 
    $i = 1;
    while($result = mysql_fetch_assoc($query_1)) : ?>
      <tr>
        <td align="center">
          <?= $i++ ?>.
        </td>
        <td align="center">
          <a href="e-wallet.php?wall_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
        </td>
        <td align="center" style="border-right: none;">
          <?= $result["system"] ?>
        </td>
        <?php if ($oori) : ?>
        <td align="center" width="20px" style="border-left: none;"><input type="checkbox" name="wallets[<?= $result["id"] ?>]" value="1"/></td>
        <?php endif; ?>
      </tr>
    <?php endwhile; ?>
  </table>
  <?php if ($oori) : ?>
  <div class="create_request" style="text-align: right;"><?= save_button('Сформировать запрос для помеченных...') ?></div>
  <?php endif; ?>
</form>
<?php endif; ?>

<?php if (mysql_num_rows($query_2) > 0) : ?>
  <center><h4>Не получены ответы</h4></center>
  <table rules="all" border="1" cellpadding="3" rules="all" class="result_table">
    <tr class="table_head">
      <th width="40px" rowspan="2">№<br/>п/п</th>
      <th width="110px" rowspan="2">№ кошелька</th>
      <th width="100px" rowspan="2">Система</th>
      <th colspan="2">Исходящий</th>
      <th colspan="3">Входящий</th>
    </tr>
    <tr class="table_head">
      <th width="60px">рег.№</th>
      <th width="80px">дата</th>
      <th width="60px">рег.№</th>
      <th width="60px">вх.№</th>
      <th width="80px">дата</th>
    </tr>
    <?php 
    $i = 1;
    while($result = mysql_fetch_assoc($query_2)) : ?>
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
        <td align="center">
          <?= $result["request_number"] ?>
        </td>
        <td align="center">
          <?= $result["request_date"] ?>
        </td>
        <td align="center">
          <?= $result["response_number_in"] ?>
        </td>
        <td align="center">
          <?= $result["response_number_out"] ?>
        </td>
        <td align="center">
          <?= $result["response_date"] ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
<?php endif; ?>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
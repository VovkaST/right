<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(1);

require_once(KERNEL.'connection.php');
$query_1 = mysql_query('
  SELECT
    w_rel.`obj` as id,
    IF(c.`bank` IS NULL, "-", c.`bank`) as `bank`, c.`number`
  FROM
    (SELECT
      DISTINCT CASE
        WHEN r.`from_obj_type` = 7 THEN r.`from_obj`
        WHEN r.`to_obj_type` = 7 THEN r.`to_obj`
      END as `obj`
    FROM
      `l_relatives` as r
    WHERE
      r.`ais` = 1 AND
      (CASE
        WHEN r.`from_obj_type` = 7 THEN r.`from_obj`
        WHEN r.`to_obj_type` = 7 THEN r.`to_obj`
      END) IS NOT NULL
    ) as w_rel
  LEFT JOIN
    `l_relatives` as rel ON
      rel.`from_obj_type` = 1 AND
      rel.`to_obj_type` = 7 AND
      rel.`to_obj` = w_rel.`obj`
  LEFT JOIN
    `o_bank_account` as c ON
      c.`id` = w_rel.`obj`
  LEFT JOIN
    `l_requests` as r on
      r.`obj_id` = c.`id` AND
      r.`obj_type` = 7
  WHERE
    (r.`request_number` IS NULL OR r.`request_date` IS NULL) AND
    rel.`from_obj` IS NULL
') or die(mysql_error()); // с ненаправленными запросами

$query_2 = mysql_query('
  SELECT
    w_rel.`obj` as id,
    IF(c.`bank` IS NULL, "-", c.`bank`) as `bank`, c.`number`,
    r.`request_number`,
    DATE_FORMAT(r.`request_date`, "%d.%m.%Y") as `request_date`
  FROM
    (SELECT
      DISTINCT CASE
        WHEN r.`from_obj_type` = 7 THEN r.`from_obj`
        WHEN r.`to_obj_type` = 7 THEN r.`to_obj`
      END as `obj`
    FROM
      `l_relatives` as r
    WHERE
      r.`ais` = 1 AND
      (CASE
        WHEN r.`from_obj_type` = 7 THEN r.`from_obj`
        WHEN r.`to_obj_type` = 7 THEN r.`to_obj`
      END) IS NOT NULL
    ) as w_rel
  LEFT JOIN
    `o_bank_account` as c ON
      c.`id` = w_rel.`obj`
  LEFT JOIN
    `l_requests` as r on
      r.`obj_id` = c.`id` AND
      r.`obj_type` = 7
  WHERE
    (r.`request_number` IS NOT NULL OR r.`request_date` IS NOT NULL) AND
    ((r.`response_number_in` IS NULL AND r.`response_number_out` IS NULL) OR r.`response_date` IS NULL)
  ORDER BY
    c.`number`
') or die(mysql_error()); // без ответов
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
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
</div>
<center><span style="font-size: 1.2em;"><strong>Запросы по неустановленным банковским счетам</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<?php if (mysql_num_rows($query_1) > 0) : ?>
  <input type="hidden" name="data_form" value="form_request"/>
  <center><h4>Ненаправлены запросы</h4></center>
  <table rules="all" border="1" cellpadding="3" rules="all" class="result_table">
    <tr class="table_head">
      <th width="40px">№<br/>п/п</th>
      <th width="110px">Банк</th>
      <th width="120px">Карта</th>
    </tr>
    <?php 
    $i = 1;
    while($result = mysql_fetch_assoc($query_1)) : ?>
      <tr>
        <td align="center">
          <?= $i++ ?>.
        </td>
        <td align="center">
          <?= $result["bank"] ?>
        </td>
        <td align="center" >
          <a href="bank_account.php?acc_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
<?php endif; ?>

<?php if (mysql_num_rows($query_2) > 0) : ?>
  <center><h4>Не получены ответы</h4></center>
  <table rules="all" border="1" cellpadding="3" rules="all" class="result_table">
    <tr class="table_head">
      <th width="40px" rowspan="2">№<br/>п/п</th>
      <th width="130px" rowspan="2">Банк</th>
      <th width="180px" rowspan="2">Карта</th>
      <th colspan="2">Исходящий</th>
    </tr>
    <tr class="table_head">
      <th width="80px">рег.№</th>
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
          <?= $result["bank"] ?>
        </td>
        <td align="center">
          <a href="bank_account.php?acc_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
        </td>
        <td align="center">
          <?= $result["request_number"] ?>
        </td>
        <td align="center">
          <?= $result["request_date"] ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
<?php endif; ?>
<?php
require_once('footer.php');
?>
</body>
</html>
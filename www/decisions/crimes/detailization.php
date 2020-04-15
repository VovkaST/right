<?php
$need_auth = 0;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

function telephone($id) {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      t.`id`, t.`number`, t.`operator`, t.`operator_range`
    FROM
      `o_telephone` as t
    WHERE
      t.`id` = '.$id
  );
  if (mysql_num_rows($query) > 0) {
    $result = mysql_fetch_assoc($query);
    return $result;
  } else {
    return false;
  }
}

if (!isset($_GET["tel_id"]) || !is_numeric($_GET["tel_id"])) {
  header('Location: index.php');
}

$tel_id = floor(abs($_GET["tel_id"]));
$tel = telephone($tel_id);
$par_str[] = 'аб.номер - '.$tel['number'];

if (isset($_GET['direction'])) {
  switch($_GET['direction']) {
    case 'incoming': 
      $req_str[] = 'det.`subscriber_2` = '.$tel_id;
      $par_str[] = 'входящие';
      break;
    case 'outgoing':
      $req_str[] = 'det.`subscriber_1` = '.$tel_id;
      $par_str[] = 'исходящие';
      break;
    case 'all':
      $req_str[] = 'det.`subscriber_1` = '.$tel_id.' OR det.`subscriber_2` = '.$tel_id;
      $par_str[] = 'все соединения';
      break;
  }
}

$filter = '';
if (isset($par_str)) {
  $filter = '(Ограничения: '.implode(', ', $par_str).')';
}

$query = mysql_query('
  SELECT
    det.`subscriber_1` as `subscriber_id_1`, t_1.`number` as `subscriber_1`,
    det.`subscriber_2` as `subscriber_id_2`, t_2.`number` as `subscriber_2`,
    CASE
      WHEN det.`type` = 1 THEN "Тел.соединение"
      WHEN det.`type` = 2 THEN "SMS"
    END as `type`,
    DATE_FORMAT(det.`connection_date`, "%d.%m.%Y") as `connection_date`, 
    DATE_FORMAT(det.`connection_time`, "%H:%i") as `connection_time`, 
    det.`connection_length`
  FROM
    `l_detailizations` as det
  JOIN
    `o_telephone` as t_1 ON
      t_1.`id` = det.`subscriber_1`
  JOIN
    `o_telephone` as t_2 ON
      t_2.`id` = det.`subscriber_2`
  WHERE
    '.implode(' AND ', $req_str)
) or die('Error: '.mysql_error());

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
  <link rel="stylesheet" href="css/new_tmp.css">
</head>
  <!--[if IE]>
  <link rel="stylesheet" href="<?= CSS ?>ie_fix.css">
  <![endif]-->
<body>
<?php
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
</div>
<center><span style="font-size: 1.2em;"><strong>Детализация телефонных соединений</strong><br/><i><?= $filter ?></i></span></center>
<hr color="#C6C6C6" size="0px"/>

<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th rowspan="2">№<br/>п/п</th>
    <th rowspan="2">Абонент 1</br>(исходящий)</th>
    <th rowspan="2">Абонент 2</br>(входящий)</th>
    <th colspan="4">Соединение</th>
  </tr>
  <tr class="table_head">
    <th>Тип</th>
    <th>Дата</th>
    <th>Время</th>
    <th>Продолж.</th>
  </tr>
<?php 
  $cntr = 1;
  while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center"><?= $cntr ?>.</td>
      <td align="center" width="110px"><a href="telephone.php?tel_id=<?= $result['subscriber_id_1'] ?>"><?= $result['subscriber_1'] ?></a></td>
      <td align="center" width="110px"><a href="telephone.php?tel_id=<?= $result['subscriber_id_2'] ?>"><?= $result['subscriber_2'] ?></a></td>
      <td align="center" width="120px"><?= $result['type'] ?></td>
      <td align="center" width="90px"><?= $result['connection_date'] ?></td>
      <td align="center" width="70px"><?= $result['connection_time'] ?></td>
      <td align="center" width="70px"><?= $result['connection_length'] ?> сек.</td>
    </tr>
  <?php 
  $cntr++;
  endwhile; ?>
</table>

<iframe name="frame" width="100%" style="display: none"></iframe>
<?php
require_once('footer.php');
//mysql_close($db);
?>
</body>
</html>
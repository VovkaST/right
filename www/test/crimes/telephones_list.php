<?php
$need_auth = 1;
$object = 6;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

//$req_str = array();
$par_str[] = 'номер мошенника';

//---- регион ----//
if (isset($_GET['region']) && (is_numeric($_GET['region']) || $_GET['region'] == '')) {
  if ($_GET['region']) {
    $req_str[] = 't.`region` = (SELECT r.`region` FROM `l_operators_ranges` as r WHERE r.`id` = '.$_GET['region'].')';
    $par_str[] = 'регион - '.tel_region($_GET['region']);
  } else {
    $req_str[] = 't.`region` IS NULL';
    $par_str[] = 'регион - не установлен';
  }
}

//---- способ ----//
if (isset($_GET['marking_id']) && is_numeric($_GET['marking_id'])) {
  $req_str[] = 'e.`marking_id` = '.$_GET['marking_id'];
  $par_str[] = 'способ - '.marking_type($_GET['marking_id']);
}

//---- статья УК РФ ----//
if (isset($_GET['article'])) {
  switch($_GET['article']) {
    case '158':
      $req_str[] = 'su.`st` LIKE "'.$_GET['article'].'%"';
      break;
    case '159':
      $req_str[] = 'su.`st` NOT LIKE "158%"';
      break;
  }
  $par_str[] = 'ст.'.$_GET['article'].' УК РФ';
}

//---- IMSI номер сим.карты ----//
if (isset($_GET['imsi']) && is_numeric($_GET['imsi'])) {
  $req_str[] = 'im.`IMSI` = "'.$_GET['imsi'].'"';
  $par_str[] = 'imsi - '.$_GET['imsi'];
}

//---- Номера мошенника без лица, на которое он зарегистрирован ----//
if (isset($_GET['owner']) && $_GET['owner'] == 'null') {
  $req_str[] = 'rel.`from_obj` IS NOT NULL AND rel_l.`from_obj` IS NULL AND (t.`note` NOT LIKE "%принадлежит%" OR t.`note` IS NULL)';
  $par_str[] = 'владелец не установлен';
}

$req_str = (isset($req_str)) ? ' AND '.implode(' AND ', $req_str) : ''; // собираем строку с условиями
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    t.`id`, t.`number`, t.`operator`, t.`note`,
    COUNT(DISTINCT e.`id`) as e_cnt
  FROM
    (SELECT
       t.`id`, t.`number`, o.`operator`, r.`region`, r.`id` as `range`, t.`note`
     FROM
       `o_telephone` as t
     LEFT JOIN
       `l_operators_ranges` as r ON
         r.`id` = t.`operator_range`
     LEFT JOIN
       `l_operators` as o ON
         o.`id` = t.`operator`
     ) as t 
  LEFT JOIN
    `l_relatives` as rel ON
      rel.`to_obj` = t.`id` AND
      rel.`type` = 12
  LEFT JOIN
    `o_event` as e ON
      e.`id` = rel.`from_obj`
    JOIN
      `spr_uk` as su ON
        su.`id_uk` = e.`article_id`
  LEFT JOIN
    `l_relatives` as rel_l ON
      rel_l.`to_obj` = t.`id` AND
      rel_l.`type` = 28
  LEFT JOIN
    `l_imsi` as im ON
      im.`telephone` = t.`id`
  WHERE
    rel.`type` = 12 
    '.$req_str .'
  GROUP BY
    t.`number`
  ORDER BY
    `number`
') or die(mysql_error());
$filter = '';
$i = 0;
if (isset($par_str)) {
  $filter = '(<b>Ограничения</b>: '.implode(', ', $par_str).')';
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
<style>
</style>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong><br/><i><?= $filter ?></i></span></center>
<hr color="#C6C6C6" size="0px"/>
<table rules="all" border="1" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="50px">№<br/>п/п</th>
    <th width="100px">Абонентский номер<br/>&darr;</th>
    <th width="300px">Оператор</th>
    <th width="200px">Примечание</th>
    <th width="60px">Связ.<br/>прест.</th>
  </tr>
  <?php while($result = mysql_fetch_assoc($query)) : ?>
    <tr>
      <td align="center">
        <?= ++$i ?>.
      </td>
      <td align="center">
        <a href="telephone.php?tel_id=<?= $result["id"] ?>"><?= $result["number"] ?></a>
      </td>
      <td>
        <?= $result["operator"] ?>
      </td>
      <td>
        <?= $result["note"] ?>
      </td>
      <td align="center">
        <?php if($result['e_cnt']) : ?>
          <a href="events_list.php?tel_id=<?= $result['id'] ?>"><?= $result['e_cnt'] ?></a>
        <?php else: ?>
          <?= $result['e_cnt'] ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
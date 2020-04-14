<?php
$need_auth = 0;
$object = 2;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

//---- ОВД ----//
if (isset($_GET["ovd_id"])) {
  $ovd_id = $_GET["ovd_id"];
  $ovd = getOvdName($ovd_id);
  $req_str[] = 'e.`ovd_id` = '.$ovd_id;
  $par_str[] = 'ОВД - '.$ovd[0];
}

//---- способ ----//
if (isset($_GET['marking_id']) && is_numeric($_GET['marking_id'])) {
  $req_str[] = 'e.`marking_id` = '.$_GET['marking_id'];
  $par_str[] = 'способ - '.marking_type($_GET['marking_id']);
}

//---- телефон ----//
if (isset($_GET['tel_id']) && is_numeric($_GET['tel_id'])) {
  $req_str[] = 'rel.`to_obj` = '.$_GET['tel_id'];
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

//---- служба ----//
if (isset($_GET['service'])) {
  switch($_GET['service']) {
    case '5': // дознание
      $req_str[] = 'su.`st` LIKE "%ч.1"';
      $par_str[] = 'служба - дознание';
      break;
    case '6': // следствие
      $req_str[] = 'su.`st` NOT LIKE "%ч.1"';
      $par_str[] = 'служба - следствие';
      break;
  }
}

//---- период ВУД ----//
if (isset($_GET['differ'])) {
  if ((strpos($_GET['differ'], '<') !== false) || (strpos($_GET['differ'], '>') !== false)) {
    $req_str[] = 'DATEDIFF(e.`decision_date`, e.`kusp_date`) '.$_GET['differ'].' AND e.`decision` = 2';
    if (strpos($_GET['differ'], '<') >= 0) {
      $par_str[] = 'ВУД до '.str_replace('<', '', $_GET['differ']).' суток';
    } else {
      $par_str[] = 'ВУД более '.str_replace('<', '', $_GET['differ']).' суток';
    }
  }
  elseif (strpos($_GET['differ'], ',') !== false) {
    $period = explode(',', $_GET['differ']);
    if (count($period) == 2) {
      $req_str[] = 'DATEDIFF(e.`decision_date`, e.`kusp_date`) BETWEEN '.$period[0].' AND '.$period[1].' AND e.`decision` = 2';
      $par_str[] = 'ВУД от '.$period[0].' до '.$period[1].' суток';
    }
  }
}

//---- раскрыто ----//
if (isset($_GET['disclosed'])) {
  if($_GET['disclosed'] == 'true') {
    $req_str[] = 'e.`disclose_date` IS NOT NULL';
    $par_str[] = 'раскрыто';
  }
}

//---- в производстве ----//
if (isset($_GET['procedure'])) {
  if($_GET['procedure'] == 'true') {
    if (isset($_GET['service'])) {
      switch($_GET['service']) {
        case '5': // дознание
          $req_str[] = 'e.`disclose_date` IS NULL';
          $req_str[] = 'e.`decision_date` > DATE_SUB(current_date, INTERVAL 1 MONTH) AND e.`decision` = 2';
          $par_str[] = 'в производстве';
          break;
        case '6': // следствие
          $req_str[] = 'e.`disclose_date` IS NULL';
          $req_str[] = 'e.`decision_date` > DATE_SUB(current_date, INTERVAL 2 MONTH) AND e.`decision` = 2';
          $par_str[] = 'в производстве';
          break;
      }
    }
  }
}

require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    e.`id`, e.`kusp_num`,
    DATE_FORMAT(e.`kusp_date`, "%d.%m.%Y") as `kusp_date`, 
    e.`decision_number`,
    DATE_FORMAT(e.`decision_date`, "%d.%m.%Y") as `decision_date`,
    su.`st`, e.`story`
  FROM
    `o_event` as e
  JOIN
    `spr_uk` as su ON
      su.`id_uk` = e.`article_id`
  LEFT JOIN
    `l_relatives` as rel ON
      rel.`from_obj` = e.`id` AND
      rel.`type` = 12
  LEFT JOIN
    `o_telephone` as t ON
      t.`id` = rel.`to_obj` AND
      rel.`to_obj_type` = 6
  WHERE
    '.implode(' AND ', $req_str).'
  GROUP BY
    e.`id`
  ORDER BY
    e.`kusp_date`
') or die(mysql_error());

$filter = '';
if (isset($par_str)) {
  $filter = '(Ограничения: '.implode(', ', $par_str).')';
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>...</title>
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
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Анализ...
</div>
<center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?> (список)</strong><br/><i><?= $filter ?></i></span></center>
<hr color="#C6C6C6" size="0px"/>
<table rules="all" border="1" cols="7" width="100%" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th rowspan="2" width="30px">№<br/>п/п</th>
    <th colspan="2">КУСП</th>
    <th colspan="2">уг.дело</th>
    <th width="50px" rowspan="2">ст.<br/>УК РФ</th>
    <th rowspan="2">фабула</th>
  </tr>
  <tr class="table_head">
    <th width="50px">рег.№</th>
    <th width="70px">&darr; дата рег.</th>
    <th width="50px">рег.№</th>
    <th width="70px">дата рег.</th>
  </tr>
  <?php
  $i = 1;
  while ($result = mysql_fetch_assoc($query)) : ?>
  <tr>
    <td align="center">
      <?= $i++ ?>.
    </td>
    <td align="center">
      <a href="<?= CRIMES ?>event.php?event_id=<?= $result["id"] ?>"><?= $result["kusp_num"] ?></a>
    </td>
    <td align="center">
      <?= $result["kusp_date"] ?>
    </td>
    <td align="center">
      <a href="<?= CRIMES ?>event.php?event_id=<?= $result["id"] ?>"><?= $result["decision_number"] ?></a>
    </td>
    <td align="center">
      <?= $result["decision_date"] ?>
    </td>
    <td align="center">
      <?= $result["st"] ?>
    </td>
    <td>
      <?= $result["story"] ?>
    </td>
  </tr>
  <?php endwhile; ?>
  <?php if(isset($ovd_id)) : ?>
  <tr>
    <td align="center">-</td>
    <td colspan="6">
      <a href="<?= CRIMES ?>event.php?ovd_id=<?= $ovd_id ?>">Добавить...</a>
    </td>
  </tr>
  <?php endif; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));

$object = 2;

require_once(KERNEL.'connection.php');
$yearList = null;
$q_years = mysql_query('
  SELECT
    DISTINCT YEAR(e.`decision_date`) as `year`
  FROM
    `o_event` as e
  WHERE
    e.`decision_date` AND
    e.`ais` = 1 AND
    e.`decision` = 1
');
while($r_years = mysql_fetch_assoc($q_years)) {
  $yearList[] = $r_years['year'];
}

//---- год ----//
if (!empty($_GET["year"])) {
  if (in_array($_GET["year"], $yearList)) {
    $year = $_GET["year"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}
$req_str[] = 'YEAR(e.`create_date`) = '.$year;
$par_str[] = 'Дата ввода - '.$year.' год';

if (!empty($_GET['date_from'])) {
  if (date('Y', strtotime($_GET['date_from'])) != $year) unset($_GET['date_from']);
}

if (!empty($_GET['date_to'])) {
  if (date('Y', strtotime($_GET['date_to'])) != $year) unset($_GET['date_to']);
}

switch (true) {
  case (!empty($_GET['date_from']) and empty($_GET['date_to'])):
    $req_str[] = 'e.`decision_date` >= STR_TO_DATE("'.date('Y-m-d', strtotime($_GET['date_from'])).'", "%Y-%m-%d")';
    $par_str[] = 'Дата возбуждения с '.$_GET['date_from'];
    break;

  case (!empty($_GET['date_from']) and !empty($_GET['date_to'])):
    $req_str[] = '(e.`decision_date` BETWEEN STR_TO_DATE("'.date('Y-m-d', strtotime($_GET['date_from'])).'", "%Y-%m-%d") AND STR_TO_DATE("'.date('Y-m-d', strtotime($_GET['date_to'])).'", "%Y-%m-%d"))';
    $par_str[] = 'Дата возбуждения между '.$_GET['date_from'].' и '.$_GET['date_to'];
    break;

  case (empty($_GET['date_from']) and !empty($_GET['date_to'])):
    if (isset($_GET['date_from'])) unset($_GET['date_from']);
    if (isset($_GET['date_to'])) unset($_GET['date_to']);
    break;
}

//---- АИС ----//
$req_str[] = 'e.`ais` = '.$_SESSION['crime']['ais'];

//---- ОВД ----//
if (isset($_GET["ovd_id"])) {
  $ovd_id = $_GET["ovd_id"];
  $ovd = getOvdName($ovd_id);
  $req_str[] = 'e.`ovd_id` = '.$ovd_id;
  $par_str[] = 'ОВД - '.$ovd[0];
}

//---- способ ----//
if (isset($_GET['marking_id']) && is_numeric($_GET['marking_id'])) {
  $req_str[] = 'om.`marking` = '.$_GET['marking_id'];
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
//---- в производстве ----//
if (isset($_GET['zakladka'])) {
  $req_str[] = 'om.`marking` = 12';
  $par_str[] = 'способ совершения &ndash; "Закладка"';
}
//---- Находка ----//
if (isset($_GET['nahodka'])) {
  $req_str[] = 'om.`marking` = 13';
  $par_str[] = 'способ совершения &ndash; "Находка"';
}
//---- Из рук в руки ----//
if (isset($_GET['iz_ruk'])) {
  $req_str[] = 'om.`marking` = 14';
  $par_str[] = 'способ совершения &ndash; "Из рук в руки"';
}


//---- Сигар.пачка/оболоч ----//
if (isset($_GET['sigaret'])) {
  $req_str[] = 'om.`marking` = 31';
  $par_str[] = 'способ закладки &ndash; "Сигаретная пачка/оболочка"';
}
//---- Жестяная банка ----//
if (isset($_GET['banka'])) {
  $req_str[] = 'om.`marking` = 32';
  $par_str[] = 'способ закладки &ndash; "Жестяная банка"';
}
//---- Спичечный коробок ----//
if (isset($_GET['korobok'])) {
  $req_str[] = 'om.`marking` = 33';
  $par_str[] = 'способ закладки &ndash; "Спичечный коробок"';
}
//---- Почтовое отправление ----//
if (isset($_GET['pochta'])) {
  $req_str[] = 'om.`marking` = 34';
  $par_str[] = 'способ закладки &ndash; "Почтовое отправление"';
}


//---- Zip-lock ----//
if (isset($_GET['zip'])) {
  $req_str[] = 'om.`marking` = 36';
  $par_str[] = 'вид упаковки &ndash; "Zip-lock"';
}
//---- Бумага ----//
if (isset($_GET['bumaga'])) {
  $req_str[] = 'om.`marking` = 37';
  $par_str[] = 'вид упаковки &ndash; "Бумага"';
}
//---- Полимерный пакет ----//
if (isset($_GET['paket'])) {
  $req_str[] = 'om.`marking` = 38';
  $par_str[] = 'вид упаковки &ndash; "Полимерный пакет"';
}


 $q = 'SELECT
   DISTINCT
    e.`id`, e.`kusp_num`,
    DATE_FORMAT(e.`kusp_date`, "%d.%m.%Y") as `kusp_date`,
    e.`decision_number`,
    DATE_FORMAT(e.`decision_date`, "%d.%m.%Y") as `decision_date`,
    su.`st`, e.`story`
  FROM
    `o_event` as e
  LEFT JOIN
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
  LEFT JOIN
    `l_object_marking` as om ON
      om.`object` = e.`id` AND
      om.`obj_type` = 2
  WHERE
    '.implode(' AND ', $req_str).'
   GROUP BY ';
    $q .= ($_SESSION['crime']['ais'] == 2) ? 'e.kusp_num, e.kusp_date' : 'e.decision_number, e.decision_date';
    $q .= ' ORDER BY
    e.`kusp_date`'; 
//file_put_contents('11111.txt', $q);
$query = mysql_query($q) or die(mysql_error());

$filter = '';
if (isset($par_str)) {
  $filter = '(Ограничения: '.implode(', ', $par_str).')';
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
  <link rel="stylesheet" href="/css/head.css">
  <link rel="stylesheet" href="/css/redmond/jquery-ui-1.10.4.custom.css">
  <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
  <script type="text/javascript" src="/js/jquery-ui-1.10.4.custom.js"></script>
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
    <td>
      <?= $result["story"] ?>
    </td>
  </tr>
  <?php endwhile; ?>
  <?php if(isset($ovd_id)) : ?>
  <tr>
    <td align="center">-</td>
    <td colspan="6">
      <a href="event.php?ovd_id=<?= $ovd_id ?>">Добавить...</a>
    </td>
  </tr>
  <?php endif; ?>
</table>
<?php
require_once('footer.php');
?>

</body>
</html>

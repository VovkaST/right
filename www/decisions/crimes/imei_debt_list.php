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
/*
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
*/
//---- АИС ----//
$req_str[] = 'e.`ais` = '.$_SESSION['crime']['ais'];

//---- ОВД ----//
if (isset($_GET["ovd_id"])) {
  $ovd_id = $_GET["ovd_id"];
  $ovd = getOvdName($ovd_id);
  $req_str[] = 'e.`ovd_id` = '.$ovd_id;
  $par_str[] = 'ОВД - '.$ovd[0];
}



 /*$q = "
    select e.`id`, e.`kusp_num`,
    				DATE_FORMAT(e.`kusp_date`, '%d.%m.%Y') as `kusp_date`,
    				e.`decision_number`,
				   DATE_FORMAT(e.`decision_date`, '%d.%m.%Y') as `decision_date`,
				   e.`story`, group_concat(t.number separator '<br />') as `phones`
		from o_event as e
		left join l_relatives as r
		on e.id=r.from_obj and r.from_obj_type=2
		left join o_telephone as t 
		on r.to_obj=t.id and r.to_obj_type=6
		where t.id is not null
		and e.id not in ( select e.id
								from o_event as e
								left join l_relatives as r
								on e.id=r.from_obj and r.from_obj_type=2
								left join o_mobile_device as m 
								on r.to_obj=m.id and r.to_obj_type=5
								where m.id is not null
								group by e.id)
		and e.ovd_id = ".$ovd_id."
    and year(e.decision_date) = ".$year."
    and substring(t.number, 1, 1) = '9'
		group by e.id
 ";*/ 
 
  $q = "
    select * from 
(select 
		 e.id,
		 e.`story`,
		 e.`decision_number`, 
		 DATE_FORMAT(e.`decision_date`, '%d.%m.%Y') as `decision_date`, 
		 e.kusp_num, 
		 DATE_FORMAT(e.kusp_date, '%d.%m.%Y') as `kusp_date`,
		 sum(if(r.to_obj_type=6, 1, 0)) as `phones_count`, 
		 sum(if(r.to_obj_type=5, 1, 0)) as `imei_count`,
		 group_concat(t.`number` separator '<br />') as `phones`
from o_event as e 
left join l_relatives as r
on e.id=r.from_obj 

left join o_telephone as t
on t.id = r.to_obj
and r.to_obj_type = 6
and r.type = 12
and substring(t.number, 1, 1) = '9'
where 
year(e.decision_date) = ".$year."
and e.ovd_id=".$ovd_id."
and r.to_obj_type in (5,6)
group by e.ovd_id, e.decision_number, e.decision_date) as yy
where (yy.phones_count <> 0 or yy.imei_count<>0)
 and yy.imei_count<yy.phones_count
 and yy.phones is not null
"; 
 
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
<center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?> (с телефонными номерами без IMEI)</strong><br/><i><?= $filter ?></i></span></center>
<hr color="#C6C6C6" size="0px"/>
<table rules="all" border="1" cols="7" width="100%" cellpadding="3" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th rowspan="2" width="30px">№<br/>п/п</th>
    <th colspan="2">КУСП</th>
    <th colspan="2">уг.дело</th>
    <!--th width="50px" rowspan="2">ст.<br/>УК РФ</th-->
    <th rowspan="2">фабула</th>
    <th rowspan="2">телефоны</th>
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
    <td>
      <?= $result["story"] ?>
    </td>
    <td align="center">
      <?= $result["phones"] ?>
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

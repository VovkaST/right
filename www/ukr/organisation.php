<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
require ("../../connection_ukr.php");
require ("districtList.php");
$dep = $dep_val = $dep_sel = $position = $range = $officer = $off_tel = $check_date = $check_time = $name_org = $INN = $guide = $tel = $address = $industry = $farming = $forest = $building = $trading = $consumer_service = $transport = $other_ind = $other = $workers_year = $workers_current = $workers_plan = $other = "";
$error = array();
//проверка органа
if (isset($_GET['district'])) {
	$dep = $_GET['district'];
	$dep_val = "value=\"".$dep."\"";
	$dep_sel = "\n<option selected>".$dep."</option>";
}
foreach ($districtList as $district) {
	if ($district) {
		if ($district <> $dep) {
			$list[] = "\n<option>".$district."</option>";
		}
	}
}
$list = implode($list);
//если нажата кнопка "Сформировать"
if (isset($_POST['form'])) {
	//проверка даты проверки
	if (isset($_POST['check_date'])) {
		$check_date = $_POST['check_date'];
		//проверяем на допустимость формата даты
		if (preg_match("|^[0-3]\d\.[01]\d\.[1-2]\d{3}$|", $check_date)) {
			//дата больше текущей
			if ($check_date > date('d.m.Y', strtotime('now'))) {
				$error[] = "Вводимая дата не может быть больше текущей";
			}
		}
		else {
			$error[] = "Вводимая дата не соответствует формату";
		}
	}
	else {
		$check_date = "";
	}
	//проверка должности
	if (isset($_POST['position'])) {$position = $_POST['position'];}
	//проверка звания
	if (isset($_POST['range'])) {$range = $_POST['range'];}
	//проверка сотрудника
	if (isset($_POST['officer'])) {$officer = $_POST['officer'];}
	//проверка телефона
	if (isset($_POST['off_tel'])) {$off_tel = $_POST['off_tel'];}
	//проверка времени
	if (isset($_POST['check_time'])) {$check_time = $_POST['check_time'];}
	//проверка наименования организации
	if (isset($_POST['name_org'])) {$name_org = $_POST['name_org'];}
	//проверка ИНН
	if (isset($_POST['INN'])) {$INN = $_POST['INN'];}
	//проверка руководителя
	if (isset($_POST['guide'])) {$guide = $_POST['guide'];}
	//проверка конт.телефона
	if (isset($_POST['tel'])) {$tel = $_POST['tel'];}
	//проверка факт.адреса
	if (isset($_POST['address'])) {$address = $_POST['address'];}
	//проверка отраслей
	$sql_industry = $sql_farming = $sql_forest = $sql_building = $sql_trading = $sql_consumer_service = $sql_transport = $sql_other_ind = 0;
	if (isset($_POST['industry'])) {
		$industry = "checked";
		$sql_industry = 1;
	}
	if (isset($_POST['farming'])) {
		$farming = "checked";
		$sql_farming = 1;
	}
	if (isset($_POST['forest'])) {
		$forest = "checked";
		$sql_forest = 1;
	}
	if (isset($_POST['building'])) {
		$building = "checked";
		$sql_building = 1;
	}
	if (isset($_POST['trading'])) {
		$trading = "checked";
		$sql_trading = 1;
	}
	if (isset($_POST['consumer_service'])) {
		$consumer_service = "checked";
		$sql_consumer_service = 1;
	}
	if (isset($_POST['transport'])) {
		$transport = "checked";
		$sql_transport = 1;
	}
	if (isset($_POST['other_ind'])) {
		$other_ind = "checked";
		$sql_other_ind = 1;
	}
	//проверка количественных показателей работников
	if (isset($_POST['workers_year'])) {
		$workers_year = $_POST['workers_year'];
	} 
	else {
		$error[] = "Не указано количество работавших с начала года";
	}
	if (isset($_POST['workers_current'])) {
		$workers_current = $_POST['workers_current'];
	} 
	else {
		$error[] = "Не указано количество работающих в настоящее время";
	}
	if (isset($_POST['workers_plan'])) {
		$workers_plan = $_POST['workers_plan'];
	} 
	else {
		$error[] = "Не указано планируемое количество трудоустраиваемых";
	}
	//проверка поля "Прочее"
	if (isset($_POST['other'])) {$other = $_POST['other'];}
	//если все поля заполнены
	if (!count($error)) {
		if ($name_org && $INN && $guide && $tel && $address && $dep && $position && $range && $officer && $off_tel && $check_date && $check_time && $workers_year && $workers_current && $workers_plan) {
			//если нет id организации, добавляем ее
			if (!isset($_GET['org_id'])) {
				mysql_query("
					INSERT INTO 
						organisations(id, name, INN, address, guide, 
						telephone, industry, farming, forest, 
						building, trading, consumer_service, transport, other,
						create_date, create_time, active_id)
					VALUES 
						(NULL, '".mysql_real_escape_string($name_org)."', '".$INN."', '".$address."', '".$guide."', 
						'".$tel."', '".$sql_industry."', '".$sql_farming."', '".$sql_forest."', 
						'".$sql_building."', '".$sql_trading."', '".$sql_consumer_service."', '".$sql_transport."', '".$sql_other_ind."',
						'".date('Y-m-d')."', '".date('H:i:s', time())."', '".$_SESSION['activity_id']."')
				") or $error[] = "Ошибка при добавлении организации: ".mysql_error();
				$id_added_org = mysql_insert_id();
			}
			else {
				$id_added_org = $_GET['org_id'];
			}
			mysql_query("
				INSERT INTO 
					check_org(id, ovd, position, checker_range, name, 
					telephone, check_date, check_time, workers_year, 
					workers_current, workers_plan, other, id_org,
					create_date, create_time, active_id)
				VALUES
					(NULL, '".$dep."', '".$position."', '".$range."', '".$officer."',
					 '".$off_tel."', '".date('Y-m-d', strtotime($check_date))."', '".$check_time."', '".$workers_year."',
					 '".$workers_current."', '".$workers_plan."', '".$other."', '".$id_added_org."',
					 '".date('Y-m-d')."', '".date('H:i:s', time())."', '".$_SESSION['activity_id']."')
			") or $error[] = "Ошибка при добавлении проверки: ".mysql_error();
		if (!count($error)) {
			header("Location: organisationReportView.php?id=".$id_added_org);
		}
		}
		else {
			$error[] = "Не все поля заполнены!";
		}
	}
}

//если пришли с id организации
if (isset($_GET['org_id'])) {
	$org_id = $_GET['org_id'];
	$org_id_link = "&org_id=".$org_id;
	$sql_sel_org = mysql_query("
		SELECT
			name, INN, address, guide, telephone, 
			industry, farming, forest, building, 
			trading, consumer_service, transport, other
		FROM
			organisations
		WHERE
			id = {$org_id}
	");
	while ($org = mysql_fetch_assoc($sql_sel_org)) {
		$name_org = $org['name'];
		$INN = $org['INN'];
		$address = $org['address'];
		$guide = $org['guide'];
		$tel = $org['telephone'];
		if ($org['industry']) {$industry = "checked";}
		if ($org['farming']) {$farming = "checked";}
		if ($org['forest']) {$forest = "checked";}
		if ($org['building']) {$building = "checked";}
		if ($org['trading']) {$trading = "checked";}
		if ($org['consumer_service']) {$consumer_service = "checked";}
		if ($org['transport']) {$transport = "checked";}
		if ($org['other']) {$other_ind = "checked";}
	}
}
else {$org_id_link = "";}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан, прибывших с Украины</title>
 <link rel="icon" href="../images/favicon.ico" type="../image/vnd.microsoft.icon">
 <link rel="stylesheet" href="css/migration.css">
 <link rel="stylesheet" href="../css/main.css">
 <link rel="stylesheet" href="../css/head.css">
 <link rel="stylesheet" href="../css/redmond/jquery-ui-1.10.4.custom.css">
 <script src="../js/jquery-1.10.2.js"></script>
 <script src="../js/jquery-ui-1.10.4.custom.js"></script>
 <script>
	$(function(){
		$("#check_date, #arr_time, #start_time").datepicker()
	});
 </script>
</head>
<body>
<?php
$error = implode(', ', $error);
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
echo "\n<div class=\"breadcrumbs\">";
echo "\n<a href=\"".$index."\">Главная</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"".$accounting."\">Формирование учетов</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"index.php\">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href=\"organisationTotal.php\">Проверено работодателей</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Проверка организации";
echo "\n</div>";
echo <<<HTML
<form action="organisation.php?district={$dep}{$org_id_link}" method="POST">
<table border="0" width="100%">
 <tr>
  <td colspan="6" style="color: red;">
   {$error}
  </td>
 </tr> 
 <tr>
  <td width="12%">Орган</td>
  <td colspan="5">
   <select required="required" style="width: 100%"{$dep_val}>{$dep_sel}
    {$list}
   </select>
  </td>
 </tr>
 <tr>
  <td>Должность</td>
  <td colspan="2"><input required="required" maxLength="128" name="position" style="width: 99%" value="{$position}"/></td>
  <td width="13%">Звание</td>
  <td colspan="2"><input required="required" maxLength="128" name="range" style="width: 99%" value="{$range}"/></td>
 </tr>
 <tr>
  <td>Сотрудник</td>
  <td colspan="2"><input required="required" maxLength="512" name="officer" style="width: 99%" value="{$officer}"/></td>
  <td>Телефон</td>
  <td colspan="2"><input required="required" maxLength="128" name="off_tel" style="width: 99%" value="{$off_tel}"/></td>
 </tr>
 <tr>
  <td>Дата проверки</td>
  <td colspan="2"><input required="required" id="check_date" maxLength="128" name="check_date" value="{$check_date}"/></td>
  <td>Время проверки</td>
  <td colspan="2"><input required="required" maxLength="128" type="time" name="check_time" value="{$check_time}"/></td>
 </tr>
 <tr>
  <td colspan="6" align="center"><b>Данные об организации</b></td>
 </tr>
 <tr>
  <td style="vertical-align:top">
   <tr>
    <td>Наименование:</td>
	<td colspan="2"><input style="width: 99%;" maxlength="128" name="name_org" value="{$name_org}"/></td>
    <td>ИНН:</td>
	<td colspan="2"><input style="width: 99%;" maxlength="12" name="INN" value="{$INN}"/></td>
   </tr>
   <tr>
    <td>Руководитель:</td>
	<td colspan="2"><input style="width: 99%;" maxlength="128" name="guide" value="{$guide}"/></td>
	<td>Конт.телефон:</td>
	<td colspan="2"><input style="width: 99%;" maxlength="10" name="tel" value="{$tel}"/></td>
   </tr>
   <tr>
    <td>Факт.адрес:</td>
	<td colspan="5"><input style="width: 100%;" maxlength="128" name="address" value="{$address}"/></td>
   </tr>
   <tr>
    <td colspan="6"><hr/></td>
   </tr>
   <tr>
    <td colspan="6" align="center">Осуществляет деятельность в сфере(-ах):</td>
   </tr>
   <tr align="center">
    <td colspan="2">
     <label><input type="checkbox" value="industry" name="industry" {$industry}/>Пром.призводство<br/></label>
     <label><input type="checkbox" value="farming" name="farming" {$farming}/>Сельское хозяйство<br/></label>
	</td>
	<td colspan="2">
     <label><input type="checkbox" value="forest" name="forest" {$forest}/>Лесное хозяйство<br/></label>
     <label><input type="checkbox" value="building" name="building" {$building}/>Строительство<br/></label>
	</td>
	<td colspan="2">
     <label><input type="checkbox" value="trading" name="trading" {$trading}/>Торговля<br/></label>
     <label><input type="checkbox" value="consumer_service" name="consumer_service" {$consumer_service}/>Бытовые услуги<br/></label>
	</td>
   </tr>
   <tr align="center">
    <td>
	</td>
   	<td colspan="2">
     <label><input type="checkbox" value="transport" name="transport" {$transport}/>Транспорт<br/></label>
    </td>
   	<td colspan="2">
     <label><input type="checkbox" value="other_ind" name="other_ind" {$other_ind}/>прочие отрасли<br/></label>
    </td>
    <td>
	</td>
   </tr>
   <tr>
    <td colspan="6"><hr/></td>
   </tr>
   <tr>
    <td colspan="6">
	 Всего с начала текущего года работало <input name="workers_year" style="width: 35px; text-align: center;" value="{$workers_year}"/> граждан Украины
	</td>
   </tr>
   <tr>
    <td colspan="6">
	 В настоящее время работает <input name="workers_current" style="width: 35px; text-align: center;" value="{$workers_current}"/> переселенцев
	</td>
   </tr>
   <tr>
    <td colspan="6">
	 В ближайшее время планируется принять <input name="workers_plan" style="width: 35px; text-align: center;" value="{$workers_plan}"/> человек
	</td>
   </tr>
   <tr>
    <td colspan="6">
	 Прочее: <input name="other" style="width: 93%;" value="{$other}"/>
	</td>
   </tr>
   <tr>
    <td colspan="6" align="right"><input type="submit" value="Сформировать" name="form"></td>
   </tr>
  </td>
 </tr>
</table>
</form>
HTML;

require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
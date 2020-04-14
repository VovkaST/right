<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php

require ("districtList.php");
require ("/../../connection_ukr.php");

if (!session_id()){
	
	session_start();
}

if (!isset($_SESSION['address'])){
	
	header('Location: /ukr/face.php');
	die();
} else {
	
	$address = $_SESSION['address'];
	$addressInfo = array('Строка' => $_SESSION['address']);
}

if (!isset($_SESSION['list'])){
	
	header('Location: /ukr/face.php');
	die();
} else {

	$faceIds = $_SESSION['list'];
}

$error = array();

if (isset($_POST['sub']) && ($_POST['sub'] == 'Сформировать')){
	
	if (isset($_POST['sotr'])){
			
		$sotr = $_POST['sotr'];
	}

	if (isset($_POST['check'])){
		
		$checkList = $_POST['check'];
	}
	
	if (isset($sotr['department'], $sotr['dolz'], $sotr['zvan'], $sotr['fio'], $sotr['phone'], $sotr['date'], $sotr['time']) && !empty($sotr['department']) && !empty($sotr['dolz']) && !empty($sotr['zvan']) && !empty($sotr['fio']) && !empty($sotr['phone']) && !empty($sotr['date']) && !empty($sotr['time'])){
	
		if (mysql_query('insert into message (department, адресПроверки, должность, звание, сотрудник, телефон, датаПроверки, времяПроверки, create_date, create_time, active_id) values (\'' . implode("','", array(
			mysql_real_escape_string($sotr['department']),
			$address,
			mysql_real_escape_string($sotr['dolz']),
			mysql_real_escape_string($sotr['zvan']),
			mysql_real_escape_string($sotr['fio']),
			mysql_real_escape_string($sotr['phone']),
			date('Y-m-d', strtotime($sotr['date'])),
			mysql_real_escape_string($sotr['time'])
		)) . '\', \''.date('Y-m-d').'\', \''.date('H:i:s', time()).'\', \''.$_SESSION['activity_id'].'\')')) {

			$messageId = mysql_insert_id();			
		} else {
			
			$messageId = 0;
			$error[] = 'Сообщение. Ошибка при добавлении сотрудника: ' . mysql_error();
		}

		if ($messageId){
			
			$flag = false;
			foreach ($checkList as $faceId => $check){
				
				$raport = $check['report'];
				$raportInfo = $check['info'];
				
				if (isset($raport[1]) && $raport[1]){
					
					$flag = true;
					if (!mysql_query('insert into report (messageId, faceId, сфераТрудовойДеятельности, нарушениеТрудЗак, ОбучениеНЛ, ПланируетОбучатьсяНЛ, ПосещаетДошкольноеУчрНЛ, ПланируетПосещатьДошУчрНЛ, ПроживаетБезЗакПредНЛ, ПроживаетБезОформлДокНЛ, НарушениеЗакона, create_date, create_time, active_id) values (\'' . implode("','", array(
					
						$messageId,
						$faceId,
						mysql_real_escape_string(@$raport[2] . ''),
						@$raport[3] + 0,
						@$raport[4] + 0,
						@$raport[5] + 0,
						@$raport[6] + 0,
						@$raport[7] + 0,
						@$raport[8] + 0,
						@$raport[9] + 0,
						@$raport[10] + 0
					)) . '\', \''.date('Y-m-d').'\', \''.date('H:i:s', time()).'\', \''.$_SESSION['activity_id'].'\')')){
						
						$error[] = 'Сообщение. Ошибка при добавлении проверки: ' . mysql_error();
						$raportId = 0;
					} else {
						
						$raportId = mysql_insert_id();
					};
					
					if ($raportId && (isset($raportInfo['doc']) || isset($raportInfo['addr']) || isset($raportInfo['addr_ukr']) || isset($raportInfo['addr_preb']) || isset($raportInfo['target']) || isset($raportInfo['date_preb']) || isset($raportInfo['date_viezd']) || isset($raportInfo['sogiteli']) || isset($raportInfo['phone']) || isset($raportInfo['other']))){
					
						if (!mysql_query('insert into report_info (reportId,документ,адресПроверки,адресУкраина,адресПребывания,цельПребывания,датаПрибытия,датаВыезда,совместноПроживает,контакныйТелефон,прочее, create_date, create_time, active_id) values (\'' . implode("','", array(
							$raportId,
							mysql_real_escape_string(@$raportInfo['doc']. ''),
							mysql_real_escape_string(@$raportInfo['addr']. ''),
							mysql_real_escape_string(@$raportInfo['addr_ukr']. ''),
							mysql_real_escape_string(@$raportInfo['addr_preb']. ''),
							mysql_real_escape_string(@$raportInfo['target']. ''),
							date('Y-m-d', strtotime(@$raportInfo['date_preb'])). '',
							date('Y-m-d', strtotime(@$raportInfo['date_viezd'])). '',
							mysql_real_escape_string(@$raportInfo['sogiteli']. ''),
							mysql_real_escape_string(@$raportInfo['phone']. ''),
							mysql_real_escape_string(@$raportInfo['other']. '')
						)) . '\', \''.date('Y-m-d').'\', \''.date('H:i:s', time()).'\', \''.$_SESSION['activity_id'].'\')')){
							
							$error[] = 'Сообщение. Ошибка при добавлении дополнительной информации проверки: ' . mysql_error();
							$raportInfoId = 0;
						} else {
							
							$raportInfoId = mysql_insert_id();			
						}
					}
				}
			}
			if (!$flag){
				
				$error[] = 'Сообщение. Отсутствуют данные о проверке';
			}
		}
	} else {
		
		$error[] = 'Сообщение. Отсутствуют данные о сотруднике';
	}
	
	unset($_SESSION['list']);
	unset($_SESSION['address']);
	unset($_SESSION['face']);
	
	if (empty($error)){
		
		die("<br><br>Сообщение успешно добавлено. <a target='_blank' href='/ukr/download_message.php?id={$messageId}'>—качать</a><br><a href='/ukr/index.php'>Новая проверка</a>");
	}
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>ѕроверка граждан прибывших с ”краины</title>
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
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');

echo "\n<div class=\"breadcrumbs\">";
echo "\n<a href=\"".$index."\">Главная</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"".$accounting."\">Формирование учетов</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."Проверка граждан прибывших с Украины";
echo "\n</div>";
$stmt = mysql_query('
	SELECT
		\'\' as ВидПринимающейСтороны,
		\'\' as ОтветственноеЛицо,
		\'\' as "АдресОтветственногоЛица",
		\'\' as НаименованиеПринимающейСтороны,
		\'\' as ФактическийАдресПринимающейСтороны,
		id as faceId,
		ФамилияКириллица,
		ИмяКириллица,
		ОтчествоКириллица,
		ДатаРождения,
		\'\' as ДатаПрибытия,
		\'\' as Цель
	FROM 
		face 
	WHERE 
		id IN (' . implode(',', $faceIds) . ')
	ORDER BY 
		ФамилияКириллица,
		ИмяКириллица,
		ОтчествоКириллица,
		ДатаРождения
');

$list = array();
foreach ($districtList as $v){
	
	if (isset($_POST['sotr']['department']) && ($_POST['sotr']['department'] == $v)){
		
		$list[] = '<option selected="selected">' . $v . '</option>';
	} else {
		
		$list[] = '<option>' . $v . '</option>';
	}
}

$cnt = 0;
echo '<div style="color:red">' . (implode('<br>', @$error)) . '</div><h1>' . $addressInfo['Строка'] . '</h1><form action="/ukr/people_message.php" method="POST">';
echo '<input type="hidden" name="address" value="' . $address . '">';
echo '<table>';
echo '<tr><td>Орган</td><td><select required="required" style="width:700px" name="sotr[department]">' . implode($list) . '</select></td></tr>';
echo '<tr><td>Должность</td><td><input required="required" style="width:700px" maxLength="128" type="text" name="sotr[dolz]" value="' . (isset($_POST['sotr']['dolz']) ? $_POST['sotr']['dolz'] : '') . '"></td></tr>';
echo '<tr><td>Звание</td><td><input required="required" style="width:700px" maxLength="128" type="text" name="sotr[zvan]" value="' . (isset($_POST['sotr']['zvan']) ? $_POST['sotr']['zvan'] : '') . '"></td></tr>';
echo '<tr><td>Сотрудник</td><td><input required="required" style="width:700px" maxLength="512" type="text" name="sotr[fio]" value="' . (isset($_POST['sotr']['fio']) ? $_POST['sotr']['fio'] : '') . '"></td></tr>';
echo '<tr><td>Телефон</td><td><input required="required" style="width:700px" maxLength="128" type="text" name="sotr[phone]" value="' . (isset($_POST['sotr']['phone']) ? $_POST['sotr']['phone'] : '') . '"></td></tr>';
echo '<tr><td>Дата проверки</td><td><input required="required" style="width:700px" id="check_date" maxLength="128" name="sotr[date]" value="' . (isset($_POST['sotr']['date']) ? $_POST['sotr']['date'] : '') . '"></td></tr>';
echo '<tr><td>Время проверки</td><td><input required="required" style="width:700px" maxLength="128" type="time" name="sotr[time]" value="' . (isset($_POST['sotr']['time']) ? $_POST['sotr']['time'] : '') . '"></td></tr>';
echo '</table>';
echo '<table border="1" style="padding:0;margin:0;border-collapse: collapse;border: 1px solid black"><tr><td width="300px" colspan="2">&nbsp;</td><td colspan="10" style="background:url(\'/ukr/head.gif\') no-repeat;width:545px;height:235px"></td></tr>';
while($row = mysql_fetch_assoc($stmt)){

	echo '<tr><td rowspan="2" width="30px" align="center">' . (++$cnt) . '<input type="button" value="..." onclick="var el = document.getElementById(\'info-' . $row['faceId'] . '\'); el.style.display = (el.style.display == \'none\') ? \'\' : \'none\';"></td><td width="270px"><b>' . "{$row['ФамилияКириллица']} {$row['ИмяКириллица']} {$row['ОтчествоКириллица']}<br>" . date('d.m.Y', strtotime($row['ДатаРождения']))  . '</b></td>' . '<td width="43px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][1]"' . (isset($_POST['check'][$row['faceId']]['report'][1]) && $_POST['check'][$row['faceId']]['report'][1] ? ' checked="checked"' : '') . '></td><td width="48px" align="center"><select style="width:20px" name="check[' . $row['faceId'] . '][report][2]"><option' . (!isset($_POST['check'][$row['faceId']]['report'][2]) || empty($_POST['check'][$row['faceId']]['report'][2]) ? ' selected="selected"' : '') . '></option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Промышленного производства') ? ' selected="selected"' : '') . '>Промышленного производства</option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Сельского хозяйства') ? ' selected="selected"' : '') . '>Сельского хозяйства</option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Лесной отрасли') ? ' selected="selected"' : '') . '>Лесной отрасли</option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Строительства') ? ' selected="selected"' : '') . '>Строительства</option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Торговли') ? ' selected="selected"' : '') . '>Торговли</option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Бытового обслуживания') ? ' selected="selected"' : '') . '>Бытового обслуживания</option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Транспорта') ? ' selected="selected"' : '') . '>Транспорта</option><option' . (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'В иной сфере') ? ' selected="selected"' : '') . '>¬ иной сфере</option></select></td><td width="60px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][3]"' . (isset($_POST['check'][$row['faceId']]['report'][3]) && $_POST['check'][$row['faceId']]['report'][3] ? ' checked="checked"' : '') . '></td><td width="46px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][4]"' . (isset($_POST['check'][$row['faceId']]['report'][4]) && $_POST['check'][$row['faceId']]['report'][4] ? ' checked="checked"' : '') . '></td><td width="53px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][5]"' . (isset($_POST['check'][$row['faceId']]['report'][5]) && $_POST['check'][$row['faceId']]['report'][5] ? ' checked="checked"' : '') . '></td><td width="58px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][6]"' . (isset($_POST['check'][$row['faceId']]['report'][6]) && $_POST['check'][$row['faceId']]['report'][6] ? ' checked="checked"' : '') . '></td><td width="64px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][7]"' . (isset($_POST['check'][$row['faceId']]['report'][7]) && $_POST['check'][$row['faceId']]['report'][7] ? ' checked="checked"' : '') . '></td><td width="66px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][8]"' . (isset($_POST['check'][$row['faceId']]['report'][8]) && $_POST['check'][$row['faceId']]['report'][8] ? ' checked="checked"' : '') . '></td><td width="56px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][9]"' . (isset($_POST['check'][$row['faceId']]['report'][9]) && $_POST['check'][$row['faceId']]['report'][9] ? ' checked="checked"' : '') . '></td><td width="51px" align="center"><input type="checkbox" value="1" name="check[' . $row['faceId'] . '][report][10]"' . (isset($_POST['check'][$row['faceId']]['report'][10]) && $_POST['check'][$row['faceId']]['report'][10] ? ' checked="checked"' : '') . '></td></tr>';
	echo '<tr><td colspan="11" width="815px">' . date('d.m.Y', strtotime($row['ДатаПрибытия'])) . "-{$row['Цель']}; Принимающая сторона - {$row['ВидПринимающейСтороны']} {$row['НаименованиеПринимающейСтороны']}; Ответственное лицо - {$row['ОтветственноеЛицо']} {$row['АдресОтветственногоЛица']}" . '</td></tr>';
	echo '<tr id="info-' . $row['faceId'] . '" style="display:none"><td colspan="12" width="845px"><table>';
	echo '<tr><td>Личный документ</td><td><input style="width:700px" maxLength="1024" type="text" name="check[' . $row['faceId'] . '][info][doc]" value="' . (isset($_POST['check'][$row['faceId']]['info']['doc']) ? $_POST['check'][$row['faceId']]['info']['doc'] : '' ) . '"></td></tr>';
	echo '<tr><td>Адрес проверки</td><td><input style="width:700px" maxLength="1024" type="text" name="check[' . $row['faceId'] . '][info][addr]" value="' . (isset($_POST['check'][$row['faceId']]['info']['addr']) ? $_POST['check'][$row['faceId']]['info']['addr'] : '' ) . '"></td></tr>';
	echo '<tr><td>Адрес Украина</td><td><input style="width:700px" maxLength="1024" type="text" name="check[' . $row['faceId'] . '][info][addr_ukr]" value="' . (isset($_POST['check'][$row['faceId']]['info']['addr_ukr']) ? $_POST['check'][$row['faceId']]['info']['addr_ukr'] : '' ) . '"></td></tr>';
	echo '<tr><td>Адрес пребывания</td><td><input style="width:700px" maxLength="1024" type="text" name="check[' . $row['faceId'] . '][info][addr_preb]" value="' . (isset($_POST['check'][$row['faceId']]['info']['addr_preb']) ? $_POST['check'][$row['faceId']]['info']['addr_preb'] : '' ) . '"></td></tr>';
	echo '<tr><td>Цель пребывания</td><td><input style="width:700px" maxLength="128" type="text" name="check[' . $row['faceId'] . '][info][target]" value="' . (isset($_POST['check'][$row['faceId']]['info']['target']) ? $_POST['check'][$row['faceId']]['info']['target'] : '' ) . '"></td></tr>';
	echo '<tr><td>Дата прибытия</td><td><input style="width:700px" maxLength="20" id="arr_time" name="check[' . $row['faceId'] . '][info][date_preb]" value="' . (isset($_POST['check'][$row['faceId']]['info']['date_preb']) ? $_POST['check'][$row['faceId']]['info']['date_preb'] : '' ) . '"></td></tr>';
	echo '<tr><td>Дата выезда</td><td><input style="width:700px" maxLength="20" id="start_time" name="check[' . $row['faceId'] . '][info][date_viezd]" value="' . (isset($_POST['check'][$row['faceId']]['info']['date_viezd']) ? $_POST['check'][$row['faceId']]['info']['date_viezd'] : '' ) . '"></td></tr>';
	echo '<tr><td>Совместно проживает</td><td><input style="width:700px" maxLength="1024" type="text" name="check[' . $row['faceId'] . '][info][sogiteli]" value="' . (isset($_POST['check'][$row['faceId']]['info']['sogiteli']) ? $_POST['check'][$row['faceId']]['info']['sogiteli'] : '' ) . '"></td></tr>';
	echo '<tr><td>Мобильный телефон</td><td><input style="width:700px" maxLength="128" type="text" name="check[' . $row['faceId'] . '][info][phone]" value="' . (isset($_POST['check'][$row['faceId']]['info']['phone']) ? $_POST['check'][$row['faceId']]['info']['phone'] : '' ) . '"></td></tr>';
	echo '<tr><td>Прочее</td><td><input style="width:700px" type="text" name="check[' . $row['faceId'] . '][info][other]" value="' . (isset($_POST['check'][$row['faceId']]['info']['other']) ? $_POST['check'][$row['faceId']]['info']['other'] : '' ) . '"></td></tr>';
	echo '</table></td></tr>';
}
echo '</table><br><p align="right"><input type="submit" value="Сформировать" name="sub"></p></form>';
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
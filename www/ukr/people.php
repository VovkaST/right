<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require ("districtList.php");
require ($kernel."connection_ukr.php");
if (!isset($_GET['address'])){
	header('Location: '.$addr.'ukr/district.php');
	die();
} else {
	$address = $_GET['address'];
}
//phpinfo(32);
$error = array();
if (isset($_POST['sotr'], $_POST['check']) && $_POST['sotr'] && $_POST['check']){
	if (isset($_POST['sotr'])){
		$sotr = $_POST['sotr'];
	}
	if (isset($_POST['check'])){
		$checkList = $_POST['check'];
	}
	if (isset($sotr['department'], $sotr['dolz'], $sotr['zvan'], $sotr['fio'], $sotr['phone'], $sotr['date'], $sotr['time']) && !empty($sotr['department']) && !empty($sotr['dolz']) && !empty($sotr['zvan']) && !empty($sotr['fio']) && !empty($sotr['phone']) && !empty($sotr['date']) && !empty($sotr['time'])){
	
		if (mysql_query('insert into macroreport (department, addressId, должность, звание, сотрудник, телефон, датаПроверки, времяПроверки, create_date, create_time, active_id) values (\'' . implode("','", array(
			mysql_real_escape_string($sotr['department']),
			$address,
			mysql_real_escape_string($sotr['dolz']),
			mysql_real_escape_string($sotr['zvan']),
			mysql_real_escape_string($sotr['fio']),
			mysql_real_escape_string($sotr['phone']),
			date('Y-m-d', strtotime($sotr['date'])),
			mysql_real_escape_string($sotr['time'])
		)) . '\', \''.date('Y-m-d').'\', \''.date('H:i:s', time()).'\', \''.$_SESSION['activity_id'].'\')')) {

			$macroreportId = mysql_insert_id();
			//echo "<br><br>Рапорт успешно добавлен. <a target='_blank' href='/ukr/download_raport.php?id={$macroreportId}'>Скачать</a><br><a href='/ukr/district.php'>Новая проверка</a>";
		} else {
			
			$macroreportId = 0;
			$error[] = 'Рапорт. Ошибка при добавлении сотрудника: ' . mysql_error();
		}

		if ($macroreportId){
			
			$flag = false;
			foreach ($checkList as $faceId => $check){
				
				/*if (isset($check['report'])) */
        @$raport = $check['report'];
				$raportInfo = isset($check['info']) ? $check['info'] : array();
				
				if (isset($raport[1]) && $raport[1]){
					
					$flag = true;
					if (!mysql_query('insert into report (macroreportId, faceId, сфераТрудовойДеятельности, нарушениеТрудЗак, ОбучениеНЛ, ПланируетОбучатьсяНЛ, ПосещаетДошкольноеУчрНЛ, ПланируетПосещатьДошУчрНЛ, ПроживаетБезЗакПредНЛ, ПроживаетБезОформлДокНЛ, НарушениеЗакона, create_date, create_time, active_id) values (\'' . implode("','", array(
					
						$macroreportId,
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
						
						$error[] = 'Рапорт. Ошибка при добавлении проверки 1: ' . mysql_error();
						$raportId = 0;
                        mysql_query("
                            DELETE FROM
                                macroreport
                            WHERE
                                id = '$macroreportId'
                        ");
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
							
							$error[] = 'Рапорт. Ошибка при добавлении дополнительной информации проверки 2: ' . mysql_error();
							$raportInfoId = 0;
                            mysql_query("
                                DELETE FROM
                                    report
                                WHERE
                                    id = '$raportId'
                            ");
						} else {
							
							$raportInfoId = mysql_insert_id();			
						}
					}
				}
			}
			if (!$flag){
				mysql_query('delete from macroreport where id = ' . $macroreportId);
				$error[] = 'Рапорт. Отсутствуют данные о проверке';
			}
		}
	} else {
		$error[] = 'Рапорт. Отсутствуют данные о сотруднике';
	}
	if (empty($error)){
		die('<br><br>Рапорт успешно добавлен. <a target="_blank" href="'.$addr.'ukr/download_raport.php?id='.$macroreportId.'">Скачать</a><br><a href="'.$addr.'ukr/district.php">Новая проверка</a>');
	}
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан прибывших с Украины</title>
  <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?=$css?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?=$css?>head.css"></head>
  <link rel="stylesheet" href="<?= $css ?>redmond/jquery-ui-1.10.4.custom.css">
  <script src="<?= $js ?>jquery-1.10.2.js"></script>
  <script src="<?= $js ?>jquery-ui-1.10.4.custom.js"></script>
 <script>
	$(function(){
		$("#check_date, #arr_time, #start_time").datepicker()
	});
 </script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="district.php">Требуется проверка (код 3 и 4)</a>&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="address.php?district=<?=$_GET['district']?>"><?=$_GET['district']?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<?php
$stmt = mysql_query('
	SELECT 
		Строка
	FROM 
		address
	WHERE 
		id = '.$address.'
');
$addressInfo = mysql_fetch_assoc($stmt);

$stmt = mysql_query('
	SELECT
		b.ВидПринимающейСтороны,
		b.ОтветственноеЛицо,
		f.Строка as "АдресОтветственногоЛица",
		b.НаименованиеПринимающейСтороны,
		b.ФактическийАдресПринимающейСтороны,
		c.id as faceId,
		c.ФамилияКириллица,
		c.ИмяКириллица,
		c.ОтчествоКириллица,
		c.ДатаРождения,
		b.ДатаПрибытия,
		b.Цель
	FROM (
		SELECT
			MAX(a.id) as id,
			a.faceId,
			MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет
		FROM 
			notice a
		WHERE 
			a.ДатаУбытия is null
		GROUP BY 
			a.faceId
	) as a 
  JOIN 
    notice as b ON 
      a.id = b.id 
	JOIN 
    face as c ON 
      b.faceId = c.id AND 
      c.Гражданство = "UKR"
	JOIN 
    address as e ON 
      b.addrPrebId = e.id
	JOIN 
    address as f ON 
      b.addrSideId = f.id
	LEFT JOIN 
    report d ON 
      b.faceId = d.faceId
	WHERE 
		d.id IS NULL AND 
    e.id = "'.$address.'"
	ORDER BY
		c.ФамилияКириллица,
		c.ИмяКириллица,
		c.ОтчествоКириллица,
		c.ДатаРождения
');

$list = array();
foreach ($districtList as $v){
	if (isset($_POST['sotr']['department']) && ($_POST['sotr']['department'] == $v)){
		$list[] = '<option selected>' . $v . '</option>';
	} else {
		$list[] = '<option>' . $v . '</option>';
	}
}
$cnt = 0;?>
<div style="color:red">
  <?=implode('<br>', @$error)?>
</div>
<h2><?=$addressInfo['Строка']?></h2>
<form action="<?=$_SERVER['PHP_SELF']?>?district=<?=$_GET['district']?>&address=<?=$address?>" method="POST">
  <input type="hidden" name="address" value="<?=$address?>"/>
  <table>
    <tr>
      <td>Орган</td>
      <td>
        <select required="required" style="width:700px" name="sotr[department]">
          <?=implode($list)?>
        </select>
      </td>
    </tr>
    <tr>
      <td>Должность</td>
      <td>
        <input required="required" style="width:700px" maxLength="128" type="text" name="sotr[dolz]" <?php if(isset($_POST['sotr']['dolz'])) echo 'value="'.$_POST['sotr']['dolz'].'"';?>/>
      </td>
    </tr>
    <tr>
      <td>Звание</td>
      <td>
        <input required="required" style="width:700px" maxLength="128" type="text" name="sotr[zvan]" <?php if(isset($_POST['sotr']['zvan'])) echo 'value="'.$_POST['sotr']['zvan'].'"';?>/>
      </td>
    </tr>
    <tr>
      <td>Сотрудник</td>
      <td>
        <input required="required" style="width:700px" maxLength="512" type="text" name="sotr[fio]" <?php if(isset($_POST['sotr']['fio'])) echo 'value="'.$_POST['sotr']['fio'].'"';?>/>
      </td>
    </tr>
    <tr>
      <td>Телефон</td>
      <td>
        <input required="required" style="width:700px" maxLength="128" type="text" name="sotr[phone]" <?php if(isset($_POST['sotr']['phone'])) echo 'value="'.$_POST['sotr']['phone'].'"';?>/>
      </td>
    </tr>
    <tr>
      <td>Дата проверки</td>
      <td>
        <input required="required" id="check_date" class="datepicker" style="width:700px" maxLength="128" name="sotr[date]" <?php if(isset($_POST['sotr']['date'])) echo 'value="'.$_POST['sotr']['date'].'"';?>/>
      </td>
    </tr>
    <tr>
      <td>Время проверки</td>
      <td>
        <input required="required" style="width:700px" maxLength="128" type="time" name="sotr[time]" <?php if(isset($_POST['sotr']['time'])) echo 'value="'.$_POST['sotr']['time'].'"';?>/>
      </td>
    </tr>
  </table>
  <table border="1" rules="all" class="report_table">
    <tr>
      <td colspan="2">&nbsp;</td>
      <td width="545px" colspan="10" style="background:url('<?=$img?>ukr_check_cols.png') no-repeat; width: 600px; height: 200px;"></td></tr>
  <?php while($row = mysql_fetch_assoc($stmt)): ?>
    <tr>
      <td rowspan="2" width="30px" align="center"><?=++$cnt?>
        <input type="button" value="..." onclick="var el = document.getElementById('info-<?=$row['faceId']?>'); el.style.display = (el.style.display == 'none') ? '' : 'none';">
      </td>
      <td width="250px">
        <b><?=$row['ФамилияКириллица']." ".$row['ИмяКириллица']." ".$row['ОтчествоКириллица']?><br/><?=date('d.m.Y', strtotime($row['ДатаРождения']))?></b>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][1]" <?php if (isset($_POST['check'][$row['faceId']]['report'][1]) && $_POST['check'][$row['faceId']]['report'][1]) echo "checked"?>/>
      </td>
      <td width="57px" align="center">
        <select style="width:20px" name="check[<?=$row['faceId']?>][report][2]">
          <option <?php if (!isset($_POST['check'][$row['faceId']]['report'][2]) || empty($_POST['check'][$row['faceId']]['report'][2])) echo "selected"?>></option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Промышленного производства')) echo "selected"?>>Промышленного производства</option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Сельского хозяйства')) echo "selected"?>>Сельского хозяйства</option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Лесной отрасли')) echo "selected"?>>Лесной отрасли</option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Строительства')) echo "selected"?>>Строительства</option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Торговли')) echo "selected"?>>Торговли</option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Бытового обслуживания')) echo "selected"?>>Бытового обслуживания</option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'Транспорта')) echo "selected"?>>Транспорта</option>
          <option <?php if (isset($_POST['check'][$row['faceId']]['report'][2]) && ($_POST['check'][$row['faceId']]['report'][2] == 'В иной сфере')) echo "selected"?>>В иной сфере</option>
        </select>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][3]" <?php if (isset($_POST['check'][$row['faceId']]['report'][3]) && $_POST['check'][$row['faceId']]['report'][3]) echo "selected"?>/>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][4]" <?php if (isset($_POST['check'][$row['faceId']]['report'][4]) && $_POST['check'][$row['faceId']]['report'][4]) echo "selected"?>/>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][5]" <?php if (isset($_POST['check'][$row['faceId']]['report'][5]) && $_POST['check'][$row['faceId']]['report'][5]) echo "selected"?>/>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][6]" <?php if (isset($_POST['check'][$row['faceId']]['report'][6]) && $_POST['check'][$row['faceId']]['report'][6]) echo "selected"?>/>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][7]" <?php if (isset($_POST['check'][$row['faceId']]['report'][7]) && $_POST['check'][$row['faceId']]['report'][7]) echo "selected"?>/>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][8]" <?php if (isset($_POST['check'][$row['faceId']]['report'][8]) && $_POST['check'][$row['faceId']]['report'][8]) echo "selected"?>/>
      </td>
      <td width="57px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][9]" <?php if (isset($_POST['check'][$row['faceId']]['report'][9]) && $_POST['check'][$row['faceId']]['report'][9]) echo "selected"?>/>
      </td>
      <td width="60px" align="center">
        <input type="checkbox" value="1" name="check[<?=$row['faceId']?>][report][10]" <?php if (isset($_POST['check'][$row['faceId']]['report'][10]) && $_POST['check'][$row['faceId']]['report'][10]) echo "selected"?>/>
      </td>
    </tr>
    <tr>
      <td colspan="11" width="815px">
        <?=date('d.m.Y', strtotime($row['ДатаПрибытия']))."-".$row['Цель']?> Принимающая сторона - <?=$row['ВидПринимающейСтороны']." ".$row['НаименованиеПринимающейСтороны']?> Ответственное лицо - <?=$row['ОтветственноеЛицо']." ".$row['АдресОтветственногоЛица']?> 
      </td>
    </tr>
    <tr id="info-<?=$row['faceId']?>" style="display:none">
      <td colspan="12" width="845px">
        <table style="font-size: 100%;">
          <tr>
            <td>Личный документ</td>
            <td>
              <input style="width:700px" maxLength="1024" type="text" name="check[<?=$row['faceId']?>][info][doc]" <?php if (isset($_POST['check'][$row['faceId']]['info']['doc'])) echo $_POST['check'][$row['faceId']]['info']['doc'] ?>/>
            </td>
          </tr>
          <tr>
            <td>Адрес проверки</td>
            <td>
              <input style="width:700px" maxLength="1024" type="text" name="check[<?=$row['faceId']?>][info][addr]" <?php if (isset($_POST['check'][$row['faceId']]['info']['addr'])) echo $_POST['check'][$row['faceId']]['info']['addr']?>/>
            </td>
          </tr>
          <tr>
            <td>Адрес Украина</td>
            <td>
              <input style="width:700px" maxLength="1024" type="text" name="check[<?=$row['faceId']?>][info][addr_ukr]" <?php if (isset($_POST['check'][$row['faceId']]['info']['addr_ukr'])) echo $_POST['check'][$row['faceId']]['info']['addr_ukr']?>/>
            </td>
          </tr>
          <tr>
            <td>Адрес пребывания</td>
            <td>
              <input style="width:700px" maxLength="1024" type="text" name="check[<?=$row['faceId']?>][info][addr_preb]" <?php if (isset($_POST['check'][$row['faceId']]['info']['addr_preb'])) echo $_POST['check'][$row['faceId']]['info']['addr_preb']?>/>
            </td>
          </tr>
          <tr>
            <td>Цель пребывания</td>
            <td>
              <input style="width:700px" maxLength="128" type="text" name="check[<?=$row['faceId']?>][info][target]" <?php if (isset($_POST['check'][$row['faceId']]['info']['target'])) echo $_POST['check'][$row['faceId']]['info']['target']?>/>
            </td>
          </tr>
          <tr>
            <td>Дата прибытия</td>
            <td>
              <input style="width:700px" maxLength="20" id="arr_time" name="check[<?=$row['faceId']?>][info][date_preb]" <?php if (isset($_POST['check'][$row['faceId']]['info']['date_preb'])) echo $_POST['check'][$row['faceId']]['info']['date_preb']?>/>
            </td>
          </tr>
          <tr>
            <td>Дата выезда</td>
            <td>
              <input style="width:700px" maxLength="20" id="start_time" name="check[<?=$row['faceId']?>][info][date_viezd]" <?php if (isset($_POST['check'][$row['faceId']]['info']['date_viezd'])) echo $_POST['check'][$row['faceId']]['info']['date_viezd']?>/>
            </td>
          </tr>
          <tr>
            <td>Совместно проживает</td>
            <td>
              <input style="width:700px" maxLength="1024" type="text" name="check[<?=$row['faceId']?>[info][sogiteli]" <?php if (isset($_POST['check'][$row['faceId']]['info']['sogiteli'])) echo $_POST['check'][$row['faceId']]['info']['sogiteli']?>/>
            </td>
          </tr>
          <tr>
            <td>Мобильный телефон</td>
            <td>
              <input style="width:700px" maxLength="128" type="text" name="check[<?=$row['faceId']?>][info][phone]" <?php if (isset($_POST['check'][$row['faceId']]['info']['phone'])) echo $_POST['check'][$row['faceId']]['info']['phone']?>/>
            </td>
          </tr>
          <tr>
            <td>Прочее</td>
            <td>
              <input style="width:700px" type="text" name="check[<?=$row['faceId']?>][info][other]" <?php if (isset($_POST['check'][$row['faceId']]['info']['other'])) echo $_POST['check'][$row['faceId']]['other']['phone']?>/>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  <?php endwhile; ?>
  </table><br/>
  <p align="right"><input type="submit" value="Сформировать" name="sub"></p>
</form>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
require ($kernel."connection_ukr.php");
require ("districtList.php");
$adr_id = $_GET['adr_id'];
$department = $position = $range = $officer = $off_tel = $check_date = $check_time = "";
//$reason = $telephone = $fact_address = $with_whom = $auto = $auto_color = $auto_number = $work_place = $relatives_district = $other = "";
$dep_sel = "<option value=\"\" selected></option>";
$error = $to_cpe = array();
//если нажата кнопка "Сформировать"
if (isset($_POST['check'])) {
    //-------- проверка полей на допустимость --------//
    //ОВД
    if (isset($_POST['department'])) {
        $department = $_POST['department'];
		$dep_sel = "<option value=\"$department\" selected>".$department."</option>";
    }
    else {
        $error[] = "Не указан ОВД";
    }
    
    //должность
    if (isset($_POST['position'])) {
        $position = $_POST['position'];
    }
    else {
        $error[] = "Не указана должность сотрудника";
    }
    
    //звание
    if (isset($_POST['range'])) {
        $range = $_POST['range'];
    }
    else {
        $error[] = "Не указано звание сотрудника";
    }
    
    //звание
    if (isset($_POST['range'])) {
        $range = $_POST['range'];
    }
    else {
        $error[] = "Не указано звание сотрудника";
    }
    
    //сотрудник
    if (isset($_POST['officer'])) {
        $officer = $_POST['officer'];
    }
    else {
        $error[] = "Не указан сотрудник";
    }
    
    //телефон
    if (isset($_POST['off_tel'])) {
        $off_tel = $_POST['off_tel'];
    }
    else {
        $error[] = "Не указан контактный телефон сотрудника";
    }
    
    //дата проверки
    if (isset($_POST['check_date'])) {
        $check_date = $_POST['check_date'];
        //проверяем на допустимость формата
        if (preg_match("|^[0-3]\d\.[01]\d\.[1-2]\d{3}$|", $check_date)) {
            //дата больше текущей
            if (strtotime($check_date) > strtotime('now')) {
                $error[] = "Вводимая дата не может быть больше текущей";
            }
        }
        else {
            $error[] = "Дату проверки необходимо вводить в формате '00.00.0000'";
        }
    }
    else {
        $error[] = "Не указана дата проверки";
    }
    
    //проверка времени
    if (isset($_POST['check_time'])) {
        $check_time = $_POST['check_time'];
        //проверяем на допустимость формата
        if (!preg_match("|^[0-2]\d\:[0-5]\d\$|", $check_time)) {
            $error[] = "Время проверки необходимо вводить в формате '00:00'";
        }
    }
    else {
        $error[] = "Не указано время проверки";
    }
    
	//проверяем строчки на заполненность

    //для каждого отмеченного лица
    foreach ($_POST['check'] as $id => $key) {
        $check[$id] = "checked";
        $sql_leaved[$id] = $sql_arab_lang[$id] = $sql_short_pants[$id] = $sql_hijab[$id] = $sql_beard[$id] = $sql_nervousness[$id] = $sql_islamic_literature[$id] = $sql_religion_goods[$id] = $sql_visit_mosque[$id] = $sql_ethnic_diaspora[$id] = $sql_relative_NKR[$id] = $sql_relative_arabian[$id] = $sql_violation[$id] = 0;
        //проверка характерных признаков
        //если отмечено отсутствие на момент проверки - пропускаем
        if (isset($_POST['missed'][$id])) {
            continue;
        }
        if (isset($_POST['leaved'][$id])) {
            $leaved[$id] = "checked";
            $sql_leaved[$id] = 1;
        }
        if (isset($_POST['arab_lang'][$id])) {
            $arab_lang[$id] = "checked";
            $sql_arab_lang[$id] = 1;
        }
        if (isset($_POST['short_pants'][$id])) {
            $short_pants[$id] = "checked";
            $sql_short_pants[$id] = 1;
        }
        if (isset($_POST['hijab'][$id])) {
            $hijab[$id] = "checked";
            $sql_hijab[$id] = 1;
        }
        if (isset($_POST['beard'][$id])) {
            $beard[$id] = "checked";
            $sql_beard[$id] = 1;
        }
        if (isset($_POST['nervousness'][$id])) {
            $nervousness[$id] = "checked";
            $sql_nervousness[$id] = 1;
        }
        if (isset($_POST['islamic_literature'][$id])) {
            $islamic_literature[$id] = "checked";
            $sql_islamic_literature[$id] = 1;
        }
        if (isset($_POST['religion_goods'][$id])) {
            $religion_goods[$id] = "checked";
            $sql_religion_goods[$id] = 1;
        }
        if (isset($_POST['visit_mosque'][$id])) {
            $visit_mosque[$id] = "checked";
            $sql_visit_mosque[$id] = 1;
        }
        if (isset($_POST['ethnic_diaspora'][$id])) {
            $ethnic_diaspora[$id] = "checked";
            $sql_ethnic_diaspora[$id] = 1;
        }
        if (isset($_POST['relative_NKR'][$id])) {
            $relative_NKR[$id] = "checked";
            $sql_relative_NKR[$id] = 1;
        }
        if (isset($_POST['relative_arabian'][$id])) {
            $relative_arabian[$id] = "checked";
            $sql_relative_arabian[$id] = 1;
        }
        if (isset($_POST['violation'][$id])) {
            $violation[$id] = "checked";
            $sql_violation[$id] = 1;
        }
        //проверка данных проверки
        if (isset($_POST['reason'][$id])) {
            $reason[$id] = $_POST['reason'][$id];
        }
        else {
            $reason[$id] = "NULL";
        }
        if (isset($_POST['telephone'][$id])) {
            $telephone[$id] = $_POST['telephone'][$id];
        }
        else {
            $telephone[$id] = "NULL";
        }
        if (isset($_POST['fact_address'][$id])) {
            $fact_address[$id] = $_POST['fact_address'][$id];
        }
        else {
            $fact_address[$id] = "NULL";
        }
        if (isset($_POST['with_whom'][$id])) {
            $with_whom[$id] = $_POST['with_whom'][$id];
        }
        else {
            $with_whom[$id] = "NULL";
        }
        if (isset($_POST['auto'][$id])) {
            $auto[$id] = $_POST['auto'][$id];
        }
        else {
            $auto[$id] = "NULL";
        }
        if (isset($_POST['auto_color'][$id])) {
            $auto_color[$id] = $_POST['auto_color'][$id];
        }
        else {
            $auto_color[$id] = "NULL";
        }
        if (isset($_POST['auto_number'][$id])) {
            $auto_number[$id] = $_POST['auto_number'][$id];
        }
        else {
            $auto_number[$id] = "NULL";
        }
        if (isset($_POST['work_place'][$id])) {
            $work_place[$id] = $_POST['work_place'][$id];
        }
        else {
            $work_place[$id] = "NULL";
        }
        if (isset($_POST['relatives_district'][$id])) {
            $relatives_district[$id] = $_POST['relatives_district'][$id];
        }
        else {
            $relatives_district[$id] = "NULL";
        }
        if (isset($_POST['other'][$id])) {
            $other[$id] = $_POST['other'][$id];
        }
        else {
            $other[$id] = "NULL";
        }
        //если нет ошибок
        if (!count($error)) {
            //записываем строку по проверке лица в массив
            $str[$id] = $id."','".mysql_real_escape_string($telephone[$id])."','".mysql_real_escape_string($auto[$id])."',
                '".mysql_real_escape_string($auto_number[$id])."',
                '".mysql_real_escape_string($auto_color[$id])."',
                '".mysql_real_escape_string($reason[$id])."',
                '".mysql_real_escape_string($fact_address[$id])."',
                '".mysql_real_escape_string($with_whom[$id])."',
                '".mysql_real_escape_string($other[$id])."','".mysql_real_escape_string($work_place[$id])."',NULL,NULL,NULL,
                '".mysql_real_escape_string($relatives_district[$id])."',NULL,'".$sql_leaved[$id]."','".$sql_arab_lang[$id]."','".$sql_short_pants[$id]."',
                '".$sql_hijab[$id]."','".$sql_beard[$id]."','".$sql_nervousness[$id]."','".$sql_islamic_literature[$id]."',
                '".$sql_religion_goods[$id]."','".$sql_visit_mosque[$id]."','".$sql_ethnic_diaspora[$id]."',
                '".$sql_relative_NKR[$id]."','".$sql_relative_arabian[$id]."','".$sql_violation[$id]."',
                '0','".date('Y-m-d')."','".date('H:i:s', time())."','".$_SESSION['activity_id'];
            //проверяем на экстремизм, формируем ссылки
            if ($sql_arab_lang[$id] || $sql_short_pants[$id] || $sql_hijab[$id] || $sql_beard[$id] || $sql_nervousness[$id] || $sql_islamic_literature[$id] || $sql_religion_goods[$id] || $sql_visit_mosque[$id] || $sql_ethnic_diaspora[$id] || $sql_relative_NKR[$id] || $sql_relative_arabian[$id]) {
                $to_cpe[] = "<a href=\"http://.../ukr/migration_report_list.php?adr_id=$adr_id&face_id=$id\">здесь</a>";
            }
        }				
        
    }

//-------- проверка полей на допустимость --------//

//-------- добавляем в БД --------//
    if (isset($str)) {
        //добавляем рапорт в БД
        $query_report = mysql_query('
            INSERT INTO
                migration_report(id, address_id, department, datpr, vrpr, 
                    dolj, zvan, sotr, tel, 
                    deleted, create_date, create_time, active_id) 
            VALUES (NULL,"'.$adr_id.'","'.$department.'","'.date('Y-m-d', strtotime($check_date)).'","'.$check_time.'",
                "'.mysql_real_escape_string($position).'",
                "'.mysql_real_escape_string($range).'",
                "'.mysql_real_escape_string($officer).'",
                "'.mysql_real_escape_string($off_tel).'",
                "0", current_date, current_time,"'.$_SESSION['activity_id'].'")
        ');
        //если рапорт добавлен без ошибок
        if ($query_report) {
            $id_new_report = mysql_insert_id();
            foreach($str as $id => $key) {
            $sql_leaved;
                //добавляем информацию по проверке
                $query_info = mysql_query("
                    INSERT INTO 
                        migration_report_info(id, report_id, man_id, mobila, avtomod, 
                        avtogosn, avtocvet, selpiezda, faktproj, skemproj, 
                        ustanovleno, mrab, religia, prislam, otnzeml, 
                        rodsv, naruch, leaved, arab_lang, short_pants, 
                        hijab, beard, nervousness, islamic_literature, 
                        religion_goods, visit_mosque, ethnic_diaspora, 
                        relative_NKR, relative_arabian, violation, 
                        deleted, create_date, create_time, active_id) 
                    VALUES (NULL,'$id_new_report','".$str[$id]."')
                ");
                //если информация добавлена с ошибками
                if (!$query_info) {
                    //иначе выдаем ошибку
                    $error[] = "Ошибка при добавлении информации по проверке: ".mysql_error();
                    //удаляем добавленный рапорт
                    mysql_query("
                        DELETE FROM
                            migration_report_info
                        WHERE
                            report_id = $id_new_report
                    ");
                }
            }
             
        } else {
            $error[] = "Ошибка при добавлении рапорта 1: ".mysql_error();
        }
        
    }/*
    else {
        $error[] = "Ошибка при добавлении рапорта 2: ".mysql_error();
    }*/
    //если ошибок не возникло 
    if (!count($error)) {
        //проверяем экстремистские характеристики
        if ($to_cpe) {
            //-------- письмо в ЦПЭ --------
            $theme = "Миграционный учет";
            $theme = convert_cyr_string($theme, 'w', 'k');
            $message = "
                <html>
                  <body>
                    При проверке иностранного гражданина занесены характерные признаки по линии 'Экстремизм'.<br/>
                    Более подробную информацию можно получить ".implode(', ', $to_cpe)." (после авторизации через ИБД-Р).
                  </body>
                </html>";
            $message = convert_cyr_string($message, 'w', 'k');
            $headers = "Content-type: text/html; charset=KOI8-R\r\n";
            mail($cpe_mail, $theme, $message, $headers);
            //-------- письмо в ЦПЭ --------
        }
        //и перенаправляемся на стартовую страницу сервиса
        header("Location: migration_report_list.php?adr_id=$adr_id&face_id=$id");
    }
}
	//-------- добавляем в БД --------//
	
	
$sql_face = mysql_query("
    SELECT
        f.id as face_id,
        f.ФамилияКириллица as surname,
        f.ИмяКириллица as name,
        f.ОтчествоКириллица as second_name,
        f.ДатаРождения as bd,
        n.ДатаПрибытия as arrive_date,
        n.Цель as reason,
        n.ВидПринимающейСтороны as receiver_type,
        n.ОтветственноеЛицо as receiver_man,
        n.ФактическийАдресПринимающейСтороны as receiver_address,
        a.id as fact_address,
        a.Строка as fact_address_id
    FROM
        (
        SELECT
          n.id as id_preb,
          f.id as face_id,
          MAX(mr.datpr) as CheckDate,
          mri.leaved,
          n.ДатаПостановкиНаУчет as RegDate,
          MAX(n.ДатаУбытия) as LeaveDate,
          n.addrPrebId,
          a.КЛАДР,
          a.OrderId,
          a.Район as district
        FROM
          notice as n
        JOIN
          face as f 
            ON f.id = n.faceId AND
            f.Гражданство <> 'UKR' AND
            year(n.ДатаПостановкиНаУчет) = year(current_date())
        LEFT JOIN
          migration_report_info as mri 
            ON mri.man_id = n.faceId
        LEFT JOIN
          migration_report as mr 
            ON mr.id = mri.report_id AND
                        mr.deleted = '0'
        LEFT JOIN
          address as a
            ON a.id = n.addrPrebId
        GROUP BY 
          n.faceId
        ) as not_ukr
	JOIN
    address a ON not_ukr.addrPrebId = a.id AND
    a.id = $adr_id
  JOIN
    face f ON not_ukr.face_id = f.id
  JOIN
    notice n ON not_ukr.id_preb = n.id    
	WHERE
    (not_ukr.leaved = 0 OR not_ukr.leaved IS NULL) AND 
    (
     (not_ukr.CheckDate IS NULL AND not_ukr.LeaveDate IS NULL) OR
     (not_ukr.LeaveDate IS NULL AND (not_ukr.RegDate + INTERVAL 3 month < current_date()) AND not_ukr.CheckDate + INTERVAL 2 week < current_date())
    )
  ORDER BY
    surname, name, second_name
");
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан на соблюдение миграционного законодательства</title>
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
 <script>
   function check_table(id) {
     var box = document.getElementById(id);
	 var x = document.getElementById('check_table_'+id);
	 if (box.checked == true) {
	   x.style.display = '';
	 } else {
	   x.style.display = 'none';
	 }
   }
 </script>
</head>
<body>
<?php
$error = implode(', ', $error);
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
$sql_district = mysql_query("
    SELECT
        orderId,
        Район,
        Строка
    FROM
        address
    WHERE
        id = '$adr_id'
");
while ($district = mysql_fetch_row($sql_district)) {
    $district_id = $district[0];
    $district_str = $district[1];
    $address_str = $district[2];
}
?>
<div class="breadcrumbs">
<a href="<?php echo $index ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?php echo $accounting ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?php echo $migration ?>">Проверка граждан на соблюдение миграционного законодательства (текущий год) (на проверку)</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="migration_district.php?district=<?php echo $district_id ?>&mode=1"><?php echo $district_str ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<h2><?php echo $address_str ?></h2>
<form action="<?php echo $_SERVER['PHP_SELF']."?adr_id=$adr_id&mode=1" ?>" method="POST">
<table border="0" width="100%">
 <tr>
  <td colspan="6" style="color: red;">
   <?php echo $error ?>
  </td>
 </tr> 
 <tr>
  <td width="12%">Орган</td>
  <td colspan="5">
   <select name="department" style="width: 100%" required>
   <?php
   echo $dep_sel;
   require ("districtList.php");
   $list = array();
   foreach ($districtList as $v) :
     if ($v == $department) {continue;} ?>
     <option value="<?php echo $v ?>"><?php echo $v ?></option>
   <?php endforeach; ?>
   </select>
  </td>
 </tr>
 <tr>
  <td>Должность</td>
  <td colspan="2"><input required maxLength="128" name="position" style="width: 99%" value="<?php echo $position ?>" placeholder="Должность"/></td>
  <td width="13%">Звание</td>
  <td colspan="2"><input required maxLength="128" name="range" style="width: 99%" value="<?php echo $range ?>" placeholder="Звание"/></td>
 </tr>
 <tr>
  <td>Сотрудник</td>
  <td colspan="2"><input required maxLength="512" name="officer" style="width: 99%" value="<?php echo $officer ?>" placeholder="Ф.И.О."/></td>
  <td>Телефон</td>
  <td colspan="2"><input required maxLength="128" name="off_tel" style="width: 99%" value="<?php echo $off_tel ?>" placeholder="(8332) XX-XX-XX"/></td>
 </tr>
 <tr>
  <td>Дата проверки</td>
  <td colspan="2"><input required id="check_date" maxLength="128" name="check_date" value="<?php echo $check_date ?>" placeholder="00.00.0000"/></td>
  <td>Время проверки</td>
  <td colspan="2"><input required maxLength="128" type="time" name="check_time" value="<?php echo $check_time ?>" placeholder="00:00"/></td>
 </tr> 
</table>
<table width="100%" border="1" style="border: 1px solid black; border-collapse: collapse;">
  <tr>
    <td colspan="2" width="292px">&nbsp;</td>
    <td width="600px" colspan="15" style="background: url(<?php echo $img ?>migrant_check_cols.png) no-repeat; width: 600px; height:234px">&nbsp;</td>
  </tr>
  <?php 
  $n = 0;
  while ($face = mysql_fetch_array($sql_face)) : 
  $face_id = $face['face_id'];
  ?>
  
  
  <tr>
    <th rowspan="2" width="30px">
      <?php echo ++$n ?>
    </th>
    <td><b><?php echo $face['surname']." ".$face['name']." ".$face['second_name'] ?><br/><?php echo date('d.m.Y', strtotime($face['bd'])) ?></b></td>
    <td width="35px"><center><input type="checkbox" name="check[<?php echo $face_id ?>]" id="<?php echo $face_id ?>" <?php if(isset($check[$face_id])) {echo $check[$face_id];} else {echo "";} ?>/ onclick = "check_table(this.id);"></center></td>
    <td width="37px"><center><input type="checkbox" name="missed[<?php echo $face_id ?>]" <?php if(isset($missed[$face_id])) {echo $missed[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="leaved[<?php echo $face_id ?>]" <?php if(isset($leaved[$face_id])) {echo $leaved[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="arab_lang[<?php echo $face_id ?>]" <?php if(isset($arab_lang[$face_id])) {echo $arab_lang[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="short_pants[<?php echo $face_id ?>]" <?php if(isset($short_pants[$face_id])) {echo $short_pants[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="hijab[<?php echo $face_id ?>]" <?php if(isset($hijab[$face_id])) {echo $hijab[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="beard[<?php echo $face_id ?>]" <?php if(isset($beard[$face_id])) {echo $beard[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="nervousness[<?php echo $face_id ?>]" <?php if(isset($nervousness[$face_id])) {echo $nervousness[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="islamic_literature[<?php echo $face_id ?>]" <?php if(isset($islamic_literature[$face_id])) {echo $islamic_literature[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="religion_goods[<?php echo $face_id ?>]" <?php if(isset($religion_goods[$face_id])) {echo $religion_goods[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="visit_mosque[<?php echo $face_id ?>]" <?php if(isset($visit_mosque[$face_id])) {echo $visit_mosque[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="ethnic_diaspora[<?php echo $face_id ?>]" <?php if(isset($ethnic_diaspora[$face_id])) {echo $ethnic_diaspora[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="relative_NKR[<?php echo $face_id ?>]" <?php if(isset($relative_NKR[$face_id])) {echo $relative_NKR[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="relative_arabian[<?php echo $face_id ?>]" <?php if(isset($relative_arabian[$face_id])) {echo $relative_arabian[$face_id];} else {echo "";} ?>/></center></td>
    <td width="37px"><center><input type="checkbox" name="violation[<?php echo $face_id ?>]" <?php if(isset($violation[$face_id])) {echo $violation[$face_id];} else {echo "";} ?>/></center></td>
  </tr>
  <tr>
    <td colspan="17"><?php echo date('d.m.Y', strtotime($face['arrive_date']))." - ".$face['reason'] ?>; 
    Принимающая сторона: <?php echo $face['receiver_type'] ?>; 
    Ответственное лицо: <?php echo $face['receiver_man'] ?><br/><?php echo $face['receiver_address'] ?></td>
  </tr>
  <tr id="check_table_<?php echo $face_id ?>" style="display: <?php if (isset($check[$face_id])) {echo "";} else {echo "none";}?>">
    <td colspan="17">
      <table width="100%" border="0" >
        <tr>
          <td style="width: 130px;">Цель приезда:</td>
          <td colspan="3"><input type="text" style="width: 99%;" name="reason[<?php echo $face_id ?>]" placeholder="Цель приезда" value="<?php if(isset($reason[$face_id])) {echo $reason[$face_id];} ?>"/></td>
		  <td style="width: 130px;"><center>Сотовый<br/>телефон:</center></td>
          <td><input type="text" style="width: 95%;" name="telephone[<?php echo $face_id ?>]" placeholder="XXX-XXX-XX-XX" value="<?php if(isset($telephone[$face_id])) {echo $telephone[$face_id];} ?>"/></td>
        </tr>
        <tr>
          <td>Фактическое<br/>место проживания:</td>
          <td colspan="5"><input type="text" style="width: 99%;" name="fact_address[<?php echo $face_id ?>]" placeholder="Адрес фактического проживания" value="<?php if(isset($fact_address[$face_id])) {echo $fact_address[$face_id];} ?>"/></td>
        </tr>
        <tr>
          <td>С кем:</td>
          <td colspan="5"><input type="text" style="width: 99%;" name="with_whom[<?php echo $face_id ?>]" placeholder="Проживает совместно" value="<?php if(isset($with_whom[$face_id])) {echo $with_whom[$face_id];} ?>"/></td>
        </tr>
        <tr>
          <td>АМТС:</td>
          <td><input type="text" style="width: 95%;" name="auto[<?php echo $face_id ?>]" placeholder="ВАЗ-21103" value="<?php if(isset($auto[$face_id])) {echo $auto[$face_id];} ?>"/></td>
          <td style="width: 130px;"><center>Цвет:</center></td>
          <td><input type="text" style="width: 95%;" name="auto_color[<?php echo $face_id ?>]" placeholder="красный" value="<?php if(isset($auto_color[$face_id])) {echo $auto_color[$face_id];} ?>"/></td>
          <td style="width: 130px;"><center>Гос.номер</center></td>
          <td><input type="text" style="width: 95%;" name="auto_number[<?php echo $face_id ?>]" placeholder="А000АА43" value="<?php if(isset($auto_number[$face_id])) {echo $auto_number[$face_id];} ?>"/></td>
        </tr>
        <tr>
          <td>Место работы,<br/>должность:</td>
          <td colspan="5"><input type="text" style="width: 99%;" name="work_place[<?php echo $face_id ?>]" placeholder="ООО 'Рога и копыта', водитель" value="<?php if(isset($work_place[$face_id])) {echo $work_place[$face_id];} ?>"/></td>
        </tr>
        <tr>
          <td>Родственники<br/>в Кировской обл.:</td>
          <td colspan="5"><input type="text" style="width: 99%;" name="relatives_district[<?php echo $face_id ?>]" placeholder="Родственники, проживающие на территории Кировской области" value="<?php if(isset($relatives_district[$face_id])) {echo $relatives_district[$face_id];} ?>"/></td>
        </tr>
        <tr>
          <td>Иное:</td>
          <td colspan="5"><input type="text" style="width: 99%;" name="other[<?php echo $face_id ?>]" placeholder="Иное" value="<?php if(isset($other[$face_id])) {echo $other[$face_id];} ?>"/></td>
        </tr>
      </table>
    </td>
  </tr>
  
  
  <?php endwhile; ?>
</table>
<p align="right"><input type="submit" name="save_report" value="Сформировать"/></p>
</form>
<?php require ($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>

</body>
</html>
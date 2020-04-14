<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
 <title>Проверка граждан, прибывших с Украины</title>
 <link rel="icon" href="<?= IMG ?>favicon.ico" type="<?= IMG ?>vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= CSS ?>main.css">
 <link rel="stylesheet" href="<?= CSS ?>new.css">
 <link rel="stylesheet" href="<?= CSS ?>head.css">
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Проверка граждан, прибывших с Украины
</div>
<?php
require (KERNEL."connection_ukr.php");
$stmt = mysql_query('
	SELECT 
		count(distinct IF(a.ДатаУбытия is null AND a.СрокПребыванияДо >= current_date(), faceId, null)) as "НаходящиесяВКирОбл",
		count(distinct IF(truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(b.ДатаРождения, \'%Y%m\')) / 12, 0) >= 18, IF(a.ДатаУбытия is null AND a.СрокПребыванияДо >= current_date(), faceId, null), null)) as "НаходящиесяВКирОблВзрослые",
		count(distinct IF(truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(b.ДатаРождения, \'%Y%m\')) / 12, 0) between 7 AND 17, IF(a.ДатаУбытия is null AND a.СрокПребыванияДо >= current_date(), faceId, null), null)) as "НаходящиесяВКирОблНесовершеннолетние",
		count(distinct IF(truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(b.ДатаРождения, \'%Y%m\')) / 12, 0) < 7, IF(a.ДатаУбытия is null AND a.СрокПребыванияДо >= current_date(), faceId, null), null)) as "НаходящиесяВКирОблМалолетние",
		count(distinct IF(a.ДатаУбытия is null AND a.СрокПребыванияДо < current_date(), faceId, null)) as "НетОтметкиУбытия"
	FROM (
		SELECT
			MAX(a.id) as id,
			a.faceId,
			MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет,
				MAX(a.СрокПребыванияДо) as СрокПребыванияДо,
				MAX(a.ДатаУбытия) as ДатаУбытия
		FROM 
			notice a
		WHERE 
			a.ДатаУбытия is null
		GROUP BY 
			a.faceId
		) a JOIN face b ON a.faceId = b.id AND b.Гражданство = \'UKR\'
');
$row = mysql_fetch_assoc($stmt);

$stmt = mysql_query('
SELECT 
    sum(cnt) as "ВсегоРегистраций",
    count(distinct faceId) as "ВсегоЛиц",
    count(distinct IF(truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(b.ДатаРождения, \'%Y%m\')) / 12, 0) >= 18, faceId, null)) as "Взрослые",
    count(distinct IF(truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(b.ДатаРождения, \'%Y%m\')) / 12, 0) between 7 and 17, faceId, null)) as "Несовершеннолетние",
    count(distinct IF(truncate(period_diff(DATE_FORMAT(a.ДатаПостановкиНаУчет, \'%Y%m\'), DATE_FORMAT(b.ДатаРождения, \'%Y%m\')) / 12, 0) < 7, faceId, null)) as "Малолетние"
FROM    
    (SELECT
    count(IF(year(a.ДатаПостановкиНаУчет) = year(current_date()), 1, null)) as cnt,
    MAX(a.id) as id,
    a.faceId,
    MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет
FROM 
    notice a
GROUP BY 
    a.faceId
) a JOIN face b ON a.faceId = b.id AND b.Гражданство = \'UKR\' AND year(a.ДатаПостановкиНаУчет) = year(current_date())
');
$_row = mysql_fetch_assoc($stmt);

$stmt = mysql_query('
	SELECT 
		count(distinct IF(c.id is null, a.faceId, null)) as "ТребуетсяПроверкаЛиц",
		count(distinct c.faceId) as "ПровереноЛиц"
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
	) a JOIN face b ON a.faceId = b.id AND b.Гражданство = \'UKR\'
	LEFT JOIN report c ON a.faceId = c.faceId
');
$row2 = mysql_fetch_assoc($stmt);

$stmt = mysql_query('
	SELECT
		count(distinct c.faceId) as "ВсегоПровереноЛиц",
		count(distinct IF(d.id is not null, c.faceId, null)) as "ВыявленоЛиц",
		count(distinct IF(length(СфераТрудовойДеятельности) > 0, c.faceId, null)) as "ЛицоТруд",
		count(distinct IF(СфераТрудовойДеятельности = "Промышленного производства", c.faceId, null)) as "ЛицоТрудСфераПромышленногоПроизводства",
		count(distinct IF(СфераТрудовойДеятельности = "Сельского хозяйства", c.faceId, null)) as "ЛицоТрудСфераСельскогоХозяйства",
		count(distinct IF(СфераТрудовойДеятельности = "Мясной отрасли", c.faceId, null)) as "ЛицоТрудСфераМяснойОтрасли",
		count(distinct IF(СфераТрудовойДеятельности = "Строительства", c.faceId, null)) as "ЛицоТрудСфераСтроительства",
		count(distinct IF(СфераТрудовойДеятельности = "Торговли", c.faceId, null)) as "ЛицоТрудСфераТорговли",
		count(distinct IF(СфераТрудовойДеятельности = "Бытового обслуживания", c.faceId, null)) as "ЛицоТрудСфераБытовогоОбслуживания",
		count(distinct IF(СфераТрудовойДеятельности = "Транспорта", c.faceId, null)) as "ЛицоТрудСфераТранспорта",
		count(distinct IF(СфераТрудовойДеятельности = "В иной сфере", c.faceId, null)) as "ЛицоТрудВИнойСфере",
		count(distinct IF(нарушениеТрудЗак, c.faceId, null)) as "НарушениеТрудЗак",
		count(distinct IF(ОбучениеНЛ, c.faceId, null)) as "НлОбучение",
		count(distinct IF(ПланируетОбучатьсяНЛ, c.faceId, null)) as "НлПланируетОбучение",
		count(distinct IF(ПосещаетДошкольноеУчрНЛ, c.faceId, null)) as "НлДошкольник",
		count(distinct IF(ПланируетПосещатьДошУчрНл, c.faceId, null)) as "НлПланируетСтатьДошкольник",
		count(distinct IF(ПроживаетБезЗакПредНЛ, c.faceId, null)) as "НлПроживаетОдин",
		count(distinct IF(ПроживаетБезОформлДокНЛ, c.faceId, null)) as "НлПроживаетБезДок",
		count(distinct IF(НарушениеЗакона, c.faceId, null)) as "ЛицоВыявленоНарЗак"
	FROM 
		report c
	LEFT JOIN message d ON d.id = c.messageId
');
$row3 = mysql_fetch_assoc($stmt);

$stmt = mysql_query('
	SELECT
		count(*) as cnt
	FROM
		check_org
');
$row4 = mysql_fetch_assoc($stmt);?>

<table cellpadding="3" width="70%" border="1" rules="all" align="center" class="result_table">
  <tr align="center" class="table_head">
    <th colspan="3" width="60%">Позиция</th>
    <th width="5%">Код</th>
    <th width="5%">Количество<br/>проверок</th>
  </tr>
  <tr>
    <td colspan="3">Всего регистраций (УФМС, с начала текущего года)</td>
    <td>1</td>
    <td align="center"><?=$_row['ВсегоРегистраций']?></td>
  </tr>
  <tr>
    <td colspan="3">Всего прибыло лиц (УФМС, с начала текущего года)</td>
    <td>2</td>
    <td align="center"><a href="<?=$addr?>ukr/districtTotal.php?mode=0"><?=$_row['ВсегоЛиц']?></a></td>
  </tr>
  <tr>
    <td rowspan="3" width="10%">из них</td>
    <td colspan="2" width="50%">Взрослых</td>
    <td>2.1</td>
    <td align="center"><a href="<?=$addr?>ukr/districtTotal.php?mode=1"><?=$_row['Взрослые']?></a></td>
  </tr>
  <tr>
    <td colspan="2" width="50%">Несовершеннолетних</td>
    <td>2.2</td>
    <td align="center"><a href="<?=$addr?>ukr/districtTotal.php?mode=2"><?=$_row['Несовершеннолетние']?></a></td>
  </tr>
  <tr>
    <td colspan="2" width="50%">Малолетних</td>
    <td>2.3</td>
    <td align="center"><a href="<?=$addr?>ukr/districtTotal.php?mode=3"><?=$_row['Малолетние']?></a></td>
  </tr>
  <tr>
    <td colspan="3">Находящиеся в Кировской области</td>
    <td>3</td>
    <td align="center"><a href="<?=$addr?>ukr/districtCurrent.php?mode=0"><?=$row['НаходящиесяВКирОбл']?></a></td>
  </tr>
  <tr>
    <td rowspan="3" width="10%">из них</td>
    <td colspan="2" width="50%">Взрослых</td>
    <td>3.1</td>
    <td align="center"><a href="<?=$addr?>ukr/districtCurrent.php?mode=1"><?=$row['НаходящиесяВКирОблВзрослые']?></a></td>
  </tr>
  <tr>
    <td colspan="2" width="50%">Несовершеннолетних</td>
    <td>3.2</td>
    <td align="center"><a href="<?=$addr?>ukr/districtCurrent.php?mode=2"><?=$row['НаходящиесяВКирОблНесовершеннолетние']?></a></td>
  </tr>
  <tr>
    <td colspan="2" width="50%">Малолетних</td>
    <td>3.3</td>
    <td align="center"><a href="<?=$addr?>ukr/districtCurrent.php?mode=3"><?=$row['НаходящиесяВКирОблМалолетние']?></a></td>
  </tr>
  <tr>
    <td colspan="3">Нет отметки об убытии</td>
    <td>4</td>
    <td align="center"><?=$row['НетОтметкиУбытия']?></td>
  </tr>
  <tr>
    <td colspan="3">Требуется проверка (код 3 и 4)</td>
    <td>5</td>
    <td align="center"><a href="<?=$addr?>ukr/district.php"><?=$row2['ТребуетсяПроверкаЛиц']?></a></td>
  </tr>
  <tr>
    <td colspan="3">Всего проверено лиц</td>
    <td>6</td>
    <td align="center"><?=$row3['ВсегоПровереноЛиц']?></td>
  </tr>
  <tr>
    <td rowspan="18" width="10%">из них</td>
    <td colspan="2" width="50%">Состоящих на учете УФМС</td>
    <td>6.1</td>
    <td align="center"><a href="<?=$addr?>ukr/districtFaceList.php"><?=$row2['ПровереноЛиц']?></a></td>
  </tr>
  <tr>
    <td colspan="2" width="50%">Не состоящих на учете У‘МС (в инициативном порядке)</td>
    <td>6.2</td>
    <td align="center"><a href="<?=$addr?>ukr/districtMessageFaceList.php"><?=$row3['ВыявленоЛиц']?></a>&nbsp;&nbsp;&nbsp;<a href="/ukr/face.php">+Доб.</a></td>
  </tr>
  <tr>
    <td colspan="2">Нсуществляющие трудовую деятельность</td>
    <td>6.3</td>
    <td align="center"><?=$row3['ЛицоТруд']?></td>
  </tr>
  <tr>
    <td rowspan="8" width="10%">в том числе в сфере</td>
    <td>Промышленного производства</td>
    <td>6.3.1</td>
    <td align="center"><?=$row3['ЛицоТрудСфераПромышленногоПроизводства']?></td>
  </tr>
  <tr>
    <td>Сельского хозяйства</td>
    <td>6.3.2</td>
    <td align="center"><?=$row3['ЛицоТрудСфераСельскогоХозяйства']?></td>
  </tr>
  <tr>
    <td>Месной отрасли</td>
    <td>6.3.3</td>
    <td align="center"><?=$row3['ЛицоТрудСфераМяснойОтрасли']?></td>
  </tr>
  <tr>
    <td>Строительства</td>
    <td>6.3.4</td>
    <td align="center"><?=$row3['ЛицоТрудСфераСтроительства']?></td>
  </tr>
  <tr>
    <td>Торговли</td>
    <td>6.3.5</td>
    <td align="center"><?=$row3['ЛицоТрудСфераТорговли']?></td>
  </tr>
  <tr>
    <td>Пытового обслуживания</td>
    <td>6.3.6</td>
    <td align="center"><?=$row3['ЛицоТрудСфераБытовогоОбслуживания']?></td>
  </tr>
  <tr>
    <td>Транспорта</td>
    <td>6.3.7</td>
    <td align="center"><?=$row3['ЛицоТрудСфераТранспорта']?></td>
  </tr>
  <tr>
    <td>В иной сфере</td>
    <td>6.3.8</td>
    <td align="center"><?=$row3['ЛицоТрудВИнойСфере']?></td>
  </tr>
  <tr>
    <td>в том числе</td>
    <td>С нарушением трудового законодательства</td>
    <td>6.3.9</td>
    <td align="center"><?=$row3['НарушениеТрудЗак']?></td>
  </tr>
  <tr>
    <td colspan="2">Н/М обучающихся в уч.зав.</td>
    <td>6.4</td>
    <td align="center"><?=$row3['НлОбучение']?></td>
  </tr>
  <tr>
    <td colspan="2">Н/М планирующих обуч. в уч.зав.</td>
    <td>6.5</td>
    <td align="center"><?=$row3['НлПланируетОбучение']?></td>
  </tr>
  <tr>
    <td colspan="2">М/М посещающих дош.учр.</td>
    <td>6.6</td>
    <td align="center"><?=$row3['НлДошкольник']?></td>
  </tr>
  <tr>
    <td colspan="2">М/М планирующих посещ. дош.учр.</td>
    <td>6.7</td>
    <td align="center"><?=$row3['НлПланируетСтатьДошкольник']?></td>
  </tr>
  <tr>
    <td colspan="2">Проживающих без родителей (зак.предст.)</td>
    <td>6.8</td>
    <td align="center"><?=$row3['НлПроживаетОдин']?></td>
  </tr>
  <tr>
    <td colspan="2">Проживающих оформления документов на зак.предст.</td>
    <td>6.9</td>
    <td align="center"><?=$row3['НлПроживаетБезДок']?></td>
  </tr>
  <tr>
    <td colspan="3">Выявлено нарушений законодательства гражданами украины</td>
    <td>7</td>
    <td align="center"><?=$row3['ЛицоВыявленоНарЗак']?></td>
  </tr>
  <tr>
    <td colspan="3">Проверено работодателей</td>
    <td>8</td>
    <td align="center"><a href="<?=$addr?>ukr/organisationTotal.php"><?=$row4['cnt']?></a></td>
  </tr>
  <tr>
    <td colspan="3">Проверено организаций, волонтеров и иных лиц, организующих сбор гуманитарной помощи</td>
    <td>9</td>
    <td align="center">&mdash;</td>
  </tr>
</table>

<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
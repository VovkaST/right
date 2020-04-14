<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<?php
require ("/../../connection_ukr.php");

if (!session_id()){
	
	session_start();
}

if (isset($_POST['address'])){
	
	$_SESSION['address'] = $_POST['address'];
} elseif (!isset($_SESSION['address'])){
	
	$_SESSION['address'] = '';
}

if (!isset($_POST['mode'])){
	
	$mode = 'Проверить';
} else {
	
	$mode = $_POST['mode'];
}

$error = array();
$modeParts = explode('_', $mode);
$mode = $modeParts[0];
switch ($mode){
	
	case 'Проверить':
		
		$face = array(
			'fam' => isset($_POST['fam']) ? $_POST['fam'] : '',
			'imj' => isset($_POST['imj']) ? $_POST['imj'] : '',
			'otch' => isset($_POST['otch']) ? $_POST['otch'] : '',
			'dt' => isset($_POST['dt']) ? $_POST['dt'] : '',
			'sex' => isset($_POST['sex']) ? $_POST['sex'] : '',
			'mr' => isset($_POST['mr']) ? $_POST['mr'] : '',
			'grajdan' => 'UKR',
			'country' => 'UKR'
		);
		$_SESSION['face'] = $face;		
		
		$search = array();
		if ($face['fam']){
			
			$search[] = "ФамилияКириллица like '" . mb_strcut($face['fam'], 0, 10, 'UTF-8') . "%'";
		}
		if ($face['imj']){
			
			$search[] = "ИмяКириллица like '" . mb_strcut($face['imj'], 0, 10, 'UTF-8') . "%'";
		}
		if ($face['dt']){
			
			$search[] = "year(ДатаРождения) = " . date('Y', strtotime($face['dt']));
		}		
		if ($search){
						
			$stmt = mysql_query('SELECT id, ФамилияКириллица, ИмяКириллица, ОтчествоКириллица, ДатаРождения FROM face WHERE Гражданство = \'UKR\' and ' . implode(' and ', $search));
		} else {
			
			$stmt = false;
		}
	break;
	
	case 'Добавить':
		
		if (!isset($_SESSION['list'])){
			
			$_SESSION['list'] = array();
		}

		if ($_SESSION['face']['fam']){
			$sql_face_search = mysql_query('
				SELECT 
					id 
				FROM 
					face 
				WHERE
					ФамилияКириллица = "'.$_SESSION['face']['fam'].'" and
					ИмяКириллица = "'.$_SESSION['face']['imj'].'" and
					ОтчествоКириллица = "'.$_SESSION['face']['otch'].'" and
					ДатаРождения = "'.date('Y-m-d', strtotime($_SESSION['face']['dt'])).'" 
			');
			
			if (mysql_num_rows($sql_face_search)) {
				while ($face_search = mysql_fetch_array($sql_face_search)) {
					$_SESSION['list'][] = $face_search['id'];
				}
				$_SESSION['face'] = array(
					'fam' => '',
					'imj' => '',
					'otch' => '',
					'dt' => '',
					'sex' => '',
					'mr' => '',
					'country' => '',
					'grajdan' => ''
				);
			}
			else
			{
			if (mysql_query('INSERT INTO face (ФамилияКириллица, ИмяКириллица, ОтчествоКириллица, ДатаРождения, МестоРождения, Пол, СтранаРождения, Гражданство, create_date, create_time, active_id) VALUES (\'' . implode("','", array(
				mysql_real_escape_string($_SESSION['face']['fam']),
				mysql_real_escape_string($_SESSION['face']['imj']),
				mysql_real_escape_string($_SESSION['face']['otch']),
				date('Y-m-d', strtotime($_SESSION['face']['dt'])),
				mysql_real_escape_string($_SESSION['face']['mr']),
				mysql_real_escape_string($_SESSION['face']['sex']),
				mysql_real_escape_string($_SESSION['face']['country']),
				mysql_real_escape_string($_SESSION['face']['grajdan'])
				)) . '\', \''.date('Y-m-d').'\', \''.date('H:i:s', time()).'\', \''.$_SESSION['activity_id'].'\')')){				
					$_SESSION['list'][] = mysql_insert_id();
					$_SESSION['face'] = array(
						'fam' => '',
						'imj' => '',
						'otch' => '',
						'dt' => '',
						'sex' => '',
						'mr' => '',
						'country' => '',
						'grajdan' => ''
					);
				} else {
				
				$error[] = 'Лицо. Ошибка при добавлении: ' . mysql_error();
				}
			}
		}
	break;
	
	case 'Удалить':
		
		$_SESSION['list'] = array_diff($_SESSION['list'], array($modeParts[1]));	
	break;
	
	case 'Сформировать сообщение':
		
		if (empty($_SESSION['address'])){

			$error[] = 'Лицо. Отсутствует адрес проверки';
		} else {
		
			header('Location: /ukr/people_message.php');
			die();
		}
	break;
}

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Проверка граждан, прибывших с Украины</title>
  <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?=$css?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?=$css?>head.css">
  <link rel="stylesheet" href="<?=$css?>redmond/jquery-ui-1.10.4.custom.css">
 <script src="<?=$js?>jquery-1.10.2.js"></script>
 <script src="<?=$js?>jquery-ui-1.10.4.custom.js"></script>
 <script>
	$(function(){
		$("#borth_date").datepicker()
	});
 </script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');

echo "\n<div class=\"breadcrumbs\">";
echo "\n<a href=\"".$index."\">Главная</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."<a href=\"".$accounting."\">Формирование учетов</a>"."&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;"."Проверка граждан прибывших с Украины";
echo "\n</div>";
$html = '<div style="color:red">' . implode('<br>', $error) . '</div><h1>Инициативная проверка</h1><form action="" method="POST"><table border>';
$html .= '<tr><td colspan="2"><table><tr><td style="width:113px; vertical-align:top">Адрес проверки: </td><td style="vertical-align:top"><input style="width: 680px" required="required" maxLength="1024" type="text" name="address" value="' . $_SESSION['address'] . '"></td></tr></table><br></td></tr>';

$html .= '<tr><th>Постановка на учет нового лица</th><th>Проверка лиц</th></tr><tr><td width="400px" style="vertical-align:top">';
$add = !empty($_SESSION['face']['fam']) && !empty($_SESSION['face']['imj']) && !empty($_SESSION['face']['dt']) && !empty($_SESSION['face']['sex']) && !empty($_SESSION['face']['mr']) ? '<input type="submit" value="Добавить" name="mode">' : '';
$options = '<option' . ($_SESSION['face']['sex'] == 'М' ? ' selected = "selected"' : '') . '>М</option><option' . ($_SESSION['face']['sex'] == 'Ж' ? ' selected = "selected"' : '') . '>Ж</option>';
$html .= <<<HTML
<table>
<tr><td>Фамилия:</td><td><input maxlength="128" type="text" name="fam" style="width:250px" value="{$_SESSION['face']['fam']}"></td></tr>
<tr><td>Имя:</td><td><input maxlength="128" type="text" name="imj" style="width:250px" value="{$_SESSION['face']['imj']}"></td></tr>
<tr><td>Отчество:</td><td><input maxlength="128" type="text" name="otch" style="width:250px" value="{$_SESSION['face']['otch']}"></td></tr>
<tr><td>Дата рождения:</td><td><input maxlength="10" name="dt" id="borth_date" class="datepicker" style="width:250px" value="{$_SESSION['face']['dt']}"></td></tr>
<tr><td>Пол:</td><td><select name="sex" style="width:250px">{$options}</select></td></tr>
<tr><td>Место рождения:</td><td><input maxlength="512" type="text" name="mr" style="width:250px" value="{$_SESSION['face']['mr']}"></td></tr>
<tr><td colspan="2" align="right"><input type="submit" value="Проверить" name="mode">{$add}</td></tr>
</table>
</td>
HTML;

$html .= '<td width="400px" style="vertical-align:top;text-align:right"><select multiple size="13" style="width:100%; height: 100%;">';
if ($mode == 'Проверить' && $stmt){

	while ($row = mysql_fetch_assoc($stmt)){
		
		$html .= '<option>' . "{$row['ФамилияКириллица']} {$row['ИмяКириллица']} {$row['ОтчествоКириллица']} " . date('d.m.Y', strtotime($row['ДатаРождения'])) . '</option>';
	}
}
$html .= '</select>';

$html .= '</td></tr>';

if (@$_SESSION['list']){

	$html .= '<tr><th colspan="2">Вновь выявленные лица для сообщения</th></tr><tr><td colspan="2"><table>';
	$cnt = 0;
	$stmt2 = mysql_query('SELECT id, ФамилияКириллица, ИмяКириллица, ОтчествоКириллица, ДатаРождения, МестоРождения FROM face WHERE Гражданство = \'UKR\' and id in (' . implode(',', $_SESSION['list']) . ')');
	while ($row = mysql_fetch_assoc($stmt2)){
		
		$html .= '<tr><td style="width:700px">' . (++$cnt) . ". {$row['ФамилияКириллица']} {$row['ИмяКириллица']} {$row['ОтчествоКириллица']} " . date('d.m.Y', strtotime($row['ДатаРождения'])) . ' ' . $row['МестоРождения'] . '</td><td style="width:100px;text-align:center"><input onclick="this.value=\'Удалить_' . $row['id'] . '\';" type="submit" value="Удалить" name="mode"></td></tr>';
	}
$html .= '</table></td></tr>';
$html .= '<tr><td colspan="2" align="right"><br><input type="submit" value="Сформировать сообщение" name="mode"></td></tr>';
}

$html .= '</table></form>';

echo $html;
@mysql_close($db);
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
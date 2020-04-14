<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require (KERNEL."connection_uii.php");
$error = "";
if (isset($_POST["_submit"])) {
	if(!trim(@$_POST["zvan"])){
		$error .= "незаполнено поле звания<br/>";
	}
	if(!trim(@$_POST["sotr"])){
		$error .= "незаполнено поле ФИО сотрудника";
	}
	if(!$error){
		mysql_query('
      INSERT INTO raport (journal_id, mobila, faktproj, skemproj, uslovij_proj,
        mrab, m_rab_xar, v_vid, sosedi, mproj_sosedi, harakteristika,
        svazi, avto, gos_num, zvan_ruk, rukovoditel, data_vvoda, v_ogran,
				sotr, zvan, dolj, Inoe)
			VALUES
				("'.$_GET["man_id"].'", 
				"'.mysql_real_escape_string($_POST["mobila"]).'",
				"'.mysql_real_escape_string($_POST["faktproj"]).'",
				"'.mysql_real_escape_string($_POST["skemproj"]).'",
				"'.mysql_real_escape_string($_POST["uslovij_proj"]).'",
				"'.mysql_real_escape_string($_POST["mrab"]).'",
				"'.mysql_real_escape_string($_POST["m_rab_xar"]).'",
				"'.mysql_real_escape_string($_POST["v_vid"]).'",
				"'.mysql_real_escape_string($_POST["sosedi"]).'",
				"'.mysql_real_escape_string($_POST["mproj_sosedi"]).'",
				"'.mysql_real_escape_string($_POST["harakteristika"]).'",
				"'.mysql_real_escape_string($_POST["svazi"]).'",
				"'.mysql_real_escape_string($_POST["avto"]).'",
				"'.mysql_real_escape_string($_POST["gos_num"]).'",
				"'.mysql_real_escape_string($_POST["zvan_ruk"]).'",
				"'.mysql_real_escape_string($_POST["rukovoditel"]).'",
				current_date,
				"'.mysql_real_escape_string($_POST["v_ogran"]).'",
				"'.mysql_real_escape_string($_POST["sotr"]).'",
				"'.mysql_real_escape_string($_POST["zvan"]).'",
				"'.mysql_real_escape_string($_POST["dolj"]).'",
				"'.mysql_real_escape_string($_POST["Inoe"]).'"
			)
		') or die(mysql_error());
    $rep_id = mysql_insert_id();
    foreach ($_POST['dat_prov'] as $k => $v) {
      if ($v) {
        $h = $_POST['vrem'][$k] == '' ? '00' : $_POST['vrem'][$k];
        $checks[] = '('.$rep_id.', "'.date('Y-m-d', strtotime($v)).'", "'.$h.'")';
      }
    }
    mysql_query('
      INSERT INTO 
        raport_date (`raport_id`, `check_date`, `check_time`)
      VALUES '.implode(', ', $checks).'
    ') or die(mysql_error());
		//header("Location: men.php?men_id=".$_GET["man_id"]."&ovd_id=".$_POST["ovd_id"]);
	}
}
if (isset($_GET["mode"])) {
  $mode = $_GET["mode"];
  if ($mode == 1) {
    $check = " (всего на учете)";
  }
  elseif ($mode == 2) {
    $check = " (на проверку)";
  }
} else {
  header("Location: ".$uii);
}
$result = @mysql_query('
  SELECT
    *
  FROM
    journal
  WHERE
    id = "'.$_GET["man_id"].'"
') or die("Query failed : " . mysql_error());
$man = mysql_fetch_array($result);
@mysql_free_result($result);
$fio = $man["fam"]." ".$man["im"]." ". $man["otch"]." ". $man["datroj"];
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Рапорт</title>
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="<?= IMG ?>vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <script src="<?= JS ?>jquery-1.10.2.js"></script>
  <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script src="<?= JS ?>procedures.js"></script>
<script>
$(document).on('change', '.date_str input, .date_str select', function(){
  var div = $(this).closest('.date_str');
  var empty = false;
  var list = $('.date_list');
  div.find('input, select').each(function(){
    if ($(this).val() == '') {
      empty = true;
      div.removeAttr('filled');
      div.children('.date_str_deleter').css('display', 'none');
    }
  });
  if (!empty) {
    div.attr('filled', 'true');
    div.children('.date_str_deleter').css('display', 'inline');
    if (list.children('.date_str').length == list.children('.date_str[filled="true"]').length) {
      $('.date_str').first().clone(true)
        .appendTo(list)
        .removeAttr('filled');
      $('.date_str').last().children('.date_str_deleter').css('display', 'none');
      $('.date_str').last().find('input, select').each(function(){
        $(this)
          .val('')
          .removeClass('hasDatepicker')
          .removeAttr('id');
      });
      $(".datepicker").datepicker();
    }
  }
});
</script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= UII ?>">Лица, состоящие на учете в УИИ</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ADDR ?>uii/count.php?mode=<?=$mode?>"><?=$man["ovd"].$check?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<?php if ($error != ''):?>
  <div class="error" id="error_main">
    <?=$error?>
  </div>
<?php endif;?>
<h2><?=$fio?> г.р.</h2>
<form method="POST">
<table width="100%" id="add_report_table">
  <tr>
    <td width="70%"></td>
    <td>Начальнику</td>
  </tr>
  <tr>
    <td></td>
    <td><?=$man["uin"]?></td>
  </tr>
  <tr>
    <td></td>
    <td>
      <input size="50%" name="zvan_ruk" <?php if (isset($_POST["zvan_ruk"])) {echo 'value="'.$_POST["zvan_ruk"].'"';};?>/>
    </td>
  </tr>
  <tr>
    <td></td>
    <td>
      <input size="50%" name="rukovoditel" <?php if (isset($_POST["rukovoditel"])) {echo 'value="'.$_POST["rukovoditel"].'"';};?>/>
    </td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td align="center" colspan="2">ОБЗОРНАЯ СПРАВКА</td>
  </tr>
  <tr>
    <td align="left" colspan="2">Докладываю Вам, что
      <div class="date_list" style="display: inline;">
        <div class="date_str" style="display: inline;">
          <input style="width: 100px" name="dat_prov[]" class="datepicker" placeholder="Дата" />
          около
          <select name="vrem[]" style="min-width: 50px;">
            <option></option>
            <option value="01">01</option>
            <option value="02">02</option>
            <option value="03">03</option>
            <option value="04">04</option>
            <option value="05">05</option>
            <option value="06">06</option>
            <option value="07">07</option>
            <option value="08">08</option>
            <option value="09">09</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
            <option value="13">13</option>
            <option value="14">14</option>
            <option value="15">15</option>
            <option value="16">16</option>
            <option value="17">17</option>
            <option value="18">18</option>
            <option value="19">19</option>
            <option value="20">20</option>
            <option value="21">21</option>
            <option value="22">22</option>
            <option value="23">23</option>
            <option value="24">24</option>
          </select>
          часов
          <span class="date_str_deleter" style="cursor: pointer; display: none;" onclick="if($('.date_str').length > 1) {$(this).parent('.date_str').remove();}">
            <strong style="color: rgb(199, 92, 92);">×</strong>, 
          </span>
        </div>
      </div>
      проверен по месту жительства состоящий с <?=date("d.m.Y", strtotime($man["dat_post_uch"]))?> на учете в <?=$man["uin"]?>
      - <?=$fio?> г.р., уроженец: <?=$man["gor_rai_reg"]?>, <?=$man["nas_p_reg"]?>,
      адрес регистрации <?=$man["ADR_REG"]?>, адрес проживанния: <?=$man["ADR_FAKT"]?>, номер личного дела - <?=$man["num_delo"]?>.
      Категория учета - "<?=$man["kat_uch"]?>". Основание постановки - "<?=$man["osnov_uch"]?>" за совершение преступления предусмотренного ст.
      <?=$man["st_uk"]?> УК РФ. Ограничения: <?=$man["ogranichenia"]?>, Обязанности: <?=$man["obazannost"]?>.
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      Мобильный телефон
      <input size="50%" name="mobila" <?php if (isset($_POST["mobila"])) {echo 'value="'.$_POST["mobila"].'"';};?>/>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      При проверке установлено, что <?=$man["fam"]?> фактически проживает по адресу: 
      <input style="width:39%" name="faktproj" <?php if (isset($_POST["faktproj"])) {echo 'value="'.$_POST["faktproj"].'"';};?>/>
    </td>
  </tr>
  <tr rowspan=3>
    <td align=left colspan=2>
      совместно с  
      <textarea name="skemproj" style="width:100%"><?php if (isset($_POST["skemproj"])) {echo $_POST["skemproj"];};?></textarea>
    </td>
  </tr>
  <tr rowspan=3>
    <td align=left colspan=2>
      Условия проживания
      <textarea name="uslovij_proj" style="width:100%"><?php if (isset($_POST["uslovij_proj"])) {echo $_POST["uslovij_proj"];};?></textarea>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      Место работы, должность: 
      <input style="width:79%" name="mrab" <?php if (isset($_POST["mrab"])) {echo 'value="'.$_POST["mrab"].'"';};?>/>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      По месту работы характеризуется: 
      <input style="width:73%" name="m_rab_xar" <?php if (isset($_POST["m_rab_xar"])) {echo 'value="'.$_POST["m_rab_xar"].'"';};?>/>
    </td>
  </tr>
  <tr rowspan=3>
    <td align=left colspan=2>
      Внешний вид (особые приметы) подучетного лица: 
      <textarea name="v_vid" style="width:100%"><?php if (isset($_POST["v_vid"])) {echo $_POST["v_vid"];};?></textarea>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      Со слов членов семьи (родственников) и соседей : 
      <input style="width:62%" name="sosedi" <?php if (isset($_POST["sosedi"])) {echo 'value="'.$_POST["sosedi"].'"';};?>/>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
    , проживающих по адресу:
    <input style="width:79%" name="mproj_sosedi" <?php if (isset($_POST["mproj_sosedi"])) {echo 'value="'.$_POST["mproj_sosedi"].'"';};?>/>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      , в быту <?=$man["fam"]?> характеризуется (склонность употребления спиртных напитков): 
      <textarea name="harakteristika" style="width:100%"><?php if (isset($_POST["harakteristika"])) {echo $_POST["harakteristika"];};?></textarea>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      Возложенные обязанности/ограничения (выполняет в полном объеме, выполняет частично, не выполняет): 
      <input style="width:20%" name="v_ogran" <?php if (isset($_POST["v_ogran"])) {echo 'value="'.$_POST["v_ogran"].'"';};?>/>
    </td>
  </tr>
  <tr rowspan=3>
    <td align=left colspan=2>
      Связи лица (круг общения): 
      <textarea name="svazi" style="width:100%"><?php if (isset($_POST["svazi"])) {echo $_POST["svazi"];};?></textarea>
    </td>
  </tr>
  <tr>
    <td align=left colspan=2>
      Передвигается на автомашине:   
      <input size="30%" name="avto" <?php if (isset($_POST["avto"])) {echo 'value="'.$_POST["avto"].'"';};?>>, гос.№ - 
      <input size="20%" name="gos_num" <?php if (isset($_POST["gos_num"])) {echo 'value="'.$_POST["gos_num"].'"';};?>>,
    </td>
  </tr>
  <tr rowspan=3>
    <td align=left colspan=2>
      Иная информация установленная при проверке 
      <textarea name="Inoe" style="width:100%"><?php if (isset($_POST["Inoe"])) {echo $_POST["Inoe"];};?></textarea>
    </td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr rowspan=4>
    <td align=left colspan=2>
      С подучетным лицом проведена профилактическая беседа о недопущении нарушений общественного порядка и общественной безопасности, а также необходимости соблюдения наложенных обязанностей и ограничений.
    </td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr rowspan=4>
    <td align=left colspan=2>
      Распечатка справки проверки лица по учетам ИБД-Р, на оборотном листе данного рапорта, прилагается.
    </td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr rowspan=4>
    <td align=left colspan=2>
      В соответствии Инструкцией «О порядке взаимодействия» утвержденной приказом УФСИН России по Кировской области и УМВД России по Кировской области от 30.04.2013 №203/331 прошу Вашего разрешения настоящий рапорт направить в <?=$man["uin"]?>. Информация о проверке подучетного лица внесена в паспорт на административный участок.
    </td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td align=left colspan=2>
      <input style="width:30%" name="dolj" <?php if (isset($_POST["dolj"])) {echo 'value="'.$_POST["dolj"].'"';};?>/> (указать должность)
    </td>
  </tr>
  <tr>
    <td align=left>
      <input style="width:30%" name="zvan" <?php if (isset($_POST["zvan"])) {echo 'value="'.$_POST["zvan"].'"';};?>/> (указать звание)
    </td>
    <td align=left>
      <input style="width:80%" name="sotr" <?php if (isset($_POST["sotr"])) {echo 'value="'.$_POST["sotr"].'"';};?>/> (ФИО)
    </td>
  </tr>
  <tr>
    <td align=left><?=date("d.m.Y")?></td>
  </tr>
</table>
<input type="hidden" name="man_id" value="<?=$_GET["man_id"]?>"/>
<input type="hidden" name="ovd_id" value="<?=$_POST["ovd_id"]?>"/>
<input name="_submit" type="submit" value="Добавить" class="reg" id="reg_button"/>
</form>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
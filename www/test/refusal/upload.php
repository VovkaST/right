<?php
require('d:/www.sites/const.php');
require(KERNEL.'functions.php');
session_start();
if (isset($_SESSION["user"])) { // если авторизован (в сессии есть имя пользователя)
  if (isset($_POST["yours_ovd"]) && isset($_POST["service"])) { // если нажали ссылку службы и выбран ОВД
    unset($_SESSION["refusal"]); // начинаем регистрацию нового файла
    $_SESSION["refusal"]["ovd"] = $_POST["yours_ovd"];
    $_SESSION["refusal"]["slujba"] = $_POST["service"];
  }
  $sess_id = session_id();
  $_SESSION['end_session_time'] = time() + 1800;
  $_SESSION['last_active_date'] = date('Y-m-d');
  $_SESSION['last_active_time'] = date('H:i:s', time());
  if (!isset($_SESSION['dir_session'])) {
    $_SESSION['dir_session'] = DIR_SESSION."_tmp_".$sess_id."/"; // временный каталог сессии
  }
  if (!is_dir($_SESSION['dir_session'])) {
    mkdir($_SESSION['dir_session']); // создаем его
  }
  activity($_SESSION['activity_id']);
} else { // если пользователь не авторизован
  setcookie("none_auth", "1", 0x7FFFFFFF, "/"); // если не авторизован, ставим метку в куки
  $sess_id = session_id();
  if (isset($_POST["yours_ovd"]) || isset($_POST["service"])) {
    if (isset($_SESSION["refusal"])) {
      unset($_SESSION["refusal"]);
    }
    @$_SESSION["refusal"]["ovd"] = $_POST["yours_ovd"];
    @$_SESSION["refusal"]["slujba"] = $_POST["service"];
  }
  $_SESSION['last_active_date'] = date('Y-m-d');
  $_SESSION['last_active_time'] = date('H:i:s', time());
  if (!isset($_SESSION['dir_session'])) {
    $_SESSION['dir_session'] = DIR_SESSION."_tmp_".$sess_id."/"; // временный каталог сессии
  }
  if (!is_dir($_SESSION['dir_session'])) {
    mkdir($_SESSION['dir_session']); // создаем его
  }
  if (!isset($_SESSION['activity_id'])) {
    $_SESSION['activity_id'] = new_activity($sess_id, $_SERVER['REMOTE_ADDR'], "refuse_loader");
  }
}
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Загрузка файлов</title>
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="<?= IMG ?>vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <script src="<?= JS ?>jquery-1.10.2.js"></script>
  <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script src="<?= JS ?>procedures.js"></script>
  <script>
    $(function(){
      $("#kusp_num").onlyNumbers("Номер КУСП должен содержать только числа!");
    });
    $(function(){
      $('#av_f, #av_i, #av_o, #offender_f, #offender_i, #offender_o').onlyLetters('Значение поля должно содержать только буквенные значения!', 2, ["нет", "отсут", "устан", "неустан", "не устан", "н/у", "нз", "хз", "работ", "не работ"]);
    });
  </script>
</head>
<!--[if IE]>
<style>
  fieldset {
    padding: 0px 9px 9px;
    border: 2px ridge white;
    margin-top: 2px;
    }
  #upload_form,#send_file,#file_form {
    margin-top: 5px;
    }
  #add_file {
    margin-top: 3px;
    }
  #send_file {
    margin-top: 0px;
    }
  .legend {
    padding-bottom: 5px;
    }
  .otkaz {
    padding: 0px 5px 5px 10px;
    }
  .str2 {
    width: 490px;
      }
  #s_otkaz, #n_otkaz, #fn_otkaz {
    width: 135px;
    }
  .status_radio {
    margin-left: -4px;
    }
  #status2 {
    margin-top: 0px;
    }
  .bp {
    margin: 0px;
    }
  .check {
    margin-bottom: 5px;
    }
  .datepicker {
    width: 80px;
    }
  #criminal_st_add {
    margin-top: 5px;
    }
  #add_av_org #av_org {
    width: 588px;
    }
  hr {
    border: 1px ridge white;
    }
  .check {
    margin-top: 2px;
    margin-bottom: -3px;
    }
  #str_unknown {
    margin-left: -4px;
    }
  #form_kusp {
    margin-top: 2px;
    }
</style>
<![endif]-->
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= REFUSAL_VIEW_UPLOAD ?>">Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела</a>
</div>
<div style="border: 2px solid orange;text-align: center;font-size: 20px;color: rgb(134, 65, 65);padding: 20px 0;background: rgb(255, 254, 194);margin: 0 0 20px;">
  C 16.00 до 17.00 18.08.2015 будут проводиться регламентные работы. В работе сервиса возможны перебои.
</div>
<div class="header">
  <p>РЕГИСТРАЦИЯ ЭЛЕКТРОННЫХ КОПИЙ ПОСТАНОВЛЕНИЙ<br>ОБ ОТКАЗЕ В ВОЗБУЖДЕНИИ УГОЛОВНОГО ДЕЛА</p>
</div>
<center><h3 style="color: red">ВНИМАТЕЛЬНО СМОТРИТЕ КАКОЙ ГОД РЕШЕНИЯ И РЕГИСТРАЦИИ КУСП ВВОДИТЕ</h3></center>
<div class="choose_file">
  <fieldset>
    <legend><b>Выберите файл для загрузки*:</b></legend>
    <form id="upload_form" method="POST" enctype="multipart/form-data" action="otkaz_send.php" target="frame">
      <input type="file" name="UpLoadFile" id="UpLoadFile" <?php if (isset($_SESSION["refusal"]["uploaded_file"])) echo 'style="color: transparent;" disabled'?>/>
    <?php if (isset($_SESSION["refusal"]["uploaded_file"])): ?>
      <div class="add_str_dis"><input type="image" class="add" id="add_criminal" src="<?= IMG ?>plus_disabled.png" disabled/></div>
    <?php else: ?>
      <div class="add_str"><input type="image" class="add" id="add_criminal" src="<?= IMG ?>plus.png" class="add"/></div>
    <?php endif; ?>
    </form>
    <iframe name="frame" id="frame" style="display: none"></iframe>
    <div id="file_form">
    <?php //-------- ищем загруженный файл --------//
    if (isset($_SESSION["refusal"]["uploaded_file"])): 
      $file_name = $_SESSION["refusal"]["uploaded_file"];
      $file_type = substr(strrchr($file_name, "."), 1);
      if (strlen($file_name) > 33) {
        $file_name = mb_strcut($file_name, 0, 50, 'UTF-8').'...'.$file_type;
      }?>
      <form id="uploaded_file" enctype="multipart/form-data" method="POST">
        Файл: <b><?= $file_name; ?></b>
        <label class="del_str"><input type="image" src="#" class="del" /><span class="del_str_but"><strong>&times;</strong>Удалить</span></label>
      </form>      
    <?php endif; //-------- ищем загруженный файл --------//?>
    </div>
    <div class="prim">
      <b>*</b>допустимы документы MS Word типа *.doc, *.docx, *.rtf размером до 2 Мб
    </div>
  </fieldset>
</div>
<form id="form_kusp" method="POST">
  <div class="form_otkaz">
    <div class="status">
      <fieldset class="otkaz">
        <legend class="legend"><b>Отказной:</b></legend>
        <label class="status_radio"><input type="radio" name="status" id="status1" value="1" <?php if (isset($_SESSION['refusal']['status']) && $_SESSION['refusal']['status'] == 1) echo 'checked';?>/>Первичный</label><br/>
        <label class="status_radio"><input type="radio" name="status" id="status2" value="2" <?php if (isset($_SESSION['refusal']['status']) && $_SESSION['refusal']['status'] == 2) echo 'checked';?>/>По доп.проверке</label>
      </fieldset>
    </div>
    <div class="maker">
      <div class="str1">
        <div class="ovd">ОВД:
          <select name="ovd_otkaz" id="ovd_otkaz">
            <?=sel_ovd($_SESSION['refusal']['ovd']);?>
          </select>
        </div>
        <div class="slujba">Служба:
          <select name="slujba" id="slujba_otkaz">
            <?php if (isset($_SESSION['refusal']['slujba'])) {echo sel_slujba($_SESSION['refusal']['slujba']);} else {echo sel_slujba();}?>
          </select>
        </div>
      </div>
      <div class="str2">Вынес:
        <input name="surname" id="s_otkaz" placeholder="Фамилия" value="<?php if (isset($_SESSION['refusal']['surname'])) echo $_SESSION['refusal']['surname'];?>"/>
        <input name="name" id="n_otkaz" placeholder="Имя" value="<?php if (isset($_SESSION['refusal']['name'])) echo $_SESSION['refusal']['name'];?>"/>
        <input name="father_name" id="fn_otkaz" placeholder="Отчество" value="<?php if (isset($_SESSION['refusal']['father_name'])) echo $_SESSION['refusal']['father_name'];?>"/>
      </div>      
      <div class="upk">Основание: п.
        <select name="upk" id="upk_otkaz">
          <option selected style="display: none" value=""></option>
          <?php if (isset($_SESSION['refusal']['upk'])) {echo sel_upk($_SESSION['refusal']['upk']);} else {echo sel_upk();}?>
        </select>
        ч.1 ст.24 УПК РФ.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Дата решения:
        <input id="datepicker_1" name="otk_date" class="datepicker" placeholder="Дата" value="<?php if (isset($_SESSION['refusal']['otk_date'])) echo $_SESSION['refusal']['otk_date'];?>"/>
        <label class="bp"><input type="checkbox" id="bp" name="bp" value="1" <?php if (isset($_SESSION['refusal']['bp'])) echo 'checked';?>/>По без вести пропавшему</label>
      </div>
    </div>
  </div>
</form>

<div class="kusp">
  По сообщениям, зарегистрированным в КУСП:
  <form action="otkaz_send.php" id="add_kusp_form">
    <select name="kusp_ovd" id="kusp_ovd">
      <?=sel_ovd($_SESSION['refusal']['ovd']);?>
    </select>
    <div class="kusp_str">
      <input type="text" name="kusp_num" id="kusp_num" class="kusp_num" placeholder="№ КУСП"/>
      <input type="text" id="datepicker_2" name="kusp_date" class="datepicker" placeholder="Дата рег."/>
      <div class="add_str"><input type="image" id="add_KUSP" name="add_KUSP" src="<?= IMG ?>plus.png" class="add"/></div>
    </div>
  </form>
</div>

<div class="added_kusp">
  <?php if(isset($_SESSION['refusal']['kusp'])): ?>
    <?= added_kusp(IMG); ?>
  <?php endif; ?>
</div>
<div id="criminal_block">
  <fieldset id="criminal_add" <?php if (isset($_SESSION['refusal']['bp'])) echo 'style="display: none;"'; ?>>
    <legend><b>По признакам преступления (УК РФ):</b></legend>
    <form class="criminal_st_form" id="criminal_st_add" method="POST">
      
        <?php if (isset($_SESSION['refusal']['uk'])) {echo sel_uk($_SESSION['refusal']['uk']);} else {echo sel_uk();} ?>
      <div class="add_str"><input type="image" id="add_criminal" src="<?= IMG ?>plus.png" class="add"/></div>
    </form>
    <?php if(isset($_SESSION['refusal']['uk'])) {echo added_criminal(IMG);} ?>
  </fieldset>
</div>
<div id="decl_vict">
  <fieldset>
    <legend><b>Заявитель/Потерпевший/Пропавший без вести:</b></legend>
      <label class="anon_emp"><input type="checkbox" id="anonymous" name="anonymous" <?php if (isset($_SESSION['refusal']["anonymous"])) echo "checked"; if (isset($_SESSION['refusal']["decl_emp"])) echo "disabled";?>/>Анонимное сообщение (заявитель или потерпевший не установлены)</label><br/>
      <label class="anon_emp"><input type="checkbox" id="decl_emp" name="decl_emp" <?php if (isset($_SESSION['refusal']["decl_emp"])) echo "checked"; if (isset($_SESSION['refusal']["anonymous"])) echo "disabled";?>/>Рапорт сотрудника/сообщение прокурора</label>
    <fieldset>
      <legend>Физическое лицо</legend>
      <form id="add_applicant_victim" method="POST">
        <div class="check">
          <label class="relative"><input type="checkbox" class="declarer" id="av_declarer" name="declarer" <?php if (isset($_SESSION['refusal']["anonymous"]) || isset($_SESSION['refusal']["decl_emp"])) echo "disabled";?>/><b>Заявитель</b></label>
          <label class="relative"><input type="checkbox" class="victim" id="av_victim" name="victim" <?php if (isset($_SESSION['refusal']["anonymous"])) echo "disabled";?>/><b>Потерпевший</b></label>
          <label class="relative"><input type="checkbox" class="missing" id="av_missing" name="missing"/><b>Пропавший без вести</b></label>
        </div>
        <div class="av">
          <input type="text" name="av_f" id="av_f" placeholder="Фамилия"/>
          <input type="text" name="av_i" id="av_i" placeholder="Имя"/>
          <input type="text" name="av_o" id="av_o" placeholder="Отчество"/>
          <input type="text" id="datepicker_3" name="av_dr" class="av_dr datepicker" placeholder="Дата рожд."/>
          <div class="add_str"><input type="image" id="add_dv" src="<?= IMG ?>plus.png" class="add"/></div>
        </div>
      </form>
    </fieldset>
    <fieldset>
      <legend>Юридическое лицо (Организация)</legend>
      <form id="add_av_org" method="POST">
        <div class="check">
          <label class="relative"><input type="checkbox" class="declarer" id="av_org_declarer" name="declarer" <?php if (isset($_SESSION['refusal']["anonymous"]) || isset($_SESSION['refusal']["decl_emp"])) echo "disabled";?>/><b>Заявитель</b></label>
          <label class="relative"><input type="checkbox" class="victim" id="av_org_victim" name="victim" <?php if (isset($_SESSION['refusal']["anonymous"])) echo "disabled";?>/><b>Потерпевший</b></label>
        </div>
        <div class="av_org">
          <input type="text" name="av_org" id="av_org" placeholder="Наименование организации"/>
          <div class="add_str"><input type="image" id="add_dv" src="<?= IMG ?>plus.png" class="add"/></div>
        </div>
      </form>
    </fieldset>
    <hr/>
    <div class="added_av">
    <?php if(isset($_SESSION['refusal']['av'])) {
      echo added_faces($_SESSION['refusal']['av'], "added_av_str", IMG);
    }
    ?>
    <?php if(isset($_SESSION['refusal']['org'])) {
      echo added_organisations($_SESSION['refusal']["org"], 'v_org_added');
    }
    ?>
    </div>
  </fieldset>
</div>  
<div id="offender_form">
  <fieldset>
    <legend><b>Причастное (заподозренное) лицо:</b></legend>
    <form id="add_offender" method="POST">
      <div class="offender">
        <input type="text"  name="offender_f" id="offender_f" placeholder="Фамилия"/>
        <input type="text"  name="offender_i" id="offender_i" placeholder="Имя"/>
        <input type="text"  name="offender_o" id="offender_o" placeholder="Отчество"/>
        <input type="text"  id="datepicker_4" name="offender_dr" class="offender_dr datepicker" placeholder="Дата рожд."/>
        <div class="add_str"><input type="image" id="add_offender_button" src="<?= IMG ?>plus.png" class="add"/></div>
      </div>
    </form>
    <hr/>
    <div class="added_offender">
      <?php if (isset($_SESSION['refusal']['offender'])) echo added_faces($_SESSION['refusal']['offender'], "added_offender_str", IMG); ?>
    </div>
  </fieldset>
</div>
<input type="submit" id="reg_button" name="reg" class="reg" value="Зарегистрировать"/>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
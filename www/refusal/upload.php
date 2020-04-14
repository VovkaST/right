<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_SESSION['dir_session'])) {
  $_SESSION['dir_session'] = session_save_path()."_tmp_".session_id().'\\';
  if (!is_dir($_SESSION['dir_session'])) mkdir($_SESSION['dir_session']);
}
if (isset($_POST['ovd'])) $_SESSION['refusal']['ovd'] = $_POST['ovd'];
if (isset($_POST['service'])) $_SESSION['refusal']['service'] = $_POST['service'];
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Загрузка файлов</title>
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <!--[if IE]>
  <link rel="stylesheet" href="<?= CSS ?>ie_fi1x.css">
  <![endif]-->
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="<?= IMG ?>vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <script src="<?= JS ?>jquery-1.10.2.js"></script>
  <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script src="<?= JS ?>functions.js"></script>
  <script>
    $(function(){
      $("#kusp_num").onlyNumbers("Номер КУСП должен содержать только числа!");
    });
    $(function(){
      $('#av_f, #av_i, #av_o, #offender_f, #offender_i, #offender_o').onlyLetters('Значение поля должно содержать только буквенные значения!', 2, ["нет", "отсут", "устан", "неустан", "не устан", "н/у", "нз", "хз", "работ", "не работ"]);
    });

    function toggle_checks(elem) {
      var form = elem.closest('form');
      var rem = false;
      var inputs = form.find('input[type="checkbox"]');
      inputs.each(function(){
        if ($(this).prop('checked')) {
          rem = true;
        }
      });
      if (rem) {
        inputs.each(function(){
          $(this).removeAttr('req');
        });
      } else {
        inputs.each(function(){
          $(this).attr('req', 'true');
        });
      }
    }
  </script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= REFUSAL_VIEW_UPLOAD ?>">Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела</a>
</div>
<center><span style="font-size: 1.2em;"><strong>РЕГИСТРАЦИЯ ЭЛЕКТРОННОЙ КОПИИ ПОСТАНОВЛЕНИЯ<br>ОБ ОТКАЗЕ В ВОЗБУЖДЕНИИ УГОЛОВНОГО ДЕЛА</strong></span></center>
<div class="strip"></div>
<div class="choose_file">
  <div class="pre_upload_form">Файл: </div>
  <form class="upload_form">
    <?php echo (isset($_SESSION["refusal"]["uploaded_file"])) ? file_input('UpLoadFile', true) : file_input('UpLoadFile') ?>
  </form>
  <div class="uploaded_file_form">
  <?php //-------- ищем загруженный файл --------//
  if (isset($_SESSION["refusal"]["uploaded_file"])): 
    $file_name = $_SESSION["refusal"]["uploaded_file"];
    $file_type = substr(strrchr($file_name, "."), 1);
    if (strlen($file_name) > 33) {
      $file_name = mb_strcut($file_name, 0, 50, 'UTF-8').'...'.$file_type;
    }?>
    <form class="uploaded_file" method="POST">
      Файл: <b><?= $file_name; ?></b>
      <label class="del_str"><input type="image" src="#" class="del" /><span class="del_str_but"><strong>&times;</strong>Удалить</span></label>
      <input type="hidden" name="file_delete" value="true"/>
    </form>
  <?php endif; //-------- ищем загруженный файл --------//?>
  </div>
  <div class="prim">
    <b>*</b>допустимы документы MS Word типа *.doc, *.docx, *.rtf размером до 2 Мб
  </div>
</div>
<form method="POST">
  <div class="form_otkaz">
    <div class="status">
      <fieldset class="otkaz">
        <legend class="legend"><b>Отказной:</b></legend>
        <label class="status_radio"><input type="radio" name="status" id="status1" value="1" <?php if (isset($_SESSION['refusal']['status']) && $_SESSION['refusal']['status'] == 1) echo 'checked';?>/>Первичный</label><br/>
        <label class="status_radio"><input type="radio" name="status" id="status2" value="2" <?php if (isset($_SESSION['refusal']['status']) && $_SESSION['refusal']['status'] == 2) echo 'checked';?>/>По доп.проверке</label>
      </fieldset>
    </div>
    <div class="refusal_maker">
      <div class="refusal_maker_str">
        <div class="ovd">ОВД:
          <?php echo isset($_SESSION['refusal']['ovd']) ? sel_ovd('ovd_otkaz', $_SESSION['refusal']['ovd']) : sel_ovd('ovd_otkaz') ?>
        </div>
        <div class="slujba">Служба:
          <select name="slujba" id="slujba_otkaz">
            <?php if (isset($_SESSION['refusal']['service'])) {echo sel_slujba($_SESSION['refusal']['service']);} else {echo sel_slujba();}?>
          </select>
        </div>
      </div>
      <div class="refusal_maker_str">Вынес:
        <input name="surname" class="employeer" placeholder="Фамилия" value="<?php if (isset($_SESSION['refusal']['emp_s'])) echo $_SESSION['refusal']['emp_s'];?>"/>
        <input name="name" class="employeer" placeholder="Имя" value="<?php if (isset($_SESSION['refusal']['emp_n'])) echo $_SESSION['refusal']['emp_n'];?>"/>
        <input name="father_name" class="employeer" placeholder="Отчество" value="<?php if (isset($_SESSION['refusal']['emp_fn'])) echo $_SESSION['refusal']['emp_fn'];?>"/>
      </div>      
      <div class="refusal_maker_str">Основание: п.
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
  <form action="procedures.php" id="add_kusp_form">
    <?php echo isset($_SESSION['refusal']['ovd']) ? sel_ovd('kusp_ovd', $_SESSION['refusal']['ovd']) : sel_ovd('kusp_ovd') ?>
    <div class="kusp_str">
      <input type="text" name="kusp_num" id="kusp_num" class="kusp_num" placeholder="№ КУСП" req="true"/>
      <input type="text" id="datepicker_2" name="kusp_date" class="datepicker" placeholder="Дата рег." req="true"/>
      <?= save_button('Добавить') ?>
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
      <?= save_button('Добавить') ?>
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
          <label class="relative"><input type="checkbox" class="declarer" id="av_declarer" name="declarer" <?php if (isset($_SESSION['refusal']["anonymous"]) || isset($_SESSION['refusal']["decl_emp"])) echo "disabled";?> req="true" onclick="toggle_checks($(this));"/><b>Заявитель</b></label>
          <label class="relative"><input type="checkbox" class="victim" id="av_victim" name="victim" <?php if (isset($_SESSION['refusal']["anonymous"])) echo "disabled";?> req="true" onclick="toggle_checks($(this));"/><b>Потерпевший</b></label>
          <label class="relative"><input type="checkbox" class="missing" id="av_missing" name="missing" req="true" onclick="toggle_checks($(this));"/><b>Пропавший без вести</b></label>
        </div>
        <div class="av">
          <input type="text" name="av_f" id="av_f" placeholder="Фамилия" req="true" autocomplete="off"/>
          <input type="text" name="av_i" id="av_i" placeholder="Имя" req="true" autocomplete="off"/>
          <input type="text" name="av_o" id="av_o" placeholder="Отчество" req="true"autocomplete="off" />
          <input type="text" id="datepicker_3" name="av_dr" class="av_dr datepicker" placeholder="Дата рожд." req="true" autocomplete="off"/>
          <?= save_button('Добавить') ?>
        </div>
      </form>
    </fieldset>
    
    <fieldset>
      <legend>Юридическое лицо (Организация)</legend>
      <form id="add_av_org" method="POST">
        <div class="check">
          <label class="relative">
            <input type="checkbox" class="declarer" id="av_org_declarer" name="declarer" <?php if (isset($_SESSION['refusal']["anonymous"]) || isset($_SESSION['refusal']["decl_emp"])) echo "disabled";?> req="true" onclick="toggle_checks($(this));"/>
            <b>Заявитель</b>
          </label>
          <label class="relative">
            <input type="checkbox" class="victim" id="av_org_victim" name="victim" <?php if (isset($_SESSION['refusal']["anonymous"])) echo "disabled";?> req="true" onclick="toggle_checks($(this));"/>
            <b>Потерпевший</b>
          </label>
        </div>
        <div class="av_org">
          <input type="text" name="av_org" id="av_org" placeholder="Наименование организации"/>
          <?= save_button('Добавить') ?>
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
    <div style="margin: 5px 0 10px 0;text-align: center;">
      В соответствии с ч.1 ст.148 УПК РФ отказ в возбуждении уголовного дела по основанию, предусмотренному <b>п.2 ч.1 ст.24 УПК РФ</b>, допускается <b>лишь в отношении конкретного лица</b>.
    </div>
    <form id="add_offender" method="POST">
      <div class="offender">
        <input type="text"  name="offender_f" id="offender_f" placeholder="Фамилия" req="true"/>
        <input type="text"  name="offender_i" id="offender_i" placeholder="Имя" req="true"/>
        <input type="text"  name="offender_o" id="offender_o" placeholder="Отчество" req="true"/>
        <input type="text"  id="datepicker_4" name="offender_dr" class="offender_dr datepicker" placeholder="Дата рожд." req="true"/>
        <?= save_button('Добавить') ?>
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
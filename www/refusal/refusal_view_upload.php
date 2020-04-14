<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

//если нажали продолжить
if (isset($_POST["continue"])) {
	header('Location: upload.php');
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <title>Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела</title>
 <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
 <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= CSS ?>main.css">
 <link rel="stylesheet" href="<?= CSS ?>new.css">
 <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
 <script src="<?= JS ?>jquery-1.10.2.js"></script>
 <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
 <script src="<?= JS ?>functions.js"></script>
 <script src="js/procedures.js"></script>
 <script>
  $(function(){
    $(".sel_service").click(function(){
      $("#service").val(this.id);
      $("#sel_service_form").submit();
    });
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
  #sel_service_form {
    margin-top: 6px;
    }
  .clear_link {
    margin-top: -1px;
    }
</style>
<![endif]-->
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
<a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела
</div>
<a href="formation_results.php" class="resultsLink">Долги, недостатки и результаты формирования</a>
<fieldset id="refusal_view_upload_sel">
  <legend>Регистрация решений (электронного документа)</legend>
  <form method="POST" id="sel_service_form" action="upload.php">
  ОВД:
  <?php
    if (isset($_SESSION['ovd'])) {
      echo sel_ovd('ovd', $_SESSION['ovd']);
    } else {
      echo sel_ovd('ovd');
    }
  ?>
    <?= sel_slujba_link(); ?>
    <input type="hidden" name="service" id="service"/>
	</form>
  <?php if (isset($_SESSION["user"])) :?>
    <div class="my_button" id="search">
      <a href="#">Поиск</a>
    </div>
  <?php endif; ?>
  <?php if(isset($_SESSION["refusal"]) || isset($_COOKIE['none_auth'])) :?>
    <div class="my_button" id="continue">
      <a href="<?= ADDR ?>refusal/upload.php">Продолжить ввод</a>
    </div>
  <?php endif; ?>
  <?php if (isset($_SESSION["user"])) :?>
  <div id="search_block">
  
    <strong id="close_link">×</strong>
    <h2 style="text-align: center; margin: 2px 0 5px 0;">Поиск</h2>
    <hr/>
    <ul class="search_list">
      <h3 id="reg_head"><img src="<?= $img ?>search.png" style="height: 15px; vertical-align: middle; margin-right: 5px;"/>По рег.данным</h3>
      <form id="search_reg" name="search_reg" method="POST">
        ОВД:
        <?php
          if (isset($_SESSION['ovd'])) {
            echo sel_ovd('id_ovd', $_SESSION['ovd']);
          } else {
            echo sel_ovd('id_ovd');
          }
        ?>
        <li class="str_1">
          <div style=" display: table-cell; width: 200px; vertical-align: middle; ">
            Рег.№ <input type="text" name="otkaz_id" id="otkaz_id"/>
          </div>
          <div style=" display: table-cell; width: 301px; ">
            <label><input type="checkbox" name="status" id="status1" value="1"/>Первичный</label><br/>
            <label><input type="checkbox" name="status" id="status2" value="2"/>По доп.проверке</label>
          </div>
        </li>
        <li>
          Сотрудник: <input type="text" name="employeer" id="s_otkaz" placeholder="Фамилия"/>
        </li>
        <li>
          Служба: <select name="id_slujba" id="search_slujba_otkaz">
            <?= sel_slujba(); ?>
          </select>
        </li>
        <li>
          Основание: п. <select name="upk" id="upk_otkaz">
            <?=sel_upk();?>
          </select> ч.1 ст.24 УПК РФ
        </li>
        <li>
          Решение с: <input type="text" id="datepicker_1" name="start_otk_date" class="datepicker" placeholder="Дата"/>
           по: <input type="text" id="datepicker_2" name="end_otk_date" class="datepicker" placeholder="Дата"/>
        </li>
        <hr/>
        <li>
          ОВД:
            <?php echo isset($_SESSION['refusal']['ovd']) ? sel_ovd('KUSP_ovd', $_SESSION['refusal']['ovd']) : sel_ovd('KUSP_ovd') ?>
        </li>
        <li>
          КУСП: <input type="text" name="KUSP_num" id="kusp_num" class="kusp_num" placeholder="№ КУСП"/>
           от <input type="text" id="datepicker_3" name="KUSP_date" class="datepicker" placeholder="Дата рег."/>
        </li>
        <hr/>
        <li>
          ст.УК РФ: <input type="text" name="uk" id="uk_otkaz"/>
        </li>
      </form>
      <h3 id="face_head"><img src="<?= IMG ?>search.png" style="height: 15px; vertical-align: middle; margin-right: 5px;"/>По установочным данным лица</h3>
      <form id="search_face" name="search_face" method="POST">
        <li>
          <table id="search_table">
            <tr>
              <td>Фамилия: </td>
              <td><input type="text" name="surname" id="f_s_otkaz"/></td>
            </tr>
            <tr>
              <td>Имя: </td>
              <td><input type="text" name="name" id="f_n_otkaz"/></td>
            </tr>
            <tr>
              <td>Отчество: </td>
              <td><input type="text" name="fath_name" id="f_f_n_otkaz"/></td>
            </tr>
            <tr>
              <td>Д.рожд.: </td>
              <td><input type="text" id="datepicker_4" name="borth" class="datepicker"/></td>
            </tr>
            <tr>
              <td>Тип связи: </td>
              <td>
                <?= sel_relative(1, 0); ?>
              </td>
            </tr>
          </table>
        </li>
      </form>
      <div class="my_button" id="start_search">
        <a href="#">Искать</a>
      </div>
    </ul>
  </div>
  <?php endif; ?>
  </fieldset>
<div class="response_place" id="refusal" style="display: none;">
</div>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
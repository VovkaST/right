<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
//phpinfo(32);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset=utf-8>
  <title>...</title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <script src="<?= JS ?>jquery-1.10.2.js"></script>
  <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script src="<?= JS ?>procedures.js"></script>  
  <script>
    $(function(){
      $('#tel_add').click(function(){
        var tel_added = $("#tel_added");
        var addedCount = tel_added.children("div").length;
        //alert(tel_added.children("div").length);
        tel_added.append(
          $('<div name="victim_phone[' + (1 + addedCount) + ']"><input type="text" name=" " id=" " placeholder="Телефон ' + (1 + addedCount) + '"/></div>')
        );
        event.preventDefault ? event.preventDefault() : (event.returnValue = false);
      });
    });
    $(function(){
      $("#test").click(function(){
        var inp = $(this);
        $("#result_area")
          .offset({
            top: (inp.position().top + inp.outerHeight() - 1),
            left: inp.position().left})
          .css("display", "block");
      });
    });
    $(function(){
      $(".data_form").submit(function(){
        var form = $(this);
        $.ajax({
          type: "POST",
          url: "search.php",
          data: form.serialize()
        });
        return false;
      });
    });
  </script>
</head>
<style>
  input[type="text"], select {
    border: 1px solid rgb(169, 169, 169);
    padding: 2px 1px;
    }
  #result_area {
    display: none;
    position: absolute;
    background: bisque;
    border: 1px solid gray;
    min-width: 150px;
  }
</style>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<div>
  <form id="form_event" method="POST">
    <div>КУСП:
      <input type="text" placeholder="№" style="width: 60px;"/>
      от
      <input type="text" class="datepicker" placeholder="Дата"/>
      ОВД:
      <select name="ovd_otkaz" id="ovd_otkaz">
        <?= sel_ovd(); ?>
      </select>
    </div>  
    <div>
      Служба:
      <select name="service" id="">
        <?= sel_slujba(); ?>
      </select>
      Сотрудник:
      <input type="text" name="employeer" id="" placeholder="ФИО" value=""/>
    </div>
    <div>
      Должность:
      <input type="text" name="range" id="" placeholder="должность" value=""/>
      Конт.тел.:
      <input type="text" name="emp_tel" id="" placeholder="xxx-xxx-xx-xx" value=""/>
    </div>
    <div>У/д:
      <input type="text" name="crim_case" placeholder="№ у/д"/>
      от
      <input type="text" name="crim_case_date" class="datepicker" placeholder="Дата"/>
    </div>
  </form>
    <table width="100%" border="1" rules="all">
      <tr>
        <td align="center" colspan="3">Потерпевший</td>
      </tr>
      <tr>
        <td align="center">Установочные данные</td>
        <td align="center" width="35%">Телефонные номера/<br/>Мобильные устройства</td>
        <td align="center" width="35%">Банковские счета/<br/>Электронные кошельки</td>
      </tr>
      <tr>
        <td rowspan="2">
          <form id="form_victim" method="POST">
            <table>
              <tr>
                <td align="right">Фамилия:</td>
                <td><input type="text" name="vict_s" id=""/></td>
              </tr>
              <tr>
                <td align="right">Имя:</td>
                <td><input type="text" name="vict_n" id=""/></td>
              </tr>
              <tr>
                <td align="right">Отчество:</td>
                <td><input type="text" name="vict_fn" id=""/></td>
              </tr>
              <tr>
                <td align="right">Дата рожд.:</td>
                <td><input type="text" name="vict_bd" class="datepicker"/></td>
              </tr>
            </table>
          </form>
        </td>
        <td height="40px" style="border-bottom: none;">
          <form name="form_victim_phones" class="data_form" method="POST">
            Тип:
            <select name="form_victim_phones_type">
              <option></option>
              <option value="1">Телефонный номер</option>
              <option value="2">Мобильное устройство</option>
            </select><br/>
            Субъект РФ:
            <select name="form_victim_phones_subject">
              <option></option>
              <option value="1">Кировская область</option>
              <option value="2">Коми Республика</option>
            </select><br/>
            IMEI:<input type="text" id="test" name="form_victim_phones_num" id=""/>
            <div id="result_area">
              <ul class="quick_search_results_items">
                <li>Результат 1</li>
                <li>Результат 2</li>
                <li>Результат 3</li>
              </ul>
            </div>
            <div class="add_str"><input type="image" id=" " src="<?= IMG ?>plus.png" class="add"/></div>
          </form>
        </td>
        <td style="border-bottom: none;">
          <form name="form_victim_account" method="POST">
            Тип:
            <select name="form_victim_account_type">
              <option></option>
              <option value="1">Банковский счет</option>
              <option value="2">Электронный кошелек</option>
            </select><br/>
            Номер:
            <input type="text" name="form_victim_account_num" id=""/>
          </form>
        </td>
      </tr>
      <tr>
        <td align="center" style="border-top: none;">Отображаются сведения по устройствам</td>
        <td align="center" style="border-top: none;">Отображаются сведения по счетам</td>
      </tr>
      <tr>
        <td align="center" colspan="3">Подозреваемый</td>
      </tr>
    </table>
      <a href="#" id="tel_add">Добавить телефон</a>
      <div id="tel_added">
    </fieldset>
    <input type="submit">
</div>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
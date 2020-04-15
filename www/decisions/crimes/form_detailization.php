<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
is_ais();

if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <fieldset>
        <legend>Ввод строки "Сетевая активность":</legend>
        <form method="POST" class="data_form">
          <input type="hidden" name="data_form" value="form_detailization"/>
          <input type="hidden" name="viewed_obj" value="<?= $_GET["id"] ?>"/>
          <table width="100%" border="0" rules="none">
            <tr>
              <td align="right">Тип:<span class="req">*</span></td>
              <td><label><input type="radio" name="type" value="1" checked/>Соединение</label></td>
              <td colspan="2"><label><input type="radio" name="type" value="2"/>SMS</label></td>
            </tr>
            <tr>
              <td align="right">Направл.:<span class="req">*</span></td>
              <td><label><input type="radio" name="direction" value="1" checked/>Исходящее</label></td>
              <td colspan="2"><label><input type="radio" name="direction" value="2"/>Входящее</label></td>
            </tr>
            <tr>
              <td colspan="4"><hr/></td>
            </tr>
            <tr>
              <td align="right">Дата:<span class="req">*</span></td>
              <td><input type="text" name="connection_date" class="datepicker" autocomplete="off" req="true"/></td>
              <td align="right">Время:<span class="req">*</span></td>
              <td><input type="text" name="connection_time" class="time" autocomplete="off" req="true"/></td>
            </tr>
            <tr>
              <td align="right">Номер:<span class="req">*</span></td>
              <td><input type="text" name="subscriber" class="tel_num" autocomplete="off" req="true"/></td>
              <td align="right">Продолж.(сек.):</td>
              <td><input type="text" name="connection_length" class="connection_length" autocomplete="off"/></td>
            </tr>
            <tr>
              <td colspan="4" align="center"><hr/><b>Базовая станция</b></td>
            </tr>
            <tr>
              <td align="right">Код:</td>
              <td colspan="3"><input type="text" name="base_station_code" autocomplete="off"/></td>
            </tr>
            <tr>
              <td colspan="4" align="center">Адрес</td>
            </tr>
            <tr>
              <td colspan="4">
                <table border="0" rules="none" width="100%">
                  <tr>
                    <td align="right">Регион:<span class="req">*</span></td>
                    <td colspan="3">
                      <input type="text" name="region_text" id="region" class="ajax_search" autocomplete="off" req="true"/>
                      <div class="ajax_search_result"></div>
                      <input type="hidden" name="region"/>
                    </td>
                    <td width="30px" class="wait"></td>
                  </tr>
                  <tr>
                    <td align="right">Район:</td>
                    <td colspan="3">
                      <input type="text" name="district_text" id="district" class="ajax_search" autocomplete="off"/>
                      <div class="ajax_search_result"></div>
                      <input type="hidden" name="district"/>
                    </td>
                    <td class="wait"></td>
                  </tr>
                  <tr>
                    <td align="right">Город:</td>
                    <td colspan="3">
                      <input type="text" name="city_text" id="city" class="ajax_search" autocomplete="off"/>
                      <div class="ajax_search_result"></div>
                      <input type="hidden" name="city"/>
                    </td>
                    <td class="wait"></td>
                  </tr>
                  <tr>
                    <td align="right">Нас.пункт:</td>
                    <td colspan="3">
                      <input type="text" name="locality_text" id="locality" class="ajax_search" autocomplete="off"/>
                      <div class="ajax_search_result"></div>
                      <input type="hidden" name="locality"/>
                    </td>
                    <td class="wait"></td>
                  </tr>
                  <tr>
                    <td align="right">Улица:</td>
                    <td colspan="3">
                      <input type="text" name="street_text" id="street" class="ajax_search" autocomplete="off"/>
                      <div class="ajax_search_result"></div>
                      <input type="hidden" name="street"/>
                    </td>
                    <td class="wait"></td>
                  </tr>
                  <tr>
                    <td align="right">Дом:</td>
                    <td><input type="text" name="house" style="width: 30px;" autocomplete="off"/></td>
                    <td colspan="2">Литера: <input type="text" name="house_lit" style="width: 30px;" autocomplete="off"/></td>
                    <td></td>
                  </tr>
                  <tr>
                    <td align="right">Квартира:</td>
                    <td width="60px"><input type="text" name="flat" style="width: 30px;" autocomplete="off"/></td>
                    <td colspan="2">Литера: <input type="text" name="flat_lit" style="width: 30px;" autocomplete="off"/></td>
                    <td></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr height="25px">
              <td colspan="4" align="center">
                 <?= save_button('Добавить') ?>
              </td>
            </tr>
          </table>
        </form>
      </fieldset>
    </td>
    <td width="60%" valign="top" style="padding: 10px 0;">
      <fieldset class="related_objects">
        <?= connection_indicators($_GET["id"]); ?>
      </fieldset>
    </td>
  </tr>
</table>
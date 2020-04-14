<?php
$need_auth = 0;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
$types = array($_GET["object"], 3);
sort($types, SORT_NUMERIC);
?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <fieldset>
        <legend>Ввод связи с объектом "Адрес"<b>*</b>:</legend>
        <form method="POST" class="data_form" name="form_address">
          <input type="hidden" name="data_form" value="form_address"/>
          <input type="hidden" name="viewed_obj" value="<?= $_GET["id"] ?>"/>
          <input type="hidden" name="viewed_obj_type" value="<?= $_GET["object"] ?>"/>
          <table width="100%" border="0" rules="none">
            <tr>
              <td align="right" width="90px">Тип связи:<span class="req">*</span></td>
              <td colspan="4"><?= sel_relative($types[0], $types[1]); ?></td>
            </tr>
            <tr>
              <td colspan="5"><hr/></td>
            </tr>
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
              <td colspan="2">Литера:<input type="text" name="house_lit" style="width: 30px;" autocomplete="off"/></td>
              <td></td>
            </tr>
            <tr>
              <td align="right">Квартира:</td>
              <td width="60px"><input type="text" name="flat" style="width: 30px;" autocomplete="off"/></td>
              <td colspan="2">Литера:<input type="text" name="flat_lit" style="width: 30px;" autocomplete="off"/></td>
              <td></td>
            </tr>
            <tr height="25px">
              <td colspan="5" align="center">
                <?= save_button('Добавить') ?>
              </td>
            </tr>
            <tr height="25px">
              <td colspan="5" align="right">
                <b>*</b>только соответствующих КЛАДР
              </td>
            </tr>
          </table>
        </form>
      </fieldset>
    </td>
    <td width="60%" valign="top" style="padding: 10px 0;">
      <fieldset class="related_objects">
        <?= related_address($_GET["id"], $_GET["object"]) ?>
      </fieldset>
    </td>
  </tr>
</table>
<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
is_ais(array(1, 2));

if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <?php if ($_GET["object"] == 2) :?>
      <fieldset>
        <legend>Ввод строки "Запрос/ответ":</legend>
        <form method="POST" class="data_form">
          <input type="hidden" name="data_form" value="form_requests"/>
          <input type="hidden" name="viewed_obj" value="<?= $_GET["id"] ?>"/>
          <table width="100%" border="0" rules="none">
            <tr>
              <td colspan="4" align="center"><b>Направлен запрос в рамках у/д</b></td>
            </tr>
            <tr>
              <td colspan="4"><hr/></td>
            </tr>
            <tr>
              <td align="right" width="75px">Тип:<span class="req">*</span></td>
              <td colspan="3"><?= request_types_select() ?></td>
            </tr>
            <tr>
              <td align="right">Куда:<span class="req">*</span></td>
              <td colspan="3"><input type="text" name="organisation"/></td>
            </tr>
            <tr>
              <td align="right">Дата:<span class="req">*</span></td>
              <td><input type="text" name="request_date" class="datepicker" autocomplete="off"/></td>
              <td align="right">Исх.№:<span class="req">*</span></td>
              <td><input type="text" name="request_number" class="request_number" style="width: 60px;" autocomplete="off"/></td>
            </tr>
            <tr height="25px">
              <td colspan="4" align="center">
                <?= save_button('Добавить') ?>
              </td>
            </tr>
          </table>
        </form>
      </fieldset>
      <?php else : ?>
      <center><b>Фигурировал в запросе:</b></center>
      <?php endif; ?>
    </td>
    <td width="60%" valign="top" style="padding: 10px 0;">
      <fieldset class="related_objects">
        <?= related_requests($_GET["id"], $_GET["object"]); ?>
      </fieldset>
    </td>
  </tr>
</table>
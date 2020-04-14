<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
$types = array($_GET["object"], 1);
sort($types, SORT_NUMERIC);
?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <fieldset>
        <legend>Ввод связи с объектом "Лицо":</legend>
        <form method="POST" class="data_form" name="form_name">
          <input type="hidden" name="data_form" value="form_face"/>
          <input type="hidden" name="viewed_obj" value="<?= $_GET["id"] ?>"/>
          <input type="hidden" name="viewed_obj_type" value="<?= $_GET["object"] ?>"/>
          <table width="100%">
            <tr>
              <td align="right">Тип связи:<span class="req">*</span></td>
              <td><?= sel_relative($types[0], $types[1]); ?></td>
            </tr>
            <tr>
              <td colspan="2"><hr/></td>
            </tr>
            <tr>
              <td align="right">Фамилия:<span class="req">*</span></td>
              <td><input type="text" name="surname" req="true"/></td>
            </tr>
            <tr>
              <td align="right">Имя:<span class="req">*</span></td>
              <td><input type="text" name="name" req="true"/></td>
            </tr>
            <tr>
              <td align="right">Отчество:</td>
              <td><input type="text" name="fath_name"/></td>
            </tr>
            <tr>
              <td align="right">Дата рожд.:</td>
              <td><input type="text" name="borth" class="datepicker"/></td>
            </tr>
            <tr height="25px">
              <td colspan="2" align="center">
                <?= save_button('Добавить') ?>
              </td>
            </tr>
          </table>
        </form>
      </fieldset>
    </td>
    <td width="60%" valign="top" style="padding: 10px 0;">
      <fieldset class="related_objects">
        <?php echo related_faces($_GET["id"], $_GET["object"]) ?>
      </fieldset>
    </td>
  </tr>
</table>
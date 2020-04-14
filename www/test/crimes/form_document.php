<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
$types = array($_GET["object"], 4);
sort($types, SORT_NUMERIC);
?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <fieldset>
        <legend>Ввод связи с объектом "Документ":</legend>
        <form method="POST" class="data_form">
          <input type="hidden" name="data_form" value="form_document"/>
          <input type="hidden" name="viewed_obj" value="<?= $_GET["id"] ?>"/>
          <input type="hidden" name="viewed_obj_type" value="<?= $_GET["object"] ?>"/>
          <table width="100%">
            <tr>
              <td align="right">Тип связи:<span class="req">*</span></td>
              <td colspan="3"><?= sel_relative($types[0], $types[1]); ?></td>
            </tr>
            <tr>
              <td colspan="4"><hr/></td>
            </tr>
            <tr>
              <td align="right">Тип:<span class="req">*</span></td>
              <td colspan="3">
                <?= sel_documents() ?>
              </td>
            </tr>
            <tr>
              <td align="right">Серия:<span class="req">*</span></td>
              <td><input type="text" name="serial" class="doc_serial" req="true"/></td>
              <td align="right">Номер:<span class="req">*</span></td>
              <td><input type="text" name="number" class="doc_number" req="true"/></td>
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
        <?= related_documents($_GET["id"], $_GET["object"]) ?>
      </fieldset>
    </td>
  </tr>
</table>
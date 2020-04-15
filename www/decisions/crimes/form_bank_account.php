<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
is_ais();

if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
$types = array($_GET["object"], 7);
sort($types, SORT_NUMERIC);
?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <fieldset>
        <legend>Ввод связи с объектом "Банковский счет":</legend>
        <form method="POST" class="data_form">
          <input type="hidden" name="data_form" value="form_bank_account"/>
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
              <td align="right">№:<span class="req">*</span></td>
              <td><input type="text" name="number" class="account_num" req="true"/></td>
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
        <?= related_bank_accounts($_GET["id"], $_GET["object"]) ?>
      </fieldset>
    </td>
  </tr>
</table>
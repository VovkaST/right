<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
is_ais(array(1, 2, 9));

if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
switch($_GET["object"]) {
  case 1:
    $rel = 'Пользуется / имеет личный аккаунт';
    break;
  case 2:
    $rel = 'Использовался при совершении<br/>преступления';
    break;
  case 9:
    $rel = 'Пополнение баланса аккаунта';
    break;
  default:
    exit('<div id="error"><p>Неподходящий объект...</p></div>');
    break;
}
?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <fieldset>
        <legend>Ввод строки "Интернет-мессенджер":</legend>
        <form method="POST" class="data_form">
          <input type="hidden" name="data_form" value="form_messenger"/>
          <input type="hidden" name="viewed_obj" value="<?= $_GET["id"] ?>"/>
          <input type="hidden" name="viewed_obj_type" value="<?= $_GET["object"] ?>"/>
          <table width="100%" border="0" rules="none">
            <tr>
              <td colspan="2" align="center"><b><?= $rel ?></b></td>
            </tr>
            <tr>
              <td colspan="2"><hr/></td>
            </tr>
            <tr>
              <td align="right" width="75px">Тип:<span class="req">*</span></td>
              <td><?= messenger_select() ?></td>
            </tr>
            <tr>
              <td align="right">Аккаунт:<span class="req">*</span></td>
              <td><input type="text" name="account"/></td>
            </tr>
            <tr>
              <td align="right">Ник / имя:</td>
              <td><input type="text" name="nick"/></td>
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
        <?= related_messengers($_GET["id"], $_GET["object"]); ?>
      </fieldset>
    </td>
  </tr>
</table>
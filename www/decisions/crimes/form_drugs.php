<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
is_ais();

if (!isset($_GET["object"]) || !isset($_GET["id"])) {
  exit('<div id="error"><p>Ошибка GET параметра!</p></div>');
}
switch($_GET["object"]) {
  case 2:
    break;
  default:
    exit('<div id="error"><p>Неподходящий объект...</p></div>');
    break;
}

function drug_types() {
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      dt.`id`, dt.`type`
    FROM
      `spr_drug_types` as dt
  ');
  $ret = '<select name="type" class="drug_type">';
  $ret .= '<option></option>';
  while($result = mysql_fetch_assoc($query)) {
    $ret .= '<option value="'.$result['id'].'">'.$result['type'].'</option>';
  }
  $ret .= '</select>';
  return $ret;
}

?>
<table width="100%">
  <tr>
    <td width="40%" valign="top" style="padding: 10px 0;">
      <fieldset>
        <legend>Ввод строки "Изъятые наркотики":</legend>
        <form method="POST" class="data_form">
          <input type="hidden" name="data_form" value="form_drugs"/>
          <input type="hidden" name="viewed_obj" value="<?= $_GET["id"] ?>"/>
          <input type="hidden" name="viewed_obj_type" value="<?= $_GET["object"] ?>"/>
          <table width="100%">
            <tr>
              <td colspan="2" align="center"><b>Изъято наркотических средств</b></td>
            </tr>
            <tr>
              <td colspan="2"><hr/></td>
            </tr>
            <tr>
              <td width="80px" align="right">Тип:<span class="req">*</span></td>
              <td><?= drug_types() ?></td>
            </tr>
            <tr>
              <td align="right">Вещество:<span class="req">*</span></td>
              <td>
                <?= my_select('drug') ?>
              </td>
            </tr>
            <tr>
              <td align="right">Масса (гр.):<span class="req">*</span></td>
              <td>
                <input type="text" name="weight" class="weight"/>
              </td>
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
        <?= related_drugs($_GET["id"]) ?>
      </fieldset>
    </td>
  </tr>
</table>
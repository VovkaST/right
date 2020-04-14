<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$object = 4;
if (!isset($_GET["doc_id"]) || !is_numeric($_GET["doc_id"])) {
  header('Location: index.php');
}
$doc_id = floor(abs($_GET["doc_id"]));
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    id,
    type,
    serial,
    number,
    DATE_FORMAT(`date`, "%d.%m.%Y") as `date`,
    by_whom,
    status
  FROM
    o_documents
  WHERE
    id = "'.$doc_id.'"
  LIMIT 1
');
$result = mysql_fetch_assoc($query);
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Отдел оперативно-разыскной информации</title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <link rel="stylesheet" href="css/new_tmp.css">
  <script type="text/javascript" src="<?= JS ?>jquery-1.10.2.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="<?= JS ?>procedures.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery.inputmask.js"></script>
  <script type="text/javascript" src="js/procedures.js"></script>
  <script type="text/javascript" src="<?= JS ?>functions.js"></script>
</head>
  <!--[if IE]>
  <link rel="stylesheet" href="<?= CSS ?>ie_fix.css">
  <![endif]-->
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<form class="current_object_form" id="<?= $doc_id ?>">
  <center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_document"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?= $doc_id ?>"/>
  </div>
  <table border="0" rules="none" align="center" width="100%" class="object_view" object="<?= $object ?>">
    <tr>
      <td align="right" width="50px">Тип:</td>
      <td colspan="5">
        <?= ($result['type'] != '') ? sel_documents($result['type']) : sel_documents() ?>
      </td>
    </tr>
    <tr>
      <td align="right">Серия:</td>
      <td width="80px"><input type="text" name="serial" class="doc_serial" <?= ($result['serial'] != '') ? 'value="'.$result['serial'].'"' : ''; ?>/></td>
      <td align="right" width="40px">Номер:</td>
      <td width="130px"><input type="text" name="number" class="doc_number" <?= ($result['number'] != '') ? 'value="'.$result['number'].'"' : ''; ?>/></td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td align="right">Выдан:</td>
      <td colspan="3"><input type="text" name="date" class="datepicker" <?= ($result['date'] != '') ? 'value="'.$result['date'].'"' : ''; ?>/></td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td align="right">Кем:</td>
      <td colspan="3"><textarea name="by_whom" class="doc_department" rows="2"><?php if ($result['by_whom'] != '') echo $result['by_whom'] ?></textarea></td>
      <td align="right" width="50px">Статус:</td>
      <td>
        <label><input type="radio" name="status" value="1" <?php if ($result['status'] == 1) echo 'checked' ?>/>Действующий</label>
        <label><input type="radio" name="status" value="0" <?php if ($result['status'] == 0) echo 'checked' ?>/>Не действующий</label>
      </td>
    </tr>
    <tr>
      <td colspan="6" align="center">
        <?= save_button('Сохранить') ?>
      </td>
    </tr>
  </table>
</form>
<table rules="none" border="0" width="100%" class="objects_table">
  <tr class="table_head">
    <td align="center" colspan="2">
      <b>Объекты:</b>
      <hr color="#C6C6C6" size="0px"/>
    </td>
  </tr>
  <tr class="table_head objects_list">
    <td align="center" colspan="2" height="40px" style="padding: 5px 0;">
      <div class="objects_list_block">
        <?= sel_objects($doc_id, $object) ?>
      </div>
    </td>
  </tr>
  <tr class="result_data_row">
    <td colspan="2" width="40%" style="padding: 10px 0;"></td>
  </tr>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
</body>
</html>
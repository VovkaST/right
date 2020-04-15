<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));

$object = 1;
if (!isset($_GET["face_id"]) || !is_numeric($_GET["face_id"])) {
  header('Location: index.php');
}
$face_id = floor(abs($_GET["face_id"]));
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    l.`id`, l.`surname`, l.`name`, l.`fath_name`,
    DATE_FORMAT(l.`borth`, "%d.%m.%Y") as borth
  FROM
    `o_lico` as l
  WHERE
    `id` = "'.$face_id.'"
  LIMIT 1
');
$result = mysql_fetch_assoc($query);
if ($result['id'] < 1) header('Location: index.php'); // если лица не существует, выходим на index.php
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?= $ais ?></title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <link rel="stylesheet" href="css/new_tmp.css">
  <script type="text/javascript" src="<?= JS ?>jquery-1.10.2.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery.inputmask.js"></script>
  <script type="text/javascript" src="js/procedures.js"></script>
  <script type="text/javascript" src="js/quick_search.js"></script>
  <script type="text/javascript" src="<?= JS ?>functions.js"></script>
</head>
  <!--[if IE]>
  <link rel="stylesheet" href="<?= CSS ?>ie_fix.css">
  <![endif]-->
<body>
<?php
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<form class="current_object_form" id="<?php if (isset($face_id)) echo $face_id; ?>">
  <center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_face"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?= $face_id ?>"/>
  </div>
  <table border="0" rules="none" align="center" width="100%" class="object_view" object="<?= $object ?>">
    <tr>
      <td align="right" width="100px">Фамилия:<span class="req">*</span></td>
      <td width="200px"><input type="text" name="surname" <?= ($result['surname'] != '') ? 'value ="'.mb_convert_case($result['surname'], MB_CASE_TITLE, "UTF-8").'"' : ''; ?> req="true"/></td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td align="right">Имя:<span class="req">*</span></td>
      <td><input type="text" name="name" <?= ($result['name'] != '') ? 'value ="'.mb_convert_case($result['name'], MB_CASE_TITLE, "UTF-8").'"' : ''; ?> req="true"/></td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td align="right">Отчество:</td>
      <td><input type="text" name="fath_name" <?= ($result['fath_name'] != '') ? 'value ="'.mb_convert_case($result['fath_name'], MB_CASE_TITLE, "UTF-8").'"' : ''; ?>/></td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td align="right">Дата рожд.:</td>
      <td><input type="text" name="borth" class="datepicker" <?= ($result['borth'] != '') ? 'value ="'.mb_convert_case($result['borth'], MB_CASE_TITLE, "UTF-8").'"' : ''; ?>/></td>
      <td align="right">Статус:</td>
      <td>
        <label><input type="radio" name="real" value="1" checked/>Реальное</label>
        <label><input type="radio" name="real" value="0"/>Вымышленное</label>
      </td>
    </tr>
    <tr>
      <td colspan="4" align="center">
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
        <?= sel_objects($face_id, $object) ?>
      </div>
    </td>
  </tr>
  <tr class="result_data_row">
    <td colspan="2" width="40%" style="padding: 10px 0;"></td>
  </tr>
</table>
<?php
require_once('footer.php');
?>
</body>
</html>
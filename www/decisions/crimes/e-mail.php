<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));

$object = 10;
if (!isset($_GET["mail_id"]) || !is_numeric($_GET["mail_id"])) {
  header('Location: index.php');
}
$mail_id = floor(abs($_GET["mail_id"]));
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    m.`id`, m.`name`, 
    DATE_FORMAT(`reg_date`, "%d.%m.%Y") as reg_date,
    r.`id` as request_id,
    DATE_FORMAT(r.`request_date`, "%d.%m.%Y") as request_date,
    r.`request_number`, r.`request_file`,
    DATE_FORMAT(r.`response_date`, "%d.%m.%Y") as response_date,
    r.`response_number_out`, r.`response_number_in`
  FROM
    `o_mail` as m
  LEFT JOIN
    `l_requests` as r ON
      r.`obj_id` = '.$mail_id.' AND
      r.`obj_type` = '.$object.'
  WHERE
    m.`id` = '.$mail_id.'
  LIMIT 1
') or die(mysql_error());
$result = mysql_fetch_assoc($query);
if ($result['id'] < 1) header('Location: index.php'); // если ящика не существует, выходим на index.php
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?= $ais ?></title>
  <link rel="shortcut icon" href="/images/favicon.ico">
  <link rel="icon" href="/images/favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="/css/main.css">
  <link rel="stylesheet" href="/css/new.css">
  <link rel="stylesheet" href="/css/redmond/jquery-ui-1.10.4.custom.css">
  <link rel="stylesheet" href="css/new_tmp.css">
  <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
  <script type="text/javascript" src="/js/jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="/js/jquery.inputmask.js"></script>
  <script type="text/javascript" src="js/procedures.js"></script>
  <script type="text/javascript" src="/js/functions.js"></script>
</head>
  <!--[if IE]>
  <link rel="stylesheet" href="/css/ie_fix.css">
  <![endif]-->
<body>
<?php
require_once('head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php"><?= $ais ?></a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<form class="current_object_form" id="<?= $mail_id ?>">
  <center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_e-mail"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?= $mail_id ?>"/>
  </div>
  <table border="0" rules="none" width="100%">
    <tr>
      <td width="50%" align="right">
        <table border="0" rules="none" align="center" class="object_view" object="<?= $object ?>">
          <tr>
            <td align="right">Имя ящика:<span class="req">*</span></td>
            <td><input type="text" name="name" class="email" <?php if ($result["name"] != '') echo 'value="'.strtolower($result["name"]).'"'; ?> req="true"/></td>
          </tr>
          <tr>
            <td align="right">Дата рег.:</td>
            <td><input type="text" name="reg_date" class="datepicker" <?php if ($result["reg_date"] != '') echo 'value="'.$result["reg_date"].'"'; ?>/></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="5" align="center">
        <?= save_button('Сохранить') ?>
      </td>
    </tr>
  </table>
</form>
<table rules="none" border="0" width="100%" class="objects_table">
  <tr class="table_head">
    <td align="center" colspan="2">
      <b>Разделы:</b>
      <hr color="#C6C6C6" size="0px"/>
    </td>
  </tr>
  <tr class="table_head objects_list">
    <td align="center" colspan="2" height="40px" style="padding: 5px 0;">
      <div class="objects_list_block">
        <?= sel_objects($mail_id, $object) ?>
      </div>
    </td>
  </tr>
  <tr class="result_data_row">
    <td colspan="2" width="40%" style="padding: 10px 0;"></td>
  </tr>
</table>
<iframe name="frame" width="100%" style="display: none"></iframe>
<?php
require_once('footer.php');
?>
</body>
</html>
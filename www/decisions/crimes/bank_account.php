<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));

$object = 7;
if (!isset($_GET["acc_id"]) || !is_numeric($_GET["acc_id"])) {
  header('Location: index.php');
}
$acc_id = floor(abs($_GET["acc_id"]));
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    b.`id`, b.`bank`, b.`number`, 
    DATE_FORMAT(`open_date`, "%d.%m.%Y") as open_date,
    r.`id` as request_id,
    DATE_FORMAT(r.`request_date`, "%d.%m.%Y") as request_date,
    r.`request_number`, r.`request_file`,
    DATE_FORMAT(r.`response_date`, "%d.%m.%Y") as response_date,
    r.`response_number_out`, r.`response_number_in`
  FROM
    `o_bank_account` as b
  LEFT JOIN
    `l_requests` as r ON
      r.`obj_id` = '.$acc_id.' AND
      r.`obj_type` = '.$object.'
  WHERE
    b.`id` = '.$acc_id.'
  LIMIT 1
') or die(mysql_error());
$result = mysql_fetch_assoc($query);
if ($result['id'] < 1) header('Location: index.php'); // если счета не существует, выходим на index.php
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
  <script type="text/javascript" src="/js/procedures.js"></script>
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
<form class="current_object_form" id="<?= $acc_id ?>">
  <center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_bank_account"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?= $acc_id ?>"/>
  </div>
  <table border="0" rules="none" width="100%">
    <tr>
      <td width="50%" align="right">
        <table border="0" rules="none" align="center" width="100%" class="object_view" object="<?= $object ?>">
          <tr>
            <td align="right" wid1th="20px">Банк:</td>
            <td><input type="text" name="bank" <?php if ($result["bank"] != '') echo "value='".stripslashes($result["bank"])."'"; ?>/></td>
          </tr>
          <tr>
            <td align="right">№:</td>
            <td><input type="text" name="number" class="account_num" <?php if ($result["number"] != '') echo 'value="'.$result["number"].'"'; ?>/></td>
          </tr>
          <tr>
            <td align="right">Дата откр:</td>
            <td><input type="text" name="open_date" class="datepicker" <?php if ($result["open_date"] != '') echo 'value="'.$result["open_date"].'"'; ?>/></td>
          </tr>
        </table>
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
      <b>Разделы:</b>
      <hr color="#C6C6C6" size="0px"/>
    </td>
  </tr>
  <tr class="table_head objects_list">
    <td align="center" colspan="2" height="40px" style="padding: 5px 0;">
      <div class="objects_list_block">
        <?= sel_objects($acc_id, $object) ?>
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
<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$ais = is_ais(array(1, 2));

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
  header('Location: index.php');
}
$id = floor(abs($_GET["id"]));
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    CONCAT(
      sd.`document`, " № ", e.`decision_number`,
      " от ", DATE_FORMAT(e.`decision_date`, "%d.%m.%Y")
    ) as `decision`,
    e.`id` as `event`, req.`id`, t.`id` as `type`,
    DATE_FORMAT(req.`request_date`, "%d.%m.%Y") as `request_date`,
    req.`request_number`, req.`organisation`,
    DATE_FORMAT(req.`response_date`, "%d.%m.%Y") as `response_date`,
    req.`response_number_out`, req.`response_number_in`
  FROM
    `l_requests` as req
  LEFT JOIN
    `spr_request_types` as t ON
      t.`id` = req.`type`
  JOIN
    `o_event` as e ON
      e.`id` = req.`event`
    JOIN
      `spr_decisions` as sd ON
        sd.`id` = e.`decision`
  WHERE
    req.`id` = '.$id.'
  LIMIT 1
') or die(mysql_error());
$result = mysql_fetch_assoc($query);
if ($result['id'] < 1) die(header('Location: index.php'));
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
<form class="current_object_form" id="<?= $id ?>">
  <center><span style="font-size: 1.2em;"><strong>Запрос по <a href="event.php?event_id=<?= $result['event'] ?>"><?= $result['decision'] ?></a></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_request"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?= $id ?>"/>
  </div>
  <table border="0" rules="none" width="100%">
    <tr>
      <td width="50%" align="right">
        <table border="0" rules="none" align="center" class="object_view" object="request">
          <tr>
            <td colspan="4" align="center" width="350px"><b>Запрос</b></td>
            <td width="15px" rowspan="4" style="border-right: 1px solid #C6C6C6;"></td>
            <td width="15px" rowspan="4" style="border-left: 1px solid #C6C6C6;"></td>
            <td colspan="4" align="center" width="350px"><b>Ответ</b></td>
          </tr>
          <tr>
            <td align="right" width="75px">Тип:<span class="req">*</span></td>
            <td colspan="3" width="275px"><?= (empty($result['type']) ? request_types_select() : request_types_select($result['type'])) ?></td>
            <td align="right" width="75px">Дата:</td>
            <td>
              <input type="text" name="response_date" class="datepicker" placeholder="Дата" autocomplete="off"<?php if (!empty($result['response_date'])) echo ' value="'.$result['response_date'].'"' ?>/>
            </td>
            <td align="right">Исх.№:</td>
            <td>
              <input type="text" name="response_number_out" style="width: 60px;" autocomplete="off"<?php if (!empty($result['response_number_out'])) echo ' value="'.$result['response_number_out'].'"' ?>/>
            </td>
          </tr>
          <tr>
            <td align="right">Куда:<span class="req">*</span></td>
            <td colspan="3"><input type="text" name="organisation" autocomplete="off"<?php if (!empty($result['organisation'])) echo ' value="'.$result['organisation'].'"' ?>/></td>
            <td align="right">Вх.№:</td>
            <td colspan="3">
              <input type="text" name="response_number_in" style="width: 60px;" autocomplete="off"<?php if (!empty($result['response_number_in'])) echo ' value="'.$result['response_number_in'].'"' ?>/>
            </td>
          </tr>
          <tr>
            <td align="right">Дата:<span class="req">*</span></td>
            <td>
              <input type="text" name="request_date" class="datepicker" autocomplete="off"<?php if (!empty($result['request_date'])) echo ' value="'.$result['request_date'].'"' ?>/>
            </td>
            <td align="right">Исх.№:<span class="req">*</span></td>
            <td>
              <input type="text" name="request_number" style="width: 60px;" autocomplete="off"<?php if (!empty($result['request_number'])) echo ' value="'.$result['request_number'].'"' ?>/>
            </td>
            <td colspan="8"></td>
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
        <?= sel_objects($id, 'request') ?>
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
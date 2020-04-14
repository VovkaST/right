<?php
$need_auth = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$object = 6;
if (!isset($_GET["tel_id"]) || !is_numeric($_GET["tel_id"])) {
  header('Location: index.php');
}
$tel_id = floor(abs($_GET["tel_id"]));

require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    t.`id`, t.`type`, t.`number`, t.`operator`, opr.`region`,
    opr.`id` as `operator_range`, t.`note`,
    r.`id` as request_id,
    DATE_FORMAT(r.request_date, "%d.%m.%Y") as request_date,
    r.`request_number`, r.`request_file`,
    DATE_FORMAT(r.response_date, "%d.%m.%Y") as response_date,
    r.`response_number_out`, r.`response_number_in`
  FROM
    o_telephone as t
  LEFT JOIN
    `l_operators_ranges` as opr ON
      opr.`id` = t.`operator_range`
  LEFT JOIN
    `l_operators` as o ON
      o.`id` = t.`operator`
  LEFT JOIN
    l_requests as r ON
      r.obj_id = "'.$tel_id.'" AND
      r.obj_type = "'.$object.'"
  WHERE
    t.id = "'.$tel_id.'"
  LIMIT 1
') or die(mysql_error());

$result = mysql_fetch_assoc($query);
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>...</title>
  <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
  <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?= CSS ?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <link rel="stylesheet" href="css/new_tmp.css">
  <script type="text/javascript" src="<?= JS ?>jquery-1.10.2.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script type="text/javascript" src="<?= JS ?>jquery.inputmask.js"></script>
  <script type="text/javascript" src="js/procedures.js"></script>
  <script type="text/javascript" src="js/quick_search.js"></script>
  <script type="text/javascript" src="<?= JS ?>functions.js"></script>
  <script type="text/javascript">
    $(function() {
      $(".tel_num").inputmask("(999)999-99-99");
    });    
  </script>
  
  <script type="text/javascript">
    function operator_detect() {
      var mob_operators = $('.mob_operators');
      var tel_num = $('.tel_num');
      if (mob_operators.val() == '') {
        if (tel_num.val().length >= 10) {
          $.getJSON("procedures.php", { "phone_search": tel_num.val() }, function(resp){
            info_box_show(resp);
            if ('html' in resp) {
              mob_operators.val(resp['html']['id']);
              $('input[name="operator_range"]').val(resp['html']['operator_range']);
              $('td #operator_region').html(resp['html']['region']);
            }
          });
        }
      }
    }
    
    $(function(){
      if ($('.tel_type').val() == 1) {
        operator_detect();
      };
    });
    
    $(function(){
      $('.tel_type').change(function(){
        if ($(this).val() == 1) {
          operator_detect();
        }
      });
    });
    
  </script>
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
<form class="current_object_form" id="<?= $tel_id ?>">
  <center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_telephone"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?= $tel_id ?>"/>
  </div>
  <input type="hidden" name="operator_range" value="<?= $result["operator_range"] ?>"/>
  <table border="0" rules="none" width="100%">
    <tr>
      <td width="50%" align="right">
        <table border="0" rules="none" class="object_view" object="<?= $object ?>">
          <tr>
            <td align="right">Тип:</td>
            <td width="100px">
              <?= ($result["type"] == '') ? tel_types() : tel_types($result["type"]) ?>
            </td>
            <td colspan="2"></td>
          </tr>    
          <tr>
            <td align="right">Номер:</td>
            <td>
              <input type="text" name="number" class="tel_num" autocomplete="off" <?php if ($result["number"] != '') echo 'value="'.$result["number"].'"' ?>/>
            </td>
            <td align="right" width="100px">Оператор:</td>
            <td>
              <?= ($result["operator"] == '') ? mob_operators() : mob_operators($result["operator"]) ?>
            </td>
          </tr>
          <tr align="right">
            <td align="right">IMSI:</td>
            <td>
              <input type="text" name="IMSI" class="IMSI" autocomplete="off"/>
            </td>
            <td colspan="2" height="20px" id="operator_region">
              <?= $result["region"] ?>
            </td>
          </tr>
          <tr>
            <td colspan="4" class="imsi_cell">
              <?= related_imsi($tel_id) ?>
            </td>
          </tr>
          <tr>
            <td colspan="4"><em><?php if ($result["note"] != '') echo 'Примечание: '.$result["note"] ?></em></td>
          </tr>
        </table>
      </td>
      <td width="15px" style="border-right: 1px solid #C6C6C6;"></td>
      <td width="15px" style="border-left: 1px solid #C6C6C6;"></td>
      <td>
        <table border="0" width="300px" rules="none">
          <tr>
            <th colspan="4">Запрос</th>
          </tr>
          <tr height="23px">
            <td align="right" width="40px">Дата:</td>
            <td width="90px" class="request_date_cell">
              <?php if ($result["request_date"] != '') : echo $result["request_date"]; else : ?>
              <input type="text" name="request_date" class="datepicker request_date" placeholder="Дата" autocomplete="off"/><?php endif; ?>
            </td>
            <td align="right">исх.№:</td>
            <td width="60px" class="request_number_cell">
              <?php if ($result["request_number"] != '') : echo $result["request_number"]; else : ?>
              <input type="text" name="request_number" class="request_number" style="width: 60px;" autocomplete="off"/><?php endif; ?>
            </td>
          </tr>
          <tr height="23px">
            <td align="right">Файл:</td>
            <td colspan="3" class="request_image_cell">
            <?php if ($result["request_file"] != '') : ?>
              <a href="<?= CRIMES ?>download_file.php?id=<?= $result["request_id"] ?>">Скачать образ запроса</a>
            <?php else : ?>
              <input type="file" name="request_file" class="request_file" style="width: 100%;"/>
            <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th colspan="4">Ответ</th>
          </tr>
          <tr height="23px">
            <td align="right">Дата:</td>
            <td class="response_date_cell">
              <?php if ($result["response_date"] != '') : echo $result["response_date"]; else : ?>
              <input type="text" name="response_date" class="datepicker response_date" placeholder="Дата" autocomplete="off"/><?php endif; ?>
            </td>
            <td align="right">исх.№:</td>
            <td class="response_number_out_cell">
              <?php if ($result["response_number_out"] != '') : echo $result["response_number_out"]; else : ?>
              <input type="text" name="response_number_out" class="response_number_out" style="width: 60px;" autocomplete="off"/><?php endif; ?>
            </td>
          </tr>
          <tr height="23px">
            <td align="right">вх.№:</td>
            <td colspan="3" class="response_number_in_cell">
              <?php if ($result["response_number_in"] != '') : echo $result["response_number_in"]; else : ?>
              <input type="text" name="response_number_in" class="response_number_in" style="width: 60px;" autocomplete="off"/><?php endif; ?>
            </td>
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
      <b>Объекты:</b>
      <hr color="#C6C6C6" size="0px"/>
    </td>
  </tr>
  <tr class="table_head objects_list">
    <td align="center" colspan="2" height="40px" style="padding: 5px 0;">
      <div class="objects_list_block">
        <?= sel_objects($tel_id, $object) ?>
      </div>
    </td>
  </tr>
  <tr class="result_data_row">
    <td colspan="2" width="40%" style="padding: 10px 0;"></td>
  </tr>
</table>
<iframe name="frame" width="100%" style="display: none"></iframe>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
mysql_close($db);
?>
</body>
</html>
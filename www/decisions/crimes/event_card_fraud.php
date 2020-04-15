<?php
$event_id = null;
if (isset($_GET["event_id"]) && is_numeric($_GET["event_id"])) {
  if (isset($_SESSION['crime']['event_id'])) unset($_SESSION['crime']['event_id']);
  $event_id = floor(abs($_GET["event_id"]));
  require_once(KERNEL.'connection.php');
  $query = mysql_query('
    SELECT
      e.`id`, e.`ovd_id`, e.`employeer`, e.`emp_telephone`, e.`emp_range`, e.`service_id`, e.`kusp_num`,
      DATE_FORMAT(e.`kusp_date`, "%d.%m.%Y") as kusp_date,
      e.decision_number,
      DATE_FORMAT(e.`decision_date`, "%d.%m.%Y") as decision_date,
      e.`article_id`, e.`marking_id`, e.`decision`,
      e.`story`, e.`voice`,
      DATE_FORMAT(e.`disclose_date`, "%d.%m.%Y") as disclose_date,
      DATE_FORMAT(max(s.`date`), "%d.%m.%Y") as susp_date,
      s.`resume_date`,
      GROUP_CONCAT(
          "с ", DATE_FORMAT(sh.`date`, "%d.%m.%Y"), " по ", DATE_FORMAT(sh.`resume_date`, "%d.%m.%Y")
          SEPARATOR
            "|"
        ) as susp_history
    FROM
      `o_event` as e
    LEFT JOIN
      l_suspension as s ON
        s.`event_id` = e.id AND
        s.resume_date IS NULL
    LEFT JOIN
      l_suspension as sh ON
        sh.`event_id` = e.id AND
        sh.resume_date IS NOT NULL
    WHERE
      e.`id` = '.$event_id.'
    ORDER BY
      sh.`date`
  ') or die('Ошибка: '.mysql_error());
  $data = mysql_fetch_assoc($query);
  if ($data['id'] < 1) header('Location: index.php'); // если события не существует, выходим на index.php
} else {
  if (isset($_SESSION['crime']['event_id'])) {
    header('Location: '.$_SERVER['PHP_SELF'].'?event_id='.$_SESSION['crime']['event_id']);
    unset($_SESSION['crime']['event_id']);
    if (empty($_SESSION['crime'])) {
      unset($_SESSION['crime']);
    }
  }
  if (!empty($_GET)) $data = $_GET;
  if (isset($_GET["sum_debt"]) && is_numeric($_GET["sum_debt"])) {
    $sum_debt = floor(abs($_GET["sum_debt"]));
    require_once(KERNEL.'connection.php');
    $query = mysql_query('
      SELECT
        d.`kusp_num`, d.`article_id`, d.`crim_case`, 
        ovd.`id_ovd` as `ovd_id`, 
        d.`story`
      FROM
        `l_dist_crimes_summary` as d
      LEFT JOIN
        `spr_ovd` as ovd ON
          ovd.`cronos_code` = d.`ovd_cronos_id`
      WHERE
        d.`id` = '.$sum_debt.'
      LIMIT 1
    ') or die('Ошибка: '.mysql_error());
    $data = mysql_fetch_assoc($query);
  }
}
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
  <script type="text/javascript" src="/js/quick_search.js"></script>
  <script type="text/javascript" src="/js/functions.js"></script>
  <script type="text/javascript">
    $(function() {
      $(".tel_num").inputmask("(999)999-99-99");
    });
  </script>
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
<form class="current_object_form" id="<?php if (isset($event_id)) echo $event_id; ?>">
  <center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_event"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?php if (isset($event_id)) echo $event_id; ?>"/>
  </div>
  <table border="0" rules="none" align="center" width="100%" class="object_view" object="<?= $object ?>">
    <tr>
      <td colspan="2" align="center">
        ОВД:<span class="req">*</span>
        <?= isset($data["ovd_id"]) ? sel_ovd('ovd_id', $data["ovd_id"]) : sel_ovd('ovd_id'); ?>
        КУСП:<span class="req">*</span>
        <input type="text" placeholder="№" name="kusp_num" style="width: 60px;" autocomplete="off" <?= isset($data["kusp_num"]) ? 'value="'.$data["kusp_num"].'"' : ""; ?> req="true"/>
        от<span class="req">*</span>
        <input type="text" name="kusp_date" class="datepicker" placeholder="Дата" autocomplete="off" <?= isset($data["kusp_date"]) ? 'value="'.$data["kusp_date"].'"' : ""; ?> req="true"/>
      </td>
      <td width="430px">
        <button type="button" class="markings">Окраска события</button>
      </td>
    </tr>
    <tr>
      <td>
        Сотрудник:<span class="req">*</span>
        <input type="text" name="employeer" placeholder="ФИО" autocomplete="off" <?= isset($data["employeer"]) ? 'value="'.$data["employeer"].'"' : "";?> req="true"/>
      </td>
      <td>
        Служба:<span class="req">*</span>
        <select name="service_id" req="true">
          <?= isset($data["service_id"]) ? sel_slujba($data["service_id"]) : sel_slujba(); ?>
        </select>
      </td>
      <td rowspan="4" style="vertical-align: top;">
        <span style="display: block;">Фабула:<span class="req">*</span></span>
        <textarea name="story" class="event story" req="true"><?= isset($data["story"]) ? $data["story"] : ""; ?></textarea>
      </td>
    </tr>
    <tr>
      <td>
        Должность:<span class="req">*</span>
        <input type="text" name="emp_range" id="" placeholder="должность" autocomplete="off" <?= isset($data["emp_range"]) ? 'value="'.$data["emp_range"].'"' : ""; ?> req="true"/>
      </td>
      <td>
        Конт.тел.:<span class="req">*</span>
        <input type="text" name="emp_telephone" class="tel_num" placeholder="xxx-xxx-xx-xx" autocomplete="off" <?= isset($data["emp_telephone"]) ? 'value="'.$data["emp_telephone"].'"' : ""; ?> req="true"/>
      </td>
    </tr>
    <tr>
      <td>
        Решение:<span class="req">*</span>
        <label>Отказано в ВУД <input type="radio" name="decision" value="1" <?= (isset($data["decision"]) && $data["decision"] == 1) ? 'checked' : '' ?> onClick="document.getElementById('refuse_table').style.display = 'table'; document.getElementById('crime_table').style.display = 'none';"/></label>
        <label>ВУД <input type="radio" name="decision" value="2" <?= ((isset($data["decision"]) && $data["decision"] == 2) || (!isset($data["decision"]))) ? 'checked' : '' ?> onClick="document.getElementById('crime_table').style.display = 'table'; document.getElementById('refuse_table').style.display = 'none';"/></label>
      </td>
      <td>
        Ст.<span class="req">*</span>
        <?= isset($data["article_id"]) ? sel_uk($data["article_id"]) : sel_uk() ?>
        УК РФ.
      </td>
    </tr>
    <tr>
      <td colspan="2" style="height: 75px;" valign="top">
        <table border="0" rules="none" width="100%" id="refuse_table" <?= ((isset($data["decision"]) && $data["decision"] == 2) || !isset($data["decision"])) ? 'style="display: none;"' : '' ?>>
          <tr>
            <td colspan="2">
              Рег. № 
              <input type="text" name="decision_number[]" placeholder="Отказной" class="refuse" autocomplete="off" <?php if (isset($data["decision_number"]) && isset($data["decision"])) {if (($data["decision_number"] != '') && ($data["decision"] == 1)) echo 'value="'.$data["decision_number"].'"';} ?> style="width: 60px;"/>
              от
              <input type="text" name="decision_date[]" class="refuse_date datepicker" placeholder="Дата" autocomplete="off" <?php if (isset($data["decision_date"]) && isset($data["decision"])) {if (($data["decision_date"] != '') && ($data["decision"] == 1)) echo 'value="'.$data["decision_date"].'"';} ?>/>
            </td>
          </tr>
        </table>
        <table border="0" rules="none" width="100%" id="crime_table" <?= (isset($data["decision"]) && $data["decision"] == 1) ? 'style="display: none;"' : '' ?>>
          <tr>
            <td colspan="2">
              У/д №
              <input type="text" name="decision_number[]" placeholder="№ у/д" class="crim_case" autocomplete="off" <?php if (isset($data["decision_number"]) && isset($data["decision"])) {if (($data["decision_number"] != '') && ($data["decision"] == 2)) echo 'value="'.$data["decision_number"].'"';} ?> style="width: 60px;"/>
              возбуждено
              <input type="text" name="decision_date[]" class="crim_case_date datepicker" placeholder="Дата" autocomplete="off" <?php if (isset($data["decision_date"]) && isset($data["decision"])) {if (($data["decision_date"] != '') && ($data["decision"] == 2)) echo 'value="'.$data["decision_date"].'"';} ?>/>
              Раскрыто:
              <input type="text" name="disclose_date" class="datepicker" placeholder="Дата" autocomplete="off" <?= isset($data["disclose_date"]) ? 'value="'.$data["disclose_date"].'"' : ""; ?>/>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              Приостановлено:
              <input type="text" name="susp_date" class="datepicker susp_date" placeholder="Дата"  autocomplete="off"<?= isset($data["susp_date"]) ? 'value="'.$data["susp_date"].'"' : ""; ?>/>
              <span class="susp_resume_date" <?= !isset($data["susp_date"]) ? ' style="display: none;"' : ""; ?>>
                Возобновлено:
                <input type="text" name="susp_resume_date" class="datepicker" placeholder="Дата" autocomplete="off"/>
              </span>
            </td>
          </tr>
          <tr>
            <td>
              <input type="hidden" name="voice" value="0"/>
              <input type="hidden" name="judgment" value="0"/>
              <ul>
                <li><label>Изъяты образцы голоса <input type="checkbox" name="voice" value="1" <?php if (isset($data['voice'])) {if ($data['voice'] == 1) echo "checked";} ?>/></label></li>
                <li><label>Ожидается судебное решение <input type="checkbox" name="judgment" value="1" <?php if (isset($data['judgment'])) {if ($data['judgment'] == 1) echo "checked";} ?>/></label></li>
              </ul>
            </td>
            <td>
              <?php if (!empty($data["susp_history"])) : 
                $history = explode("|", $data["susp_history"]); ?>
                Ранее приостанавливалось:
                <ul style="margin: 0 0 0 20px; list-style-type: circle;">
                <?php foreach ($history as $v): ?>
                  <li><?= $v ?></li>
                <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr style="height: 25px;">
      <td colspan="4" align="center">
        <? //= save_button('Сохранить') ?>
      </td>
    </tr>
  </table>
  <?= markings(2, $event_id) ?>
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
        <?= isset($event_id) ? sel_objects($event_id, $object) : 'Введите информацию о преступлении и сохраните изменения...'; ?>
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
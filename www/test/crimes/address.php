<?php
$need_auth = 0;
require_once($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$object = 3;
if (!isset($_GET["addr_id"]) || !is_numeric($_GET["addr_id"])) {
  header('Location: index.php');
}
$addr_id = floor(abs($_GET["addr_id"]));
require_once(KERNEL.'connection.php');
$query = mysql_query('
  SELECT
    a.id,
    reg.`code` as `region`,
    CONCAT(
      RTRIM(reg.`name`), ", ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = reg.`socr` AND `level` = 1)
    ) as `region_text`,
    dist.`code` as `district`,
    CONCAT(
      RTRIM(dist.`name`), ", ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = dist.`socr` AND `level` = 2)
    ) as `district_text`,
    city.`code` as `city`,
    CONCAT(
      RTRIM(city.`name`), ", ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = city.`socr` AND `level` = 3)
    ) as `city_text`,
    loc.`code` as `locality`,
    CONCAT(
      RTRIM(loc.`name`), ", ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = loc.`socr` AND `level` = 4)
    ) as `locality_text`,
    str.`code` as `street`,
    CONCAT(
      RTRIM(str.`name`), ", ", (SELECT RTRIM(`scname`) FROM `spr_socr` WHERE `id` = str.`socr` AND `level` = 5)
    ) as `street_text`,
    a.`house`, a.`house_lit`, a.`flat`, a.`flat_lit`
  FROM
    o_address as a
  LEFT JOIN
    `spr_region` as reg ON
      reg.`id` = a.`region`
  LEFT JOIN
    `spr_district` as dist ON
      dist.`id` = a.`district`
  LEFT JOIN
    `spr_city` as city ON
      city.`id` = a.`city`
  LEFT JOIN
    `spr_locality` as loc ON
      loc.`id` = a.`locality`
  LEFT JOIN
    `spr_street` as str ON
      str.`id` = a.`street`
  WHERE
    a.`id` = '.$addr_id.'
  LIMIT 1
') or die(mysql_error());
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
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="index.php">АИС "Мошенник"</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<form class="current_object_form" id="<?= $addr_id ?>">
  <center><span style="font-size: 1.2em;"><strong><?= current_object($object) ?></strong></span></center>
  <hr color="#C6C6C6" size="0px"/>
  <input type="hidden" name="data_form" value="form_address"/>
  <div class="object_id">
    <input type="hidden" name="id" value="<?= $addr_id; ?>"/>
  </div>
  <table border="0" rules="none" align="center" class="object_view" object="<?= $object ?>">
    <tr>
      <td width="80px" align="right">Регион:</td>
      <td colspan="3" width="100px">
        <input type="text" name="region_text" id="region" class="ajax_search" autocomplete="off" <?php if (!empty($result['region_text'])) echo 'value="'.$result['region_text'].'"' ?>/>
        <div class="ajax_search_result"></div>
        <input type="hidden" name="region" <?php if (!empty($result['region'])) echo 'value="'.$result['region'].'"' ?>/>
      </td>
      <td width="30px" class="wait"></td>
      <td width="80px" align="right">Район:</td>
      <td colspan="3">
        <input type="text" name="district_text" id="district" class="ajax_search" autocomplete="off" <?php if (!empty($result['district_text'])) echo 'value="'.$result['district_text'].'"' ?>/>
        <div class="ajax_search_result"></div>
        <input type="hidden" name="district" <?php if (!empty($result['district'])) echo 'value="'.$result['district'].'"' ?>/>
      </td>
      <td width="30px" class="wait"></td>
    </tr>
    <tr>
      <td align="right">Город:</td>
      <td colspan="3">
        <input type="text" name="city_text" id="city" class="ajax_search" autocomplete="off" <?php if (!empty($result['city_text'])) echo 'value="'.$result['city_text'].'"' ?>/>
        <div class="ajax_search_result"></div>
        <input type="hidden" name="city" <?php if (!empty($result['city'])) echo 'value="'.$result['city'].'"' ?>/>
      </td>
      <td width="30px"></td>
      <td align="right">Нас.пункт:</td>
      <td colspan="3">
        <input type="text" name="locality_text" id="locality" class="ajax_search" autocomplete="off" <?php if (!empty($result['locality_text'])) echo 'value="'.$result['locality_text'].'"' ?>/>
        <div class="ajax_search_result"></div>
        <input type="hidden" name="locality" <?php if (!empty($result['locality'])) echo 'value="'.$result['locality'].'"' ?>/>
      </td>
      <td width="30px"></td>
    </tr>
    <tr>
      <td align="right">Улица:</td>
      <td colspan="3">
        <input type="text" name="street_text" id="street" class="ajax_search" autocomplete="off" <?php if (!empty($result['street_text'])) echo 'value="'.$result['street_text'].'"' ?>/>
        <div class="ajax_search_result"></div>
        <input type="hidden" name="street" <?php if (!empty($result['street'])) echo 'value="'.$result['street'].'"' ?>/>
      </td>
      <td width="30px"></td>
      <td colspan="5"></td>
    </tr>
    <tr>
      <td align="right">Дом:</td>
      <td><input type="text" name="house" style="width: 30px;" autocomplete="off" <?php if (!empty($result['house'])) echo 'value="'.$result['house'].'"' ?>/></td>
      <td align="right">Литера:</td>
      <td><input type="text" name="house_lit" style="width: 30px;" autocomplete="off" <?php if (!empty($result['house_lit'])) echo 'value="'.$result['house_lit'].'"' ?>/></td>
      <td></td>
      <td align="right">Квартира:</td>
      <td><input type="text" name="flat" style="width: 30px;" autocomplete="off" <?php if (!empty($result['flat'])) echo 'value="'.$result['flat'].'"' ?>/></td>
      <td align="right">Литера:</td>
      <td><input type="text" name="flat_lit" style="width: 30px;" autocomplete="off" <?php if (!empty($result['flat_lit'])) echo 'value="'.$result['flat_lit'].'"' ?>/></td>
      <td></td>
    </tr>
    <tr>
      <td colspan="10" align="center">
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
        <?= sel_objects($addr_id, $object) ?>
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
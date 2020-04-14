<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (!empty($_GET["id_ovd"]) && is_numeric($_GET["id_ovd"])) {
  $id_ovd = intval($_REQUEST["id_ovd"]);
  $par_array["id_ovd"] = $id_ovd;
} else {
  header('Location: formation_results.php');
}
$yearList = selectRefuseYears();
if (!empty($_GET["resultYear"]) && is_numeric($_GET["resultYear"])) {
  if (in_array($_GET["resultYear"], $yearList)) {
    $year = $_GET["resultYear"];
  } else {
    $year = date('Y');
  }
} else {
  $year = date('Y');
}
$par_array["resultYear"] = $year;
if (!empty($_GET["month"])) {
  $mthList = array(1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
  $month = $par_array["month"] = $_GET["month"];
  $addQuery = ' AND MONTH(data_resh) = "'.$month.'"';
  $resMonth = abs($month) > 12 ? '('.$mthList[12].')' : '('.$mthList[intval(abs($month))].')';
} else {
  $addQuery = '';
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset=utf-8>
 <title><?= getOvdName($id_ovd)[1] ?></title>
 <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
 <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= CSS ?>head.css">
 <link rel="stylesheet" href="<?= CSS ?>main.css">
 <link rel="stylesheet" href="<?= CSS ?>new.css">
 <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
 <script src="<?= JS ?>jquery-1.10.2.js"></script>
 <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
 <script src="<?= JS ?>procedures.js"></script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= ACCOUNTING ?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?= REFUSAL_VIEW_UPLOAD ?>">Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="formation_results.php?resultYear=<?= $year ?>">Долги, недостатки и результаты формирования</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;
</div>
<h3><?= getOvdName($id_ovd)[1]; ?> <?php if (isset($resMonth)) echo $resMonth ?>
</h3>
<?php
$query = '
  SELECT
    id, reg, id_ovd, data_resh, file_final, file_original, id_slujba, anonymous,
    (SELECT slujba FROM spr_slujba as spr WHERE spr.id_slujba = o.id_slujba) as slujba, 
    sotr_f, sotr_i, sotr_o, upk, status 
  FROM
    otkaz as o
  WHERE
    deleted = "0" AND
    is_file = 1 AND
    id_ovd = "'.$id_ovd.'" AND
    year(data_resh) = "'.$year.'"'.$addQuery.'
  ORDER BY
    data_resh DESC, reg DESC';
  $kol_rec = mysql_num_rows(mysql_query($query));
  if ($kol_rec):
    $listing = listing($query, $par_array, $_SERVER["PHP_SELF"].'?', 'connection.php');?>
    <table class="result_table" id="refusal_view_table" border="1" rules="all" cols="7">
      <tr class="table_head">
        <td width="20px">Рег.№</td>
        <td width="70px">Дата решения</td>
        <td width="70px">Служба</td>
        <td width="130px">Сотрудник</td>
        <td width="70px">статья<br>УК</td>
        <td width="30px">п.<br>УПК</td>
        <td colspan="2">КУСП</td>
      </tr>
    <?php
    $limit = limit($kol_rec);
    $query .= ' '.$limit['limit'];
    $query = (mysql_query($query));
    while ($result = mysql_fetch_array($query)): ?>
    <?php
      $status = "";
      if ($result["status"] == "2") {
        $status = "Доп";
      }
      //статьи
      $query_st = mysql_query('
        SELECT
          st
        FROM
          relatives_uk_otk as rel
        JOIN
          spr_uk as spr ON
            spr.id_uk = rel.id_st
        WHERE
          rel.id_otkaz = "'.$result["id"].'"
      ');
      $array = array();
      while ($result_st = mysql_fetch_array($query_st)) {
        $array[] = $result_st["st"];
      }
      $criminal = implode("<br/>", $array);
      //КУСП
      $query_kusp = mysql_query('
        SELECT
          ovd.ovd,
          k.kusp,
          k.data
        FROM
          kusp as k
        JOIN
          spr_ovd as ovd ON
            ovd.id_ovd = k.ovd
        WHERE
          otkaz_id = "'.$result["id"].'"
      ');
      $array = array();
      while ($result_kusp = mysql_fetch_array($query_kusp)) {
        $array[] = $result_kusp["ovd"].", КУСП №<b>".$result_kusp["kusp"]."</b> от <b>".date("d.m.Y", strtotime($result_kusp["data"]))."</b>";
      }
      $kusp = implode("<br/>", $array);
      //Лица
      if (isset($_SESSION['user'])) {
        $query_lico = mysql_query('
          SELECT
            sr.type,
            l.surname,
            l.name,
            l.fath_name,
            l.borth
          FROM
            lico as l
          JOIN
            relatives as rel ON 
              rel.id_lico = l.id AND
              rel.id_otkaz = "'.$result["id"].'"
          JOIN
            spr_relatives as sr
              ON sr.id = rel.type
        ');
        $array = array();
        while ($result_lico = mysql_fetch_array($query_lico)) {
          $surname = mb_convert_case($result_lico["surname"], MB_CASE_TITLE, "UTF-8");
          $name = mb_convert_case($result_lico["name"], MB_CASE_TITLE, "UTF-8");
          $fath_name = mb_convert_case($result_lico["fath_name"], MB_CASE_TITLE, "UTF-8");
          $borth = date("d.m.Y", strtotime($result_lico["borth"]))." г.р.";
          $array[] = $result_lico["type"]." - ".$surname." ".$name." ".$fath_name." ".$borth;
        }
        $lico = implode("<br/>", $array);
        if (count($array)) $lico .= '<br/>';
        
        $query_org = mysql_query('
          SELECT
            sr.type,
            o.org_name
          FROM
            organisations as o
          JOIN
            relatives as rel ON 
              rel.id_org = o.id AND
              rel.id_otkaz = "'.$result["id"].'"
          JOIN
            spr_relatives as sr
              ON sr.id = rel.type
        ');
        unset($array);
        $array = array();
        while ($result_org = mysql_fetch_array($query_org)) {
          $org = mb_convert_case($result_org["org_name"], MB_CASE_UPPER, "UTF-8");
          $array[] = 'Организация ('.$result_org["type"].') - '.$org;
        }
        $org = implode("<br/>", $array);
        if (count($array)) $org .= '<br/>';
        
        if ($result["anonymous"]) $lico .= 'Анонимный заявитель';
      }
    ?>
    <tr class="main_row">
      <td class="otkaz_reg_cell"<?php if (isset($lico)) echo ' rowspan="2"'; ?>><?= $result["reg"]; ?><br/><?= $status; ?></td>
      <td align="center"><b><?= date("d.m.Y", strtotime($result["data_resh"])); ?></b></td>
      <td align="center"><b><?= $result["slujba"]; ?></b></td>
      <td><b><?= mb_convert_case($result["sotr_f"], MB_CASE_TITLE, "UTF-8")." ".mb_strcut($result["sotr_i"], 1, 2, "UTF-8").". ".mb_strcut($result["sotr_o"], 1, 2, "UTF-8").". "; ?></b></td>
      <td align="center"><b><?= $criminal; ?></b></td>
      <td align="center"><b><?= $result["upk"]; ?></b></td>
      <td colspan="2"><?= $kusp; ?></td>
    </tr>
    <?php if (isset($lico) || isset($org)): ?>
      <tr>
        <td class="face_cell" colspan="6"><?php if (isset($lico)) echo $lico; if (isset($org)) echo $org; ?></td>
        <td class="download_cell">
          <a href="#" target="_blank" class="download_link" id="<?= $result["id"]; ?>">
            <div class="download_link_icon">
              <span class="link_notice">Скачать файл</span>
            </div>
          </a>
          <a href="#" class="delete_link" id="<?= $result["id"]; ?>">
            <div class="delete_link_icon">
              <span class="link_notice">Удалить</span>
            </div>
          </a>
        </td>
      </tr>
    <?php
    endif;
  endwhile;?>
</table>
<?= $listing; ?>
<?php
endif;
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
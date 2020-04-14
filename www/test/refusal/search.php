<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html; charset=utf-8");
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (isset($_SESSION['activity_id'])) {
  query_log($_SESSION['activity_id'], get_defined_vars()["_REQUEST"]);
}
require(KERNEL.'connection.php');
// -------- поиск по реквизитам -------- //
if (!empty($_REQUEST["id_ovd"]) ||
    !empty($_REQUEST["otkaz_id"]) ||
    !empty($_REQUEST["status"]) ||
    !empty($_REQUEST["employeer"]) ||
    !empty($_REQUEST["id_slujba"]) ||
    !empty($_REQUEST["upk"]) ||
    !empty($_REQUEST["start_otk_date"]) ||
    !empty($_REQUEST["end_otk_date"]) ||
    !empty($_REQUEST["KUSP_ovd"]) ||
    !empty($_REQUEST["KUSP_num"]) ||
    !empty($_REQUEST["KUSP_date"]) ||
    !empty($_REQUEST["uk"])) :
  $par_array = $link_array = array(); // объявляем массив SQL запроса, адресной строки
  
  if (!empty($_REQUEST["id_ovd"]) && is_numeric($_REQUEST["id_ovd"])) {
    $par_array['id_ovd'] = intval($_REQUEST["id_ovd"]); // формируем список параметров
  }
  
  if (!empty($_REQUEST["otkaz_id"]) && is_numeric($_REQUEST["otkaz_id"])) {
    $par_array['reg'] = intval($_REQUEST["otkaz_id"]);
  }
  
  if (!empty($_REQUEST["status"]) && is_numeric($_REQUEST["status"])) {
    $par_array['status'] = intval($_REQUEST["status"]);
  }
  
  if (!empty($_REQUEST["id_slujba"]) && is_numeric($_REQUEST["id_slujba"])) {
    $par_array['id_slujba'] = intval($_REQUEST["id_slujba"]);
  }
  
  if (!empty($_REQUEST["upk"]) && is_numeric($_REQUEST["upk"])) {
    $par_array['upk'] = intval($_REQUEST["upk"]);
  }
  
  foreach($par_array as $key => $value) {
    $link_array[] = $key.' = "'.$value.'"'; // записываем параметры в массив
  }
  
  $par_employeer = "";
  if (!empty($_REQUEST["employeer"])) {
    $employeer = htmlspecialchars($_REQUEST["employeer"]);
    $employeer = preg_replace("/[0-9]/", "", $employeer);
    if (!empty($employeer)) {
      $par_employeer = ' and (sotr_f LIKE "'.$employeer.'%")';
      $par_array['employeer'] = $employeer;
    }
  }
  
  $par_date = "";
  if (!empty($_REQUEST["start_otk_date"]) && preg_match("|^[0-3]\d\.[01]\d\.[1-2]\d{3}$|", $_REQUEST["start_otk_date"])) {
    $par_date = ' and data_resh = "'.date("Y-m-d", strtotime($_REQUEST["start_otk_date"])).'"';
    $par_array['start_otk_date'] = $_REQUEST["start_otk_date"];
    if (!empty($_REQUEST["end_otk_date"]) && preg_match("|^[0-3]\d\.[01]\d\.[1-2]\d{3}$|", $_REQUEST["end_otk_date"])) {
      $par_date = ' and (data_resh BETWEEN "'.date("Y-m-d", strtotime($_REQUEST["start_otk_date"])).'" and "'.date("Y-m-d", strtotime($_REQUEST["end_otk_date"])).'")';
      $par_array['end_otk_date'] = $_REQUEST["end_otk_date"];
    }
  }
  
//-------- КУСП -------//
  $KUSP_OVD = $KUSP_query = $par_kusp_date = $par_kusp = "";
  $par_kusp_array  = array();
  
  //-------- ОВД КУСП -------//
  if (!empty($_REQUEST["KUSP_ovd"]) && is_numeric($_REQUEST["KUSP_ovd"])) {
    $KUSP_OVD = 'ovd = "'.intval($_REQUEST["KUSP_ovd"]).'"';
    $par_kusp_array[] = $KUSP_OVD;
    $par_array['KUSP_ovd'] = intval($_REQUEST["KUSP_ovd"]);
  }
  //-------- ОВД КУСП -------//
  
  //-------- номер КУСП -------//
  $kusp_list = "";
  if (!empty($_REQUEST["KUSP_num"])) {
    $kusp_num = $_REQUEST["KUSP_num"];
    if (substr_count($kusp_num, ",")) { // если номера перечислены через запятую
      $kol_reg_KUSP = substr_count($kusp_num, ",") + 1; // получаем количество частей, разделенных запятыми
      $num = explode(",", $kusp_num); // разбиваем строку
      $i = 0;
      $KUSP_query = "(";
      while ($i < $kol_reg_KUSP) { // для каждой части
        $str = str_replace(" ", "", $num[$i]); // удаляем пробелы
        if ($str != "") { // если не пусто
          if ($kol_reg_KUSP - $i == 1) {
            $and = $list_del = "";
          } else {
            $and = " or ";
            $list_del = ",";
          }
          $KUSP_query .= 'kusp = "'.$str.'"'.$and;
          $kusp_list .= $str.$list_del;
        }
        $i++;
      }
      $KUSP_query .= ")";
    } else { // если указан один номер
      $str = str_replace(" ", "", $kusp_num); // удаляем пробелы
      $KUSP_query = 'kusp = "'.$kusp_num.'"';
      $kusp_list = $kusp_num;
    }
    $par_array['KUSP_num'] = $kusp_list;
    $par_kusp_array[] = $KUSP_query;
  }
  //-------- номер КУСП -------//
  
  //-------- дата КУСП -------//
  if (!empty($_REQUEST["KUSP_date"]) && preg_match("|^[0-3]\d\.[01]\d\.[1-2]\d{3}$|", $_REQUEST["KUSP_date"])) {
    $par_kusp_date = ' data = "'.date("Y-m-d", strtotime($_REQUEST["KUSP_date"])).'"';
    $par_kusp_array[] = $par_array['KUSP_ovd'] = $par_kusp_date;
  }
  //-------- дата КУСП -------//
  
  if (count($par_kusp_array)) {
   $par_kusp = " and (id IN (SELECT otkaz_id FROM kusp WHERE ".implode(" and ", $par_kusp_array)."))";
  }
//-------- КУСП -------//
  
  //статьи УК
  $uk_query = $uk_list = "";
  if (!empty($_REQUEST["uk"])) {
    $reg_uk = $_REQUEST["uk"];
    $st_query = "";
    if (substr_count($reg_uk, ",")) {
      $kol_reg_uk = substr_count($reg_uk, ",") + 1; // получаем количество частей, разделенных запятыми
      $num = explode(",", $reg_uk); // разбиваем строку
      $i = 0;
      while ($i < $kol_reg_uk) { // для каждой части
        $str = str_replace(" ", "", $num[$i]); // удаляем пробелы
        if ($str != "") { // если не пусто
          if ($kol_reg_uk - $i == 1) {
            $and = $list_del = "";
          } else {
            $and = " or ";
            $list_del = ",";
          }
          $st_query .= 'st LIKE "'.$str.'%"'.$and;
          $uk_list .= $str.$list_del;
        }
        $i++;
      }
    }
    //если одна статья
    else {
      $st_query .= 'st LIKE "'.$reg_uk.'%"';
      $uk_list = $reg_uk;
    }
    $uk_query = " and (id IN (SELECT id_otkaz FROM relatives_uk_otk as rel JOIN spr_uk as spr ON spr.id_uk = rel.id_st WHERE ".$st_query."))";
    $par_array['uk'] = $uk_list;
  }
  if (!count($link_array)) {
    $and = "";
  } else {
    $and = " and ";
  }
  $sql_where = $and.implode(" and ", $link_array).$par_employeer.$par_date.$par_kusp.$uk_query; // строка поиска для SQL
  $query = '
    SELECT
      id, reg, data_resh, file_final, file_original, id_slujba, anonymous,
      (SELECT slujba FROM spr_slujba as spr WHERE spr.id_slujba = o.id_slujba) as slujba, 
      sotr_f, sotr_i, sotr_o, upk, status 
    FROM
      otkaz as o
    WHERE
			deleted = "0" AND 
      o.is_file = 1 '.$sql_where.'
		ORDER BY
			data_resh DESC, reg DESC';
    $kol_rec = mysql_num_rows(mysql_query($query));
    if ($kol_rec):
      $listing = listing($query, $par_array, $search, 'connection.php');?>
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
  else:?>
    <center><b>Материалы отсутствуют</b></center>
  <?php endif;
// -------- поиск по реквизитам -------- //

// -------- поиск по лицу -------- //
elseif (!empty($_REQUEST["surname"]) ||
    !empty($_REQUEST["name"]) ||
    !empty($_REQUEST["fath_name"]) ||
    !empty($_REQUEST["borth"]) ||
    !empty($_REQUEST["type"])): // если поиск по лицу
  $par_array = $link_array = array(); // объявляем массив SQL запроса, адресной строки
  
  if (!empty($_REQUEST["surname"])) {
    $surname = htmlspecialchars($_REQUEST["surname"]);
    $surname = preg_replace("/[0-9]/", "", $surname);
    if (!empty($surname)) {
      $par_array["surname"] = $surname."%";
    }
  }
  if (!empty($_REQUEST["name"])) {
    $name = htmlspecialchars($_REQUEST["name"]);
    $name = preg_replace("/[0-9]/", "", $name);
    if (!empty($name)) {
      $par_array["name"] = $name."%";
    }
  }
  if (!empty($_REQUEST["fath_name"])) {
    $fath_name = htmlspecialchars($_REQUEST["fath_name"]);
    $fath_name = preg_replace("/[0-9]/", "", $fath_name);
    if (!empty($fath_name)) {
      $par_array["fath_name"] = $fath_name."%";
    }
  }
  if (!empty($_REQUEST["borth"]) && preg_match("|^[0-3]\d\.[01]\d\.[1-2]\d{3}$|", $_REQUEST["borth"])) {
    $borth = htmlspecialchars($_REQUEST["borth"]);
    $par_array["borth"] = date("Y-m-d", strtotime($borth));
  }
  if (!empty($_REQUEST["type"])) {
    $par_array["type"] = intval($_REQUEST["type"]);
  }
  foreach($par_array as $key => $value) {
    if ($key == "type") {
      $link_array[] = $key.' = "'.$value.'"'; // тип связи совпадает
    } else {
      $link_array[] = $key.' like "'.$value.'"'; // ФИО, ДР похоже
    }
  }
  //$sql_where = implode(" and ", $link_array);
  $sql_where = $relative = "";
  foreach($link_array as $value) { // тип связи выносим в отдельную переменную
    if (strpos($value, 'type') === false) {
      $sql_where .= 'AND '.$value;
    } else {
      $relative = " AND rel.".$value;
    }
  }
  $query = '
    SELECT
      id, surname, name, fath_name, DATE_FORMAT(borth, "%d.%m.%Y") as borth
    FROM
      lico
    WHERE
      id IN (
        SELECT
          rel.id_lico
        FROM
          relatives as rel
        JOIN
          otkaz as o ON
            o.id = rel.id_otkaz AND
            o.deleted = "0"'.$relative.'
      )
    '.$sql_where;
  $kol_rec = mysql_num_rows(mysql_query($query));
  if ($kol_rec): // если результат поиска по лицу не пустой
    $listing = listing($query, $par_array, $search, 'connection.php');
  ?>
    <table border="1" rules="all" cols="5" class="result_table" id="refusal_view_table" style="width: 740px;">
      <tr class="table_head">
        <td width="150px">Тип</td>
        <td width="70px">Дата<br/>решения</td>
        <td width="70px">статья<br>УК</td>
        <td width="30px">п.<br/>УПК</td>
        <td colspan="2">КУСП</td>
      </tr>
    <?php
    $limit = limit($kol_rec);
    $query .= ' '.$limit['limit'];
    $query = (mysql_query($query));
    while ($result = mysql_fetch_array($query)) : // общий запрос
      $surname = mb_convert_case($result["surname"], MB_CASE_TITLE, "UTF-8");
      $name = mb_convert_case($result["name"], MB_CASE_TITLE, "UTF-8");
      $fath_name = mb_convert_case($result["fath_name"], MB_CASE_TITLE, "UTF-8");
    ?>
      <tr class="face_string">
        <td colspan="6"><?=$surname." ".$name." ".$fath_name." ".$result["borth"]." г.р."?></td>
      </tr>
      <?php
      $query2 = mysql_query('
        SELECT
          sr.type as type,
          GROUP_CONCAT(
            DISTINCT
              so.ovd, ", ",
              kusp.kusp, " от ",
              DATE_FORMAT(kusp.data, "%d.%m.%Y")
            ORDER BY
              kusp.data, kusp.kusp
            SEPARATOR 
              "<br/>") as kusp,
          GROUP_CONCAT(
            DISTINCT
              su.st
            ORDER BY
              su.st
            SEPARATOR 
              "<br/>") as uk,
          DATE_FORMAT(o.data_resh, "%d.%m.%Y") as data_resh,
          o.upk,
          o.id as id_otkaz
        FROM
          relatives as rel
        JOIN
          spr_relatives as sr ON
            sr.id = rel.type
        LEFT JOIN
          otkaz as o ON
            o.id = rel.id_otkaz AND
            o.deleted = "0"
          LEFT JOIN
            kusp ON
              kusp.otkaz_id = o.id
          LEFT JOIN
            relatives_uk_otk as ruo ON
              ruo.id_otkaz = o.id
            LEFT JOIN
              spr_uk as su ON
                su.id_uk = ruo.id_st
        JOIN
          spr_ovd as so ON
            so.id_ovd = kusp.ovd
        WHERE
          rel.id_lico = "'.$result["id"].'"
        GROUP BY
          kusp.otkaz_id
      ') or die("Ошибка SQL (2): ".mysql_error());
      while ($result2 = mysql_fetch_array($query2)) : // запрос по отказным на лицо
      ?>
        <tr>
          <td><?=$result2["type"]?></td>
          <td align="center"><?=$result2["data_resh"]?></td>
          <td align="center"><b><?=$result2["uk"]?></b></td>
          <td align="center"><b><?=$result2["upk"]?></b></td>
          <td class="kusp_cell"><?=$result2["kusp"]?></td>
          <td class="download_cell">
            <a href="#" target="_blank" class="download_link" id="<?= $result2["id_otkaz"]; ?>">
              <div class="download_link_icon">
                <span class="link_notice">Скачать файл</span>
              </div>
            </a>
          </td>
        </tr>
      <?php
      endwhile; // запрос по отказным на лицо
    endwhile; // общий запрос
    ?>
    </table>
    <?= $listing;?>
    <?php else:?>
    <center><b>Материалы отсутствуют</b></center>
  <?php
  endif; // если результат поиска по лицу не пустой
// -------- поиск по лицу -------- //
else: // если поиск по лицу
?>
<div class="error" id="error_main">Не указаны поисковые параметры!</div>
<?php
endif;
?>
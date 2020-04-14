<?php
require('d:/www.sites/const.php');
require(KERNEL.'functions.php');

$connect = ftp_connect('...');
$login = ftp_login($connect, 'files', 'files');
$reports = array('kusp.txt', 'lico.txt', 'org.txt', 'svyaz.txt');
foreach ($reports as $file) {
  if (is_file(DIR_FILES.'/'.$file)) {
    unlink(DIR_FILES.'/'.$file);
  }
}
$lastSwap = lastSwap('Cronos');
$LAST_SWAP_DATE = $lastSwap['LAST_SWAP_DATE'];
//$LAST_SWAP_DATE = '2015-01-01';
$LAST_SWAP_TIME = $lastSwap['LAST_SWAP_TIME'];
//$LAST_SWAP_TIME = '000:00:00';
$CURR_DATE = date('Y-m-d');
$CURR_TIME = date('H:i:s');
$arhName = '/Отказные_'.date('Y-m-d_H.i.s').'.zip';

$db = mysql_connect('localhost', '...', '...', "set names 'utf8'");
if(!$db) {
  echo '<center><p><b>Невозможно подключиться к серверу базы данных !</b></p></center>';
  exit();
}
//Проверяем доступность нужной БД
if(!mysql_select_db('obv_otk', $db)) {
  echo '<center><p><b>База данных obv_otk недоступна!</b></p></center>';
  exit();
}
mysql_query("set names 'cp1251'");


// ******** выборка отказных ******** //
$query = mysql_query('
  SELECT
    IF(o.status = 1, "Первичный", "Доп.проверка") as status,
    soo.ovd,
    ss.slujba as service,
    GROUP_CONCAT( DISTINCT o.sotr_f, " ", o.sotr_i, " ", o.sotr_o) as employer,
    o.upk,
    DATE_FORMAT(o.data_resh, "%d.%m.%Y") as data_resh,
    GROUP_CONCAT( DISTINCT
        sok.ovd, " №", k.kusp, " от ", DATE_FORMAT(k.data, "%d.%m.%Y")
      SEPARATOR ", "
    ) as kusp,
    GROUP_CONCAT( DISTINCT
        su.st
      SEPARATOR ", "
    ) as criminal,
    GROUP_CONCAT( DISTINCT
      year(o.data_resh), "год/", soo.name_dir_otk, "/",
      CASE month(o.data_resh)
        WHEN "01" THEN "01_январь"
        WHEN "02" THEN "02_февраль"
        WHEN "03" THEN "03_Март"
        WHEN "04" THEN "04_Апрель"
        WHEN "05" THEN "05_Май"
        WHEN "06" THEN "06_Июнь"
        WHEN "07" THEN "07_Июль"
        WHEN "08" THEN "08_Август"
        WHEN "09" THEN "09_Сентябрь"
        WHEN "10" THEN "10_Октябрь"
        WHEN "11" THEN "11_Ноябрь"
        WHEN "12" THEN "12_Декабрь"
      END, 
      "/", o.file_final
    ) as path,
    o.id,
    DATE_FORMAT(o.create_date, "%d.%m.%Y") as create_date
  FROM
    otkaz as o
  LEFT JOIN
    spr_ovd as soo
     ON soo.id_ovd = o.id_ovd
  LEFT JOIN
    spr_slujba as ss
      ON ss.id_slujba = o.id_slujba
  LEFT JOIN
    kusp as k
      ON k.otkaz_id = o.id
    LEFT JOIN
      spr_ovd as sok
        ON sok.id_ovd = k.ovd
  LEFT JOIN
    relatives_uk_otk as relU
      ON relU.id_otkaz = o.id
    LEFT JOIN
      spr_uk as su
        ON su.id_uk = relU.id_st
  WHERE
    o.deleted = 0 AND
    ((o.create_date = "'.$LAST_SWAP_DATE.'" AND o.create_time >= "'.$LAST_SWAP_TIME.'") OR
    (o.create_date > "'.$LAST_SWAP_DATE.'" AND o.create_date < "'.$CURR_DATE.'") OR
    (o.create_date = "'.$CURR_DATE.'" AND o.create_time <= "'.$CURR_TIME.'"))
  GROUP BY
    o.id
') or die(mysql_error());
if (mysql_num_rows($query)) { // если есть записи для экспорта
  $licoArray = $orgArray = array();
  $zip = new ZipArchive;
    $zip -> open(DIR_FILES.$arhName, ZipArchive::CREATE);
  $fileList = ftp_nlist($connect, '.'); // ищем экспортированные файлы на FTP
  if (count($fileList) > 1) {
    foreach ($fileList as $file) {
      if (in_array($file, $reports)) {
        ftp_get($connect, DIR_FILES.'/'.$file, $file, FTP_BINARY); // если есть, копируем
      }
    }
  }
  $file = fopen(DIR_FILES.'/kusp.txt', "a"); // создаем файл с метками текущей даты и времени
  while ($result = mysql_fetch_array($query)) {
    if (is_file(DIR_REFUSE . $result["path"])) {
      $otkArray[] = $result["id"]; // массив экспортированных отказных
      $str = '++ ДД|++ НН|++ RG|01 '.$result["status"].'|02 '.$result["ovd"].'|03 '.$result["service"].'|04 '.$result["employer"].'|05 '.$result["upk"].'|10 '.$result["data_resh"].'|06 '.$result["kusp"].'|09 '.$result["criminal"].'|20 m:/12_2... Отказные материалы/'.$result["path"].'|17 '.$result["id"].'|18 '.$result["create_date"].'|++ КК|++ ЯЯ|';
      fwrite($file, str_replace("&QUOT;", '"', $str)."\n");
      $zip -> addFile(DIR_REFUSE . $result["path"], iconv("windows-1251", "CP866", $result["path"]));
    } else {
      mysql_query('
        UPDATE otkaz
        SET is_file = 0
        WHERE id = "'.$result["id"].'"
      ') or mysql_error();
    }
  }
    $zip -> close();
  fclose($file);
  ftp_put($connect, 'kusp.txt', DIR_FILES.'/kusp.txt', FTP_ASCII);
}
// ^^^^^^^^ выборка отказных ^^^^^^^^ //

// ******** выборка связей ******** //
if (count($otkArray)) {
  $query = mysql_query('
    SELECT
      spr.type,
      r.id,
      r.id_lico,
      r.id_org,
      r.id_otkaz,
      DATE_FORMAT(r.create_date, "%d.%m.%Y") as create_date
    FROM
      relatives as r
    LEFT JOIN
      spr_relatives as spr
        ON spr.id = r.type
    WHERE
      (r.create_date = "'.$LAST_SWAP_DATE.'" AND r.create_time >= "'.$LAST_SWAP_TIME.'") OR
      (r.create_date > "'.$LAST_SWAP_DATE.'" AND r.create_date < "'.$CURR_DATE.'") OR
      (r.create_date = "'.$CURR_DATE.'" AND r.create_time <= "'.$CURR_TIME.'")
  ') or die(mysql_error());
  if (mysql_num_rows($query)) { // если есть записи для экспорта
    $file = fopen(DIR_FILES.'/svyaz.txt', "a"); // создаем файл с метками текущей даты и времени
    while ($result = mysql_fetch_array($query)) {
      if (in_array($result["id_otkaz"], $otkArray)) {
        $licoArray[] = $result["id_lico"]; // массив лиц, попавших по связям в выборку отказных
        $orgArray[] = $result["id_org"]; // массив организаций, попавших по связям в выборку отказных
        $str = '++ ДД|++ НН|++ SV|01 '.$result["type"].'|02 '.$result["id"].'|06 '.$result["id_lico"].'|08 '.$result["id_org"].'|07 '.$result["id_otkaz"].'|03 '.$result["create_date"].'|++ КК|++ ЯЯ|';
        fwrite($file, str_replace("&QUOT;", '"', $str)."\n");
      }
    }
    fclose($file);
    ftp_put($connect, 'svyaz.txt', DIR_FILES.'/svyaz.txt', FTP_ASCII);
  }
}
// ^^^^^^^^ выборка связей ^^^^^^^^ //


// ******** выборка лиц ******** //
if (count($otkArray) && count($licoArray)) {
  $query = mysql_query('
    SELECT
      l.surname,
      l.name,
      l.fath_name,
      l.borth,
      l.id,
      l.create_date
    FROM
      lico as l
    WHERE
      (l.create_date = "'.$LAST_SWAP_DATE.'" AND l.create_time >= "'.$LAST_SWAP_TIME.'") OR
      (l.create_date > "'.$LAST_SWAP_DATE.'" AND l.create_date < "'.$CURR_DATE.'") OR
      (l.create_date = "'.$CURR_DATE.'" AND l.create_time <= "'.$CURR_TIME.'")
  ') or die(mysql_error());
  if (mysql_num_rows($query)) { // если есть записи для экспорта
    $file = fopen(DIR_FILES.'/lico.txt', "a"); // создаем файл с метками текущей даты и времени
    while ($result = mysql_fetch_array($query)) {
      if (in_array($result["id"], $licoArray)) {
        $str = '++ ДД|++ НН|01 '.$result['surname'].'|02 '.$result['name'].'|03 '.$result['fath_name'].'|04 '.$result['borth'].'|10 '.$result['id'].'|11 '.$result['create_date'].'|++ КК|++ ЯЯ|';
        fwrite($file, str_replace("&QUOT;", '"', $str)."\n");
      }
    }
    fclose($file);
    ftp_put($connect, 'lico.txt', DIR_FILES.'/lico.txt', FTP_ASCII);
  }
}
// ^^^^^^^^ выборка лиц ^^^^^^^^ //

// ******** выборка организаций ******** //
if (count($otkArray) && count($orgArray)) {
  $query = mysql_query('
    SELECT
      o.org_name,
      o.id,
      DATE_FORMAT(o.create_date, "%d.%m.%Y") as create_date
    FROM
      organisations as o
    WHERE
      (o.create_date = "'.$LAST_SWAP_DATE.'" AND o.create_time >= "'.$LAST_SWAP_TIME.'") OR
      (o.create_date > "'.$LAST_SWAP_DATE.'" AND o.create_date < "'.$CURR_DATE.'") OR
      (o.create_date = "'.$CURR_DATE.'" AND o.create_time <= "'.$CURR_TIME.'")
  ') or die(mysql_error());
  if (mysql_num_rows($query)) { // если есть записи для экспорта
    $file = fopen(DIR_FILES.'/org.txt', "a"); // создаем файл с метками текущей даты и времени
    while ($result = mysql_fetch_array($query)) {
      if (in_array($result["id"], $orgArray)) {
        $str = '++ ДД|++ НН|++ UL|01 '.$result["org_name"].'|07 '.$result["id"].'|08 '.$result["create_date"].'|++ КК|++ ЯЯ|';
        fwrite($file, str_replace("&QUOT;", '"', $str)."\n");
      }
    }
    fclose($file);
    ftp_put($connect, 'org.txt', DIR_FILES.'/org.txt', FTP_ASCII);
  }
}
// ^^^^^^^^ выборка организаций ^^^^^^^^ //



if (ftp_put($connect, $arhName, DIR_FILES.$arhName, FTP_BINARY)) {
  unlink(DIR_FILES.$arhName);
}
ftp_close($connect);

logSwap('Cronos', $CURR_DATE, $CURR_TIME);

emptySessDirRemove(); // за одним удаляем пустые и старые каталоги несуществующих сессий
?>
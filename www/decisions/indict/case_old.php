<?php
// var_dump($_GET);
// die();
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

require('require.php');

$page_title = 'Уголовное дело';
$breadcrumbs = array(
  'Процессуальные документы, вынесенные по результатам расследования УД' => 'index.php',
  '' => ''
);


try {
  if (empty($_GET['id']))
    throw new Exception();
  
  $id = (integer)$_GET['id'];
  
  if (empty($id))
    throw new Exception();
  
  $f1 = $f2 = $f3 = $f5 = $f6 = array();
  
  // -------- форма 1, 1.1 -------- //
  $query = '
    SELECT SQL_NO_CACHE
      f1ep.`d3n_f10` as `number`, YEAR(f1ep.`d11_f10`) as `year`,
      CONCAT(f1ep.`vd3o_f10`, " № ", f1ep.`d3n_f10`, " от ", DATE_FORMAT(f1ep.`d11_f10`, "%d.%m.%Y"), ", ", f1ep.`vd01_f10`) as `str`,
      GROUP_CONCAT(
        DISTINCT f1ep.`vd01o_f1`
        SEPARATOR ", "
      ) as `org`,
      GROUP_CONCAT(
        DISTINCT
        f1.`vd10_f10`,
        IF(f1.`fam_svr` IS NOT NULL, CONCAT(" ", SUBSTRING(UPPER(f1.`fam_svr`), 1, 1), SUBSTRING(LOWER(f1.`fam_svr`), 2, LENGTH(f1.`fam_svr`) - 1)), "")
        SEPARATOR ", "
      ) as `emp`,
      GROUP_CONCAT(
        DISTINCT f1ep.`vp25_f11`, " ", DATE_FORMAT(f1ep.`d25_f11`, "%d.%m.%Y")
        SEPARATOR ","
      ) as `decision`,
      GROUP_CONCAT(
        DISTINCT f1ep.`vp251_f11`, " ", DATE_FORMAT(f1ep.`d251_f11`, "%d.%m.%Y")
        SEPARATOR ","
      ) as `jud_decision`,
      GROUP_CONCAT(
        DISTINCT
        CONCAT(
        CASE f1ep.`d16_f10`
          WHEN 1 THEN "ч.1 ст.30, "
          WHEN 2 THEN "ч.3 ст.30, "
          ELSE ""
        END,
        "ст.", f1ep.`d13_f10`, IF(f1ep.`d13p_f10` IS NOT NULL, CONCAT(" п.", f1ep.`d13p_f10`), ""), " УК РФ")
        ORDER BY f1ep.`d13_f10`
        SEPARATOR ", "
      ) as `qual`,
      GROUP_CONCAT(
        DISTINCT
        CASE
          WHEN f1ep.`kusp` IS NOT NULL THEN 
            CONCAT(\'<a href="/wonc/ek.php?id=\', f1ep.`kusp`, \'" target="_blank">\', CONCAT("КУСП №", f1ep.`n05_f10`, " от ", DATE_FORMAT(f1ep.`d05_f10`, "%d.%m.%Y")), "</a>")
          ELSE
            CONCAT("КУСП №", f1ep.`n05_f10`, " от ", DATE_FORMAT(f1ep.`d05_f10`, "%d.%m.%Y"))
        END
        ORDER BY f1ep.`d05_f10` DESC, f1ep.`n05_f10` ASC
        SEPARATOR "|x|"
      ) as `kusp`,
      (SELECT MAX(`d04_f10`) FROM `ic_f1_f11` WHERE N_GASPS = f1ep.N_GASPS
         ) as `ep`,
      GROUP_CONCAT(
        f1ep.`d04_f10`, " &mdash; ", f1ep.`d12fab_f10`
        SEPARATOR "|x|"
      ) as `fabulas`,
      GROUP_CONCAT(
        DISTINCT
        f1ep.`vd34_f11`
        SEPARATOR ", "
      ) as `discloser`,
      CONCAT(
        IFNULL(
          GROUP_CONCAT(
            DISTINCT
            f1ep.`va34d_f11`
            SEPARATOR ", "
          ), ""
        ), IF(f1ep.`vb34d_f11` IS NOT NULL, ", ", ""),
        IFNULL(
          GROUP_CONCAT(
            DISTINCT
            f1ep.`vb34d_f11`
            SEPARATOR ", "
          ), ""
        ), IF(f1ep.`vc34d_f11` IS NOT NULL, ", ", ""),
        IFNULL(
          GROUP_CONCAT(
            DISTINCT
            f1ep.`vc34d_f11`
            SEPARATOR ", "
          ), ""
        ), IF(f1ep.`vd34d_f11` IS NOT NULL, ", ", ""),
        IFNULL(
          GROUP_CONCAT(
          DISTINCT
          f1ep.`vd34d_f11`
          SEPARATOR ", "
          ), ""
        )
      ) as `contrib`,
      DATE_FORMAT(f1ep.`d06_f10`, "%d.%m.%Y") as `send_date`,
      DATE_FORMAT(f1ep.`d07_f10`, "%d.%m.%Y") as `arr_date`
    FROM
      `ic_f1_f11` as f1
    JOIN
      `ic_f1_f11` as f1ep ON f1.N_GASPS = f1ep.N_GASPS
    WHERE
      f1.`id` = @cc
    GROUP BY
      f1.N_GASPS
    ORDER BY
      f1ep.`d06_f10` DESC
  ';
  // f1.`d3n_f10`, f1.`d3g_f10`, f1.`d01_f10`, f1.`d1p_f10`
  require(KERNEL.'connect.php');
  $result = $db->query('SET group_concat_max_len = 10000');
  $db->query('SET @cc = '.$id);
  
  $result = $db->query($query);
  
  // echo $query;
  // die();
  
  if ($result->num_rows == 0)
    throw new Exception();
  
  $f1 = $result->fetch_object();
  $result->close();
  
  $f1->kusp = explode('|x|', $f1->kusp);
  $f1->fabulas = explode('|x|', $f1->fabulas);
  // -------- форма 1, 1.1 -------- //
  
  // -------- форма 2 -------- //
  $query = '
    SELECT
      f1.`d3n_f10` as `number`, 
      YEAR(f1.`d11_f10`) as `year`, 
      f1.`d01_f10` as `department`
    FROM
      `ic_f1_f11` as f1
    WHERE
      f1.`id` = @cc
    
    UNION
    
    SELECT
      DISTINCT
      IF(f3.`d08n_f3` = f1.`d3n_f10` AND f3.`d08g_f3` = YEAR(f1.`d11_f10`), f3.`d3n_f3`, f3.`d08n_f3`),
      IF(f3.`d08n_f3` = f1.`d3n_f10` AND f3.`d08g_f3` = YEAR(f1.`d11_f10`), YEAR(f3.`d06_f3`), f3.`d08g_f3`),
      f1.`d01_f10`
    FROM
      `ic_f1_f11` as f1
    JOIN
      `ic_f3_f4` as f3 ON f3.N_GASPS = f1.N_GASPS
	      OR f3.d8gasps_F3 = f1.N_GASPS
    WHERE
      f3.`d08_f3` IS NOT NULL AND
      f1.`id` = @cc';
  $result = $db->query($query);
  $_t = array();
  while ($row = $result->fetch_object()) {
    $_t[] = array('number' => $row->number, 'year' => $row->year, 'department' => $row->department);
  }
  $result->close();
  if (!empty($_t)) {
    $str = array();
    foreach ($_t as $n => $par) {
      $str[] = '(f2.`d3n_f2` = '.$par['number'].' AND f2.`d3g_f2` = '.$par['year'].' AND f2.`d01_f2` = '.$par['department'].')';
    }
    $query = '
      SELECT SQL_NO_CACHE
        CONCAT(
          CONCAT(SUBSTRING(UPPER(f2.`d07_f2`), 1, 1), SUBSTRING(LOWER(f2.`d07_f2`), 2, LENGTH(f2.`d07_f2`) - 1)), " ", 
          CONCAT(SUBSTRING(UPPER(f2.`d08_f2`), 1, 1), SUBSTRING(LOWER(f2.`d08_f2`), 2, LENGTH(f2.`d08_f2`) - 1)), " ",
          CONCAT(SUBSTRING(UPPER(f2.`d09_f2`), 1, 1), SUBSTRING(LOWER(f2.`d09_f2`), 2, LENGTH(f2.`d09_f2`) - 1)), " ",
          DATE_FORMAT(f2.`d11d_f2`, "%d.%m.%Y"),
          IF(f2.`vd40_f2` IS NOT NULL, CONCAT(" (", f2.`vd40_f2`, ")"), ""),
          CONCAT(
            \'<div class="bottomTextBlock gray right-align"> (\',
            IF(f1.`id` <> @cc, CONCAT(\'<a href="case.php?id=\', f1.`id`, \'">\'), ""),
            "у/д №", f2.`d3n_f2`,
            IF(f1.`id` <> @cc, "</a>", ""),
            ")</div>"
          )
        ) as `face`
      FROM
        `ic_f2` as f2 USE INDEX(`d3n_f2`)
      LEFT JOIN
        `ic_f1_f11` as f1 ON f2.N_GASPS = f1.N_GASPS
      WHERE
        '.implode(' OR ', $str).'
    ';
    
    $result = $db->query($query);
    while ($row = $result->fetch_object()) {
      $f2[] = $row;
    }
    $result->close();
  }
  // -------- форма 2 -------- //
  
  // -------- форма 3 -------- //
  $query_bad = '
  SELECT SQL_NO_CACHE
    DISTINCT
    IF(f3.N_GASPS = f1.N_GASPS,
      (SELECT `id` FROM `ic_f1_f11` WHERE f3.N_GASPS = f1.N_GASPS AND `d3o_f10` = 1 LIMIT 1),
      (SELECT `id` FROM `ic_f1_f11` WHERE f3.d8gasps_F3 = f1.N_GASPS AND `d3o_f10` = 1 LIMIT 1)
    ) as `id`,
    IF(f3.N_GASPS = f1.N_GASPS, f3.`d3n_f3`, f3.`d08n_f3`) as `number`,
    IF(f3.d8gasps_F3 = f1.N_GASPS, YEAR(f3.`d06_f3`), f3.`d08g_f3`) as `year`,
    f3.`d01_f3` as `department`,
    TRIM(CONCAT(
      IF(f3.`d071_f3` IS NOT NULL,
        CONCAT(DATE_FORMAT(f3.`d071_f3`, "%d.%m.%Y"), " ", f3.`vd7k_f3`, ", "),
      ""),
      IF(f3.`d08_f3` IS NOT NULL,
        CONCAT(
          IFNULL(DATE_FORMAT(f3.`d081d_f3`, "%d.%m.%Y"), ""), " ",
          CASE
          WHEN f3.`d08n_f3` = f1.`d3n_f10` AND f3.`d08g_f3` = YEAR(f1.`d11_f10`) THEN
          
            CONCAT(
              f3.`vd08_f3`, \' <a href="case.php?id=\', 
              (SELECT `id` FROM `ic_f1_f11` WHERE f3.d8gasps_F3 = f1.N_GASPS AND `d3o_f10` = 1 LIMIT 1), \'">\',
              IFNULL(f3.`d08n_f3`, ""), " от ", IFNULL(f3.`d08g_f3`, ""), " г.")  
          ELSE
            CONCAT(
              f3.`vd08_f3`, \' <a href="case.php?id=\', 
              (SELECT `id` FROM `ic_f1_f11` WHERE f3.N_GASPS = f1.N_GASPS AND `d3o_f10` = 1 LIMIT 1), \'">\',
              f3.`d3n_f3`, " от ", DATE_FORMAT(f3.`d06_f3`, "%d.%m.%Y"))  
          
        END, "</a>, "
        ),
      ""),
      IF(f3.`vd09_f3` IS NOT NULL, 
        CONCAT(
           IFNULL(DATE_FORMAT(f3.`d09_f3`, "%d.%m.%Y"), ""), " Продлен срок расследования ", 
           f3.`vd09_f3`, " до ",
           DATE_FORMAT(f3.`d10_f3`, "%d.%m.%Y"), ", "
        ),
      ""),
      IF(f3.`vd11_f3` IS NOT NULL,
        CONCAT(f3.`vd11_f3`, ", "),
      ""),
      IF(f3.`d12_f3` IS NOT NULL,
          CONCAT(IFNULL(DATE_FORMAT(f3.`d121_f3`, "%d.%m.%Y"), ""), " ", f3.`vd12_f3`,
          IF(f3.`d12f_f3` IS NOT NULL,
            CONCAT(
              " (",
              IF(f3.`d12r_f3` IS NOT NULL, CONCAT("РД №", f3.`d12r_f3`, ", "), ""),
              IF(f3.`d12_f3` = 1, " разыскиваемый: ", "Заболевший: "), 
              CONCAT(SUBSTRING(UPPER(f3.`d12f_f3`), 1, 1), SUBSTRING(LOWER(f3.`d12f_f3`), 2, LENGTH(f3.`d12f_f3`) - 1)), " ", 
              CONCAT(SUBSTRING(UPPER(f3.`d12i_f3`), 1, 1), SUBSTRING(LOWER(f3.`d12i_f3`), 2, LENGTH(f3.`d12i_f3`) - 1)), " ",
              CONCAT(SUBSTRING(UPPER(f3.`d12ot_f3`), 1, 1), SUBSTRING(LOWER(f3.`d12ot_f3`), 2, LENGTH(f3.`d12ot_f3`) - 1)),
              ")"
            ),
          ""), ", "
        ),
      ""),
      IF(f3.`d13_f3` IS NOT NULL,
        CONCAT(IFNULL(DATE_FORMAT(f3.`d131_f3`, "%d.%m.%Y"), ""), " ", f3.`vd13_f3`, ", ")
      ,""),
      IF(f3.`d16_f3` IS NOT NULL,
        CONCAT(IFNULL(DATE_FORMAT(f3.`d161_f3`, "%d.%m.%Y"), ""), " ", f3.`vd16_f3`, ", ")
      ,""),
      IF(f3.`d17_f3` IS NOT NULL,
        CONCAT(IFNULL(DATE_FORMAT(f3.`d171_f3`, "%d.%m.%Y"), ""), " ", f3.`vd17_f3`)
      ,"")
    )) as `decisions`
  FROM
    `ic_f1_f11` as f1
  JOIN
    `ic_f3_f4` as f3 ON  f3.N_GASPS = f1.N_GASPS
      OR  f3.d8gasps_F3 = f1.N_GASPS
  WHERE
    f1.`id` = @cc';
  
  $query = '
      SELECT SQL_NO_CACHE
        DISTINCT
        IF(f3.N_GASPS = f1.N_GASPS,
          (SELECT f1.`id` FROM `ic_f1_f11` as f1 WHERE f3.d8gasps_F3 = f1.N_GASPS AND f1.`d3o_f10` = 1 LIMIT 1),
          (SELECT f1.`id` FROM `ic_f1_f11` as f1 WHERE f3.N_GASPS = f1.N_GASPS AND f1.`d3o_f10` = 1 LIMIT 1)
        ) as `id`,
        IF(f3.N_GASPS = f1.N_GASPS, f3.`d08n_f3`, f3.`d3n_f3`) as `number`,
        IF(f3.N_GASPS = f1.N_GASPS, f3.`d08g_f3`, YEAR(f3.`d06_f3`)) as `year`,
        f3.`d01_f3` as `department`,
        TRIM(CONCAT(
          IF(f3.`d071_f3` IS NOT NULL,
            CONCAT(DATE_FORMAT(f3.`d071_f3`, "%d.%m.%Y"), " ", f3.`vd7k_f3`, ", "),
          ""),
          IF(f3.`d08_f3` IS NOT NULL,
            CONCAT(
              IFNULL(DATE_FORMAT(f3.`d081d_f3`, "%d.%m.%Y"), ""), " ",
              CASE
              WHEN f3.`d08n_f3` = f1.`d3n_f10` AND f3.`d08g_f3` = YEAR(f1.`d11_f10`) THEN
              
                CONCAT(
                  f3.`vd08_f3`, \' <a href="case.php?id=\', 
                  (SELECT f1.`id` FROM `ic_f1_f11` as f1 WHERE f3.N_GASPS = f1.N_GASPS AND f1.`d3o_f10` = 1 LIMIT 1), \'">\',
                  IFNULL(f3.`d3n_f3`, ""), " от ", IFNULL(f3.`d3g_f3`, ""), " г.")  
              ELSE
                CONCAT(
                  f3.`vd08_f3`, \' <a href="case.php?id=\',
                  (SELECT f1.`id` FROM `ic_f1_f11` as f1 WHERE f3.d8gasps_F3 = f1.N_GASPS AND f1.`d3o_f10` = 1 LIMIT 1), \'">\',
                  f3.`d08g_f3`, " от ", DATE_FORMAT(f3.`d06_f3`, "%d.%m.%Y"))  
              
            END, "</a>, "
            ),
          ""),
          IF(f3.`vd09_f3` IS NOT NULL, 
            CONCAT(
               IFNULL(DATE_FORMAT(f3.`d09_f3`, "%d.%m.%Y"), ""), " Продлен срок расследования ", 
               f3.`vd09_f3`, " до ",
               DATE_FORMAT(f3.`d10_f3`, "%d.%m.%Y"), ", "
            ),
          ""),
          IF(f3.`vd11_f3` IS NOT NULL,
            CONCAT(f3.`vd11_f3`, ", "),
          ""),
          IF(f3.`d12_f3` IS NOT NULL,
              CONCAT(IFNULL(DATE_FORMAT(f3.`d121_f3`, "%d.%m.%Y"), ""), " ", f3.`vd12_f3`,
              IF(f3.`d12f_f3` IS NOT NULL,	
                CONCAT(
                  " (",
                  IF(f3.`d12r_f3` IS NOT NULL, CONCAT("РД №", f3.`d12r_f3`, ", "), ""),
                  IF(f3.`d12_f3` = 1, " разыскиваемый: ", "Заболевший: "), 
                  CONCAT(SUBSTRING(UPPER(f3.`d12f_f3`), 1, 1), SUBSTRING(LOWER(f3.`d12f_f3`), 2, LENGTH(f3.`d12f_f3`) - 1)), " ", 
                  CONCAT(SUBSTRING(UPPER(f3.`d12i_f3`), 1, 1), SUBSTRING(LOWER(f3.`d12i_f3`), 2, LENGTH(f3.`d12i_f3`) - 1)), " ",
                  CONCAT(SUBSTRING(UPPER(f3.`d12ot_f3`), 1, 1), SUBSTRING(LOWER(f3.`d12ot_f3`), 2, LENGTH(f3.`d12ot_f3`) - 1)),
                  ")"
                ),
              ""), ", "
            ),
          ""),
          IF(f3.`d13_f3` IS NOT NULL,
            CONCAT(IFNULL(DATE_FORMAT(f3.`d131_f3`, "%d.%m.%Y"), ""), " ", f3.`vd13_f3`, ", ")
          ,""),
          IF(f3.`d16_f3` IS NOT NULL,
            CONCAT(IFNULL(DATE_FORMAT(f3.`d161_f3`, "%d.%m.%Y"), ""), " ", f3.`vd16_f3`, ", ")
          ,""),
          IF(f3.`d17_f3` IS NOT NULL,
            CONCAT(IFNULL(DATE_FORMAT(f3.`d171_f3`, "%d.%m.%Y"), ""), " ", f3.`vd17_f3`)
          ,"")
        )) as `decisions`,
        f3.`d08_f3` as `connect`
      FROM
        `ic_f1_f11` as f1
      JOIN
        `ic_f3_f4` as f3 ON  
              f3.N_GASPS = f1.N_GASPS
          OR  f3.d8gasps_F3 = f1.N_GASPS
      WHERE
        f1.`id` = @cc
  ';
    
  $result = $db->query($query);
  $united = array();
  while ($row = $result->fetch_object()) {
    /*if (empty($row->decisions)) continue;*/
    if (!empty($row->number))
      $united[] = array('number' => $row->number, 'year' => $row->year, 'department' => $row->department);
    $f3[] = $row;
  }
  $result->close();
  // -------- форма 3 -------- //
  
  // -------- форма 5 -------- //
  if (!empty($_t)) {
    $str = array();
    foreach ($_t as $n => $par) {
      $str[] = '(f5.`d03n_f5` = '.$par['number'].' AND YEAR(f5.`d032_f5`) = '.$par['year'].' AND f5.`d01_f5` = '.$par['department'].')';
    }
    
   $query = '
      SELECT SQL_NO_CACHE DISTINCT
        DISTINCT
        CONCAT(
          CASE f5.`d08_f5`
            WHEN 1 THEN
              CONCAT(
                SUBSTRING(UPPER(f5.`d081fam_pot_naz_ooo_f5`), 1, 1), SUBSTRING(LOWER(f5.`d081fam_pot_naz_ooo_f5`), 2, LENGTH(f5.`d081fam_pot_naz_ooo_f5`) - 1), " ", 
                IF(f5.`d081imya_f5` IS NOT NULL, CONCAT(SUBSTRING(UPPER(f5.`d081imya_f5`), 1, 1), SUBSTRING(LOWER(f5.`d081imya_f5`), 2, LENGTH(f5.`d081imya_f5`) - 1), " "), ""),
                IF(f5.`d081otch_f5` IS NOT NULL, CONCAT(SUBSTRING(UPPER(f5.`d081otch_f5`), 1, 1), SUBSTRING(LOWER(f5.`d081otch_f5`), 2, LENGTH(f5.`d081otch_f5`) - 1), " "), "")
              )
            WHEN 2 THEN
              f5.`d081fam_pot_naz_ooo_f5`
          END
        ) as `face`,
        CONCAT(
          \'<div class="bottomTextBlock gray right-align"> (у/д \',
          GROUP_CONCAT(
            DISTINCT
            IF(f1.`id` <> @cc, CONCAT(\'<a href="case.php?id=\', f1.`id`, \'">\'), ""),
            "№", f5.`d03n_f5`,
            IF(f1.`id` <> @cc, "</a>", "")
            SEPARATOR ", "
          ),
          ")</div>"
        ) as `case`
      FROM
        `ic_f5` as f5
      JOIN 
        `ic_f1_f11` as f1 ON f5.N_GASPS = f1.N_GASPS
      WHERE
        '.implode(' OR ', $str).'
      /*GROUP BY f5.`d081fam_pot_naz_ooo_f5`, f5.`d081imya_f5`, f5.`d081otch_f5`*/
     ';

    $result = $db->query($query);
    while ($row = $result->fetch_object()) {
      if (empty($row->face)) continue;
      $f5[] = $row;
    }
    $result->close();
  }
  // -------- форма 5 -------- //
  
  // -------- форма 6 -------- //
  if (!empty($_t)) {
    $str = array();
    foreach ($_t as $n => $par) {
      $str[] = '(f6.`d03n_f6` = '.$par['number'].' AND f6.`d03g_f6` = '.$par['year'].' AND f6.`d01_f6` = '.$par['department'].')';
    }
    
    $query = '
      SELECT SQL_NO_CACHE
        DISTINCT
        CONCAT(
          CONCAT(SUBSTRING(UPPER(f6.`d03_f6`), 1, 1), SUBSTRING(LOWER(f6.`d03_f6`), 2, LENGTH(f6.`d03_f6`) - 1)), " ", 
          CONCAT(SUBSTRING(UPPER(f6.`d04_f6`), 1, 1), SUBSTRING(LOWER(f6.`d04_f6`), 2, LENGTH(f6.`d04_f6`) - 1)), " ",
          CONCAT(SUBSTRING(UPPER(f6.`d05_f6`), 1, 1), SUBSTRING(LOWER(f6.`d05_f6`), 2, LENGTH(f6.`d05_f6`) - 1)), " ",
          DATE_FORMAT(f6.`d06_f6`, "%d.%m.%Y"), " ",
          TRIM(
               CONCAT(IF(f6.`va191_mera_f6` IS NOT NULL, CONCAT(f6.`va191_mera_f6`, " "), ""),
                      IF(f6.`vd14_f6` IS NOT NULL, CONCAT(" &mdash; ", f6.`vd14_f6`, " ("), ""),
                      IF(f6.`a191_sol_f6` IS NOT NULL, CONCAT(f6.`a191_sol_f6`, " г. "), ""),
                      IF(f6.`a191_som_f6` IS NOT NULL, CONCAT(f6.`a191_som_f6`, " мес. "), ""),
                      IF(f6.`a191_den_f6` IS NOT NULL, CONCAT(f6.`a191_den_f6`, " дн. "), ""),
                      IF(f6.`a191_chas_f6` IS NOT NULL, CONCAT(f6.`a191_chas_f6`, " ч."), "")
                     )
               ), IF(f6.`vd14_f6` IS NOT NULL, ")", "")
        ) as `decisions`
      FROM
        `ic_f6` as f6
      WHERE
        '.implode(' OR ', $str).'
      ORDER BY 
        `decisions`
     ';
    
    $result = $db->query($query);
    if ($result->num_rows > 0) {
      while ($row = $result->fetch_object()) {
        $f6[] = $row;
      }
    }
    $result->close();
  }
  // -------- форма 6 -------- //
  
  // -------- возможно соединенные -------- //
 /* $query = '
    SELECT SQL_NO_CACHE
      DISTINCT
      IF(s.`d3n_sud` = @n AND s.`d3g_sud` = @y, s.`d8n_sud`, s.`d3n_sud`) as `number`,
      IF(s.`d3n_sud` = @n AND s.`d3g_sud` = @y, s.`d8g_sud`, s.`d3g_sud`) as `year`
    FROM
      `ic_s_ug_d` as s
    WHERE 
      ((s.`d3n_sud` = @n AND s.`d3g_sud` = @y) OR (s.`d8n_sud` = @n AND s.`d8g_sud` = @y))
  ';
  
  if (!empty($united)) {
    $str = array();
    foreach ($united as $cc) {
      $str[] = '(IF(s.`d3n_sud` = @n AND s.`d3g_sud` = @y, NOT (s.`d8n_sud` = '.$cc['number'].' AND s.`d8g_sud` = '.$cc['year'].'), NOT (s.`d3n_sud` = '.$cc['number'].' AND s.`d3g_sud` = '.$cc['year'].')))';
    }
    $query .= 'AND ('.implode(' AND ', $str).') ';
  }
  $result = $db->query('SET @n = '.$f1->number);
  $result = $db->query('SET @y = '.$f1->year);
  $result = $db->query($query);
  while ($row = $result->fetch_object()) {
    $ids[] = '(f1.`d3n_f10` = '.$row->number.' AND YEAR(f1.`d11_f10`) = '.$row->year.')';
  }
  $result->close();
  
  if (!empty($ids)) {
    $query = '
      SELECT
        f1.`id`, f1.`d3n_f10` as `number`, DATE_FORMAT(f1.`d11_f10`, "%d.%m.%Y") as `date`
      FROM
        `ic_f1_f11` as f1
      WHERE
        '.implode(' OR ', $ids).'
      ORDER BY
        f1.`d3n_f10`, f1.`d11_f10`
    ';
    $result = $db->query($query);
    
    $un = array();
    while ($row = $result->fetch_object()) {
      $un[] = $row;
    }
    $result->close();
  }
  // -------- возможно соединенные -------- //
  */
  
  // -------- файлы -------- //
  $query = '
    SELECT
      un.`f1`, rel.`file`
    FROM
      `ic_f1_files` as rel
    JOIN
      `ic_f1_files` as un ON
        un.`file` = rel.`file` AND
        un.`deleted` = 0
    WHERE
      rel.`deleted` = 0 AND
      rel.`f1` = '.$id;
  $result = $db->query($query);
  
  $files = array();
  while ($row = $result->fetch_object()) {
    $uns[] = $row->f1;
    $files[] = $row->file;
  }
  $result->close();
  
  if (!empty($uns))
    $uns = array_unique($uns);

  if (!empty($files)) {
    $_t = array_unique($files);
    $files = null;
    foreach ($_t as $file) {
      $files[] = new ElFile($file);
    }
    unset($_t);
  }
  // -------- файлы -------- //
  
} catch(Exception $exc) {
  header('Location: /error/404.php');
}

if (!empty($_SESSION['indictment']['current'])) {
  if ($_SESSION['indictment']['current'] != $id) {
    unset($_SESSION['indictment']);
    $_SESSION['indictment']['current'] = $id;
  }
} else {
  if (!empty($_SESSION['indictment']))
    unset($_SESSION['indictment']);
  $_SESSION['indictment']['current'] = $id;
}

if (isset($_SESSION['indictment']['f3']))
  unset($_SESSION['indictment']['f3']);

if (isset($_SESSION['indictment']['possible']))
  unset($_SESSION['indictment']['possible']);

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>

</style>
<script>
</script>
<div class="header_row"><?= $f1->str ?></div>
<div class="ic_forms">
  <form id="indictment" type="json">
    <input type="hidden" value="registration" name="method"/>
    <div class="table_block woborders">
      <div class="fieldset">
        <div class="legenda">Ф1, Ф1.1 (Выявленное преступление, результаты расследования)</div>
        <table rules="none" border="0" cellpadding="3" class="result_table woborders" width="100%">
          <tr>
            <td align="right" width="120px">Орган:</td>
            <td><?= $f1->org ?></td>
          </tr>
          <tr>
            <td align="right" width="120px">В пр-ве:</td>
            <td><?= $f1->emp ?></td>
          </tr>
          <tr>
            <td align="right" width="120px">Квалификация:</td>
            <td><?= $f1->qual ?></td>
          </tr>
          <tr>
            <td align="right">Решение:</td>
            <td><?= $f1->decision ?></td>
          </tr>
          <tr>
            <td align="right">Суд.решение:</td>
            <td><?= $f1->jud_decision ?></td>
          </tr>
          <tr>
            <td align="right">Эпиздов:</td>
            <td><?= $f1->ep ?></td>
          </tr>
          
          <tr>
            <td align="right">
              КУСП:
              <?php if (count($f1->kusp) > 3) : ?>
                <div class="row center-align"><a href="#" class="maximize" target="resizable_kusp">Развернуть</a></div>
              <?php endif; ?>
            </td>
            <td>
              <?php foreach ($f1->kusp as $n => $k) : ?>
                <?php if ($n == 3) : ?>
                  <div class="resizable_kusp" style="display: none;">
                <?php endif; ?>
                  <?= $k ?><br />
                <?php if ($n >= 3 and $n == count($f1->kusp) - 1) : ?>
                  </div>
                <?php endif; ?>
              <?php endforeach;  ?>
            </td>
          </tr>
          
          <tr>
            <td align="right">
              Фабула(-ы) по эпизодам:
              <?php if (count($f1->fabulas) > 3) : ?>
                <div class="row center-align"><a href="#" class="maximize" target="resizable_ep">Развернуть</a></div>
              <?php endif; ?>
            </td>
            <td>
              <?php foreach ($f1->fabulas as $n => $fab) : ?>
                <?php if ($n == 3) : ?>
                  <div class="resizable_ep" style="display: none;">
                <?php endif; ?>
                  <?= $fab ?><br />
                <?php if ($n >= 3 and $n == count($f1->fabulas) - 1) : ?>
                  </div>
                <?php endif; ?>
              <?php endforeach;  ?>
            </td>
          </tr>
          
          <?php if (!empty($f1->discloser)) : ?>
            <tr>
              <td align="right">Лицо установлено:</td>
              <td><?= $f1->discloser ?></td>
            </tr>
          <?php endif; ?>
          
          <?php if (!empty($f1->contrib)) : ?>
            <tr>
              <td align="right">Служба, способств.:</td>
              <td><?= $f1->contrib ?></td>
            </tr>
          <?php endif; ?>
          
          <tr>
            <td align="right">Направл.в ИЦ:</td>
            <td><?= $f1->send_date ?></td>
          </tr>
          <tr>
            <td align="right">Поступ.в ИЦ:</td>
            <td><?= $f1->arr_date ?></td>
          </tr>
        </table>
      </div>
      
      <div class="fieldset">
        <div class="legenda">Ф3 (Движение уголовного дела)</div>
        <div class="result_table">
          <?php if (count($f3) > 0) : ?>
            <?php foreach ($f3 as $n => $decision) : ?>
              <?php if ($n == 5) : ?>
                <div class="result_row">
                  <div class="result_cell"><a href="#" class="maximize" target="resizable_dec">Развернуть</a></div>
                </div>
                <div class="resizable_dec" style="display: none;">
              <?php endif; ?>
              <div class="result_row">
                <div class="result_cell left-align"><?= $decision->decisions ?></div>
                
                <?php if (!empty($decision->number)) : ?>
                  <input type="hidden" value="<?= $decision->id ?>" name="united[f3][]"/>
                <?php endif; ?>
                
              </div>
              <?php if ($n >= 5 and $n == count($f3) - 1) : ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else : ?>
            Сведения отсутствуют
          <?php endif; ?>
        </div>
      </div>
      
      <?php if (!empty($un)) : ?>
        <div class="fieldset">
          <div class="legenda">Возможно соединенные</div>
          <div class="result_table checkbox">
            <?php if (count($un) > 3) : ?>
              <div class="result_row">
                <div class="result_cell"><a href="#" class="check_all">Выделить все</a></div>
              </div>
            <?php endif; ?>
            <?php foreach ($un as $n => $cc) : ?>
              <?php if ($n == 5) : ?>
                <div class="result_row">
                  <div class="result_cell"><a href="#" class="maximize" target="resizable_un">Развернуть</a></div>
                </div>
                <div class="resizable_un" style="display: none;">
              <?php endif; ?>
              <div class="result_row">
                <div class="result_cell left-align">
                  <input type="checkbox" value="<?= $cc->id ?>" name="united[possible][]" <?php if (!empty($uns) and in_array($cc->id, $uns)) echo 'checked' ?>/>
                  <a href="case.php?id=<?= $cc->id ?>">У/д №<?= $cc->number ?> от <?= $cc->date ?></a>
                </div>
              </div>
              <?php if ($n >= 5 and $n == count($un) - 1) : ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      
      <div class="fieldset">
        <div class="legenda">Ф6 (Решение суда первой инстанции)</div>
        <div class="result_table">
          <?php if (count($f6) > 0) : ?>
            <?php foreach ($f6 as $n => $decision) : ?>
              <?php if ($n == 5) : ?>
                <div class="result_row">
                  <div class="result_cell"><a href="#" class="maximize" target="resizable_f6">Развернуть</a></div>
                </div>
                <div class="resizable_f6" style="display: none;">
              <?php endif; ?>
              <div class="result_row">
                <div class="result_cell left-align"><?= $decision->decisions ?></div>
              </div>
              <?php if ($n >= 5 and $n == count($f3) - 1) : ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else : ?>
            Сведения отсутствуют
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="table_block woborders">
      <div class="fieldset">
        <div class="legenda">Ф2 (Лицо, совершившее преступление)</div>
        <div class="result_table">
          <?php if (count($f2) > 0) : ?>
            <?php foreach ($f2 as $n => $face) : ?>
              <?php if ($n == 3) : ?>
                <div class="result_row">
                  <div class="result_cell"><a href="#" class="maximize" target="resizable_f2">Развернуть</a></div>
                </div>
                <div class="resizable_f2" style="display: none;">
              <?php endif; ?>
              <div class="result_row">
                <div class="result_cell left-align"><?= $face->face ?></div>
              </div>
              <?php if ($n >= 3 and $n == count($f2) - 1) : ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else : ?>
            Сведения отсутствуют
          <?php endif; ?>
        </div>
      </div>
      
      <div class="fieldset">
        <div class="legenda">Ф5 (Потерпевший)</div>
        <div class="result_table">
          <?php if (count($f5) > 0) : ?>
            <?php foreach ($f5 as $n => $decision) : ?>
              <?php if ($n == 5) : ?>
                <div class="result_row">
                  <div class="result_cell"><a href="#" class="maximize" target="resizable_f5">Развернуть</a></div>
                </div>
                <div class="resizable_f5" style="display: none;">
              <?php endif; ?>
              <div class="result_row">
                <div class="result_cell left-align"><?= $decision->face ?> <?= $decision->case ?>
                </div>
              </div>
              <?php if ($n >= 5 and $n == count($f5) - 1) : ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else : ?>
            Сведения отсутствуют
          <?php endif; ?>
        </div>
      </div>
      
      <?php if (!empty($files)) : ?>
        <div class="fieldset">
          <div class="legenda">Электронные образы</div>
          <ul>
          <?php foreach ($files as $n => $file) : ?>
            <li>
              <?= ++$n ?>.
              <span class="actions_list">
                <a href="#" method="file_preview" file="<?= $file->get_link() ?>">Обвинительное заключение / акт</a>
                (<a href="download.php?file=<?= $file->get_link() ?>">Скачать</a>)
              </span>
            </li>
          <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </form>
    
  <div class="fieldset">
    <div class="legenda">Добавить электронный образ:</div>
    <div class="block_form_with_progressbar">
      <div class="input_row">
        <form class="form_with_progressbar" id="upload_doc" file_required="true">
          <?= file_input('upload_file') ?>
        </form>
      </div>
      <div class="response_place">
        <?php if (!empty($_SESSION['indictment']['files'])) echo get_added_files_list_with_info($_SESSION['indictment']['files']); ?>
      </div>
    </div>
  </div>
  
  <div class="registration_block">
    <div class="add_button_box" form="indictment">
      <div class="button_block"><span class="button_name">Сохранить</span></div>
    </div>
    <div class="response_place"></div>
  </div>
  
  <div id="file_preview"></div>
</div>

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
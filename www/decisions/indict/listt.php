
<?php
$need_auth = 1;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$page_title = 'Регистрация электронных копий постановлений об отказе в возбуждении уголовного дела';

$breadcrumbs = array(
  'Процессуальные документы, вынесенные по результатам расследования УД' => 'index.php',
  '' => ''
);

$mthList = array(1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
$limits = array();


if (!empty($_GET["year"])) {
  $year = (strlen($_GET["year"]) == 4) ? (integer)$_GET["year"] : date('Y');
} else {
  $year = date('Y');
}
$limits[] = $year;

$month = null;
if (isset($_GET['month']))
  $month = (integer)$_GET['month'];
if (($month >= 1 and $month <= 12) === false) {
  $month = null;
} else {
  $limits[] = $mthList[$month];
}


require(KERNEL.'connect.php');

if (!empty($_GET['ovd']))
  $ovd = (integer)$_GET['ovd'];
if (!empty($ovd)) {
  $ovd = getOvdName($ovd);
  $db->query('SET @ovd = 1');
} else {
  $ovd = null;
}
$type = (isset($_GET['type'])) ? $_GET['type'] : null;
$where = null;

switch ($type) {
  case 'added':
    $limits[] = 'Введено';
    //$where[] = 'f1.`p25_f11` IN (1,31,61,21,52,4,3,7,51,8,9,14)';
    if (!empty($month)) {
      $where[] = 'f.`create_date` BETWEEN STR_TO_DATE("'.$year.'-'.$month.'-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-'.$month.'-31", "%Y-%m-%d")';
    } else {
      $where[] = 'f.`create_date` BETWEEN STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-12-31", "%Y-%m-%d")';
    }
    if (!empty($ovd))
      $where[] = 'f1.`d01_f10` = '.$ovd['id'];
    
    $query = '
      SELECT
        f1.`id`,
        CONCAT(f1.`vd3o_f10`, " № ", f1.`d3n_f10`, " от ", DATE_FORMAT(f1.`d11_f10`, "%d.%m.%Y"),
               IF(@ovd = 1, "", CONCAT(", ", f1.`vd01_f10`))) as `str`,
        GROUP_CONCAT(
          DISTINCT f1.`vp25_f11`, " ", DATE_FORMAT(f1.`d25_f11`, "%d.%m.%Y")
          SEPARATOR ","
        ) as `decision`,
        GROUP_CONCAT(
          DISTINCT f1.`vp251_f11`, " ", DATE_FORMAT(f1.`d251_f11`, "%d.%m.%Y")
          SEPARATOR ","
        ) as `jud_decision`,
        GROUP_CONCAT(
          DISTINCT
          f1.`vd10_f10`,
          IF(f1.`fam_svr` IS NOT NULL, CONCAT(" ", SUBSTRING(UPPER(f1.`fam_svr`), 1, 1), SUBSTRING(LOWER(f1.`fam_svr`), 2, LENGTH(f1.`fam_svr`) - 1)), "")
          SEPARATOR "<br />"
        ) as `emp`,
        GROUP_CONCAT(
          DISTINCT
          CASE
            WHEN f1.`kusp` IS NOT NULL THEN 
              CONCAT(\'<a href="/wonc/ek.php?id=\', f1.`kusp`, \'" target="_blank">\', CONCAT("КУСП №", f1.`n05_f10`, " от ", DATE_FORMAT(f1.`d05_f10`, "%d.%m.%Y")), "</a>")
            ELSE
              CONCAT("КУСП №", f1.`n05_f10`, " от ", DATE_FORMAT(f1.`d05_f10`, "%d.%m.%Y"))
          END
          ORDER BY f1.`d05_f10` DESC, f1.`n05_f10` ASC
          SEPARATOR "<br />"
        ) as `kusp`,
        MAX(f1.`d04_f10`) as `ep`
      FROM
        `ic_f1_f11` as f1
      JOIN
        `ic_f1_files` as ff ON
          ff.`f1` = f1.`id` AND
          ff.`deleted` = 0
        JOIN
          `l_files` as f ON
            f.`id` = ff.`file`
      WHERE
        '.implode(' AND ', $where).'
      GROUP BY
        f1.`n_gasps` -- f1.`d3n_f10`, f1.`d3g_f10`, f1.`d01_f10`
    ';
    break;

  case 'debt':
    $org = 1;
  case 'debtsk':
    if (empty($org)) {
      $org = 2;
      $limits[] = 'Следственный комитет';
    }
    $limits[] = 'Не введено';
    
    if ($org == 1) {$wh = '!=1'; }
     else {$wh = '= 1';} ;
    
    if (!empty($ovd))
      $where[] = 'd.`ovd` = '.$ovd['id'];
    
    /*$query = '
      SELECT
        f1.`id`,
        CONCAT(f1.`vd3o_f10`, " № ", f1.`d3n_f10`, " от ", DATE_FORMAT(f1.`d11_f10`, "%d.%m.%Y"),
               IF(@ovd = 1, "", CONCAT(", ", f1.`vd01_f10`))) as `str`,
        GROUP_CONCAT(
          DISTINCT f1.`vp25_f11`, " ", DATE_FORMAT(f1.`d25_f11`, "%d.%m.%Y")
          SEPARATOR ","
        ) as `decision`,
        GROUP_CONCAT(
          DISTINCT f1.`vp251_f11`, " ", DATE_FORMAT(f1.`d251_f11`, "%d.%m.%Y")
          SEPARATOR ","
        ) as `jud_decision`,
        GROUP_CONCAT(
          DISTINCT
          f1.`vd10_f10`,
          IF(f1.`fam_svr` IS NOT NULL, CONCAT(" ", SUBSTRING(UPPER(f1.`fam_svr`), 1, 1), SUBSTRING(LOWER(f1.`fam_svr`), 2, LENGTH(f1.`fam_svr`) - 1)), "")
          SEPARATOR "<br />"
        ) as `emp`,
        GROUP_CONCAT(
          DISTINCT
          CASE
            WHEN f1.`kusp` IS NOT NULL THEN 
              CONCAT(\'<a href="/wonc/ek.php?id=\', f1.`kusp`, \'" target="_blank">\', CONCAT("КУСП №", f1.`n05_f10`, " от ", DATE_FORMAT(f1.`d05_f10`, "%d.%m.%Y")), "</a>")
            ELSE
              CONCAT("КУСП №", f1.`n05_f10`, " от ", DATE_FORMAT(f1.`d05_f10`, "%d.%m.%Y"))
          END
          ORDER BY f1.`d05_f10` DESC, f1.`n05_f10` ASC
          SEPARATOR "<br />"
        ) as `kusp`,
        MAX(f1.`d04_f10`) as `ep`
      FROM
        `ic_debts` as d
      LEFT JOIN
        `ic_f1_files` as ff ON
          ff.`f1` = d.f1
      LEFT JOIN
        `ic_f1_f11` as f1 ON
          f1.`id` = d.`f1`
      WHERE
        '.implode(' AND ', $where).'
      GROUP BY
        d.`number`, d.`year`
    ';*/
    if(isset($_GET['ovd'])) $o= 'and f1.d01_load ='.$ovd['id']; else $o='';
    $query = 
        'SELECT
        f1.id_f1 as id,
		f1.vd01_load as ovd,
		f1.vd33 as lin,
        CONCAT(f1.N_GASPS, ", ст. УК ", f1.uk) as str,
        CONCAT(date_format(f1.d25_f11, "%d.%m.%Y"), " - ", f1.vp25_f11) as decision,        
        f1.fam_sor as `emp`,
        CONCAT(
				          CASE
				            WHEN f_.kusp IS NOT NULL THEN 
				              CONCAT(\'<a href="/wonc/ek.php?id=\', f_.`kusp`, \'" target="_blank">\', CONCAT("КУСП №", f_.`n05_f10`, " от ", DATE_FORMAT(f_.`d05_f10`, "%d.%m.%Y")), "</a>")
				            ELSE
				              CONCAT("КУСП №", f_.`n05_f10`, " от ", DATE_FORMAT(f_.`d05_f10`, "%d.%m.%Y"))
				          END
				        ) as `kusp`
        FROM 
		    `ic_for_load` as f1  LEFT JOIN
                 `ic_f1_files` as f ON
                  f.`f1` = f1.id_f1  AND
                  f.`deleted` = 0 
               left join ic_f1_f11 as f_ on f_.rowid = f1.rowid 
        WHERE f.`id` IS NULL and f1.del = 0 '.$o.' and f1.d33 '.$wh.'
        
        order by f1.fam_sor, f_.d11_f10, f1.d25_f11, f1.d33';
        break;

  default:
    if (!empty($month)) {
      $where[] = 'f1.`d11_f10` BETWEEN STR_TO_DATE("'.$year.'-'.$month.'-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-'.$month.'-31", "%Y-%m-%d")';
    } else {
      $where[] = 'f1.`d11_f10` BETWEEN STR_TO_DATE("'.$year.'-01-01", "%Y-%m-%d") AND STR_TO_DATE("'.$year.'-12-31", "%Y-%m-%d")';
    }
    if (!empty($ovd))
      $where[] = 'f1.`d01_f10` = '.$ovd['id'];
    $query = '
      SELECT
        f1.`id`,
        CONCAT(f1.`vd3o_f10`, " № ", f1.`d3n_f10`, " от ", DATE_FORMAT(f1.`d11_f10`, "%d.%m.%Y"),
               IF(@ovd = 1, "", CONCAT(", ", f1.`vd01_f10`))) as `str`,
        GROUP_CONCAT(
          DISTINCT f1.`vp25_f11`, " ", DATE_FORMAT(f1.`d25_f11`, "%d.%m.%Y")
          SEPARATOR ","
        ) as `decision`,
        GROUP_CONCAT(
          DISTINCT f1.`vp251_f11`, " ", DATE_FORMAT(f1.`d251_f11`, "%d.%m.%Y")
          SEPARATOR ","
        ) as `jud_decision`,
        GROUP_CONCAT(
          DISTINCT
          f1.`vd10_f10`,
          IF(f1.`fam_svr` IS NOT NULL, CONCAT(" ", SUBSTRING(UPPER(f1.`fam_svr`), 1, 1), SUBSTRING(LOWER(f1.`fam_svr`), 2, LENGTH(f1.`fam_svr`) - 1)), "")
          SEPARATOR "<br />"
        ) as `emp`,
        GROUP_CONCAT(
          DISTINCT
          CASE
            WHEN f1.`kusp` IS NOT NULL THEN 
              CONCAT(\'<a href="/wonc/ek.php?id=\', f1.`kusp`, \'" target="_blank">\', CONCAT("КУСП №", f1.`n05_f10`, " от ", DATE_FORMAT(f1.`d05_f10`, "%d.%m.%Y")), "</a>")
            ELSE
              CONCAT("КУСП №", f1.`n05_f10`, " от ", DATE_FORMAT(f1.`d05_f10`, "%d.%m.%Y"))
          END
          ORDER BY f1.`d05_f10` DESC, f1.`n05_f10` ASC
          SEPARATOR "<br />"
        ) as `kusp`,
        MAX(f1.`d04_f10`) as `ep`
      FROM
        `ic_f1_f11` as f1
      WHERE
        '.implode(' AND ', $where).'
      GROUP BY
        f1.`d3n_f10`, f1.`d3g_f10`, f1.`d01_f10`
      ORDER BY
        f1.`d06_f10` DESC
    ';
 
    break;
    
}
$result = $db->query('SET group_concat_max_len = 10000');
$result = $db->query($query);
$n = 1;
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');

?>
<div class="header_row"><?= $ovd['ovd'] ?> <?php if (!empty($limits)) echo '('.implode(', ', $limits).')' ?></div>


<div class="result_headers">
  <div class="result_cell number">№<br />п/п</div>
  <div class="result_cell number">ОВД</div> 
  <div class="result_cell number">Лин</div>   
  <div class="result_cell text">Рег.данные</div>
  <div class="result_cell shorttext">Служба<br />сотрудник</div>
  <div class="result_cell text">Решение</div>
  <div class="result_cell text">КУСП</div>
</div>

<?php if ($result->num_rows > 0) : ?>
  <?php while ($row = $result->fetch_object()) : ?>
    <div class="result_row">
      <div class="result_cell number"><?= $n++ ?>.&</div>
	  <div class="result_cell number"><?= $row->ovd ?>.&</div>
	  <div class="result_cell number"><?= $row->lin ?>.&</div>
      <div class="result_cell text"><a href="case.php?id=<?= $row->id ?>"><?= $row->str ?></a>&</div>
      <div class="result_cell shorttext"><?= $row->emp ?>&</div>
      <div class="result_cell text"><?= $row->decision ?><?php if (!empty($row->jud_decision)) echo '<br />Судебное решение: '.$row->jud_decision ?>&</div>
      <div class="result_cell text"><?= $row->kusp ?></div>
    </div>
  <?php endwhile; ?>
<?php else : ?>
  <div class="nothing_show">Ничего не найдено</div>
<?php endif; ?>

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
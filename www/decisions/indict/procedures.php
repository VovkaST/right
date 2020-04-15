<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$jsonERR = array();
$jsonData = true;
$json = $jsonMSG = $updates = '';
$active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;

require('require.php');

function simple_error($str = null) {
  return '<div class="handling_result handling_errors">Ошибка'.(($str) ? ' (#'.$str.')' : '').'!</div>';
}

// -------- загрузка файла -------- //
if (!empty($_FILES['upload_file'])) {
  try {
   
    if ($_FILES['upload_file']['error'] != 0)
      throw new Exception('Загружаемый файл имеет нулевой размер');

    if (empty($_POST['item_id']))
      throw new Exception('Не уникализированный запрос');
      
    $file = $_FILES['upload_file'];
    
    if (!empty($_POST['browser']) and $_POST['browser'] == 'shitty') {
      $json['.response_place .item#'.$_POST['item_id'].' .file_info_block .name'] = '<b>Файл:</b> '.$file['name'];
      $json['.response_place .item#'.$_POST['item_id'].' .file_info_block .size'] = '<b>Размер:</b> '.number_format($file['size'], 0, ',', '&thinsp;').' байт';
      $json['.response_place .item#'.$_POST['item_id'].' .progress_block'] = '<div class="progress_bar"><div class="progress" style="width: 100%;"></div><span class="proc">100%</span></div>';
    }
    
    if (!empty($_SESSION['indictment']['files'])) {
      foreach ($_SESSION['indictment']['files'] as $k => $f) {
        if ($file['name'] == $f['basename']) {
          if (empty($_POST['browser']) or $_POST['browser'] != 'shitty')
            $json[] = simple_error();
          throw new Exception('Такой файл уже загружен.');
        }
      }
    }
    
    if (empty($_POST['browser']) or $_POST['browser'] == 'shitty') {
      $jsonData = false;
    }
    
    if (is_uploaded_file($file['tmp_name'])) {
      if (empty($_SESSION['dir_session'])) {
        $_SESSION['dir_session'] = session_save_path()."_tmp_".session_id().'\\'; // временный каталог сессии
      }
      if (!is_dir($_SESSION['dir_session'])) {
        mkdir($_SESSION['dir_session']); // создаем его
      }
      
      if (copy($file['tmp_name'], $_SESSION['dir_session'].mb_convert_encoding($file['name'], 'Windows-1251', 'UTF-8'))) {
        if (!empty($_POST['browser']) and $_POST['browser'] == 'shitty') {
          $json['.response_place .item#'.$_POST['item_id'].' .status_block'] = '<i>Загружен<br />В обработке...</i>';
        } else {
          $json[] = '<i>Загружен<br />В обработке...</i><span class="delete_relation" id="'.$_POST['item_id'].'" group="files">&times;</span>';
        }
        $_SESSION['indictment']['files'][$_POST['item_id']] = pathinfo($_SESSION['dir_session'].$file['name']);
        $_SESSION['indictment']['files'][$_POST['item_id']]['path'] = $_SESSION['dir_session'].$file['name'];
        $_SESSION['indictment']['files'][$_POST['item_id']]['size'] = filesize($_SESSION['dir_session'].mb_convert_encoding($file['name'], 'Windows-1251', 'UTF-8'));
        
      } else {
        $json[] = simple_error('Не удалось найти файл на сервере');
        throw new Exception($error->getMessage('Не удалось найти файл на сервере'));
      }
    } else {
      $json[] = simple_error('Недопустимый тип файла');
      throw new Exception($error->getMessage('Ошибка загрузки файла'));
    }

  } catch(Exception $exc) {
    if (!empty($_POST['browser']) and $_POST['browser'] == 'shitty') {
      $jsonData = false;
      $json['.response_place .item#'.$_POST['item_id'].' .status_block'] = simple_error();
    } else {
      $jsonData = true;
    }
    if ($exc->getMessage() != '')
      $jsonERR[] = $exc->getMessage();
  }
}
// ^^^^^^^^ загрузка файла ^^^^^^^^ //

try {

  if (!empty($_POST['method'])) {
    switch ($_POST['method']) {
      case 'fs': // first search - перичный поиск на index.php
        $pars = array();
        
        if (!empty($_POST['n']) or !empty($_POST['year']) or !empty($_POST['ngasps'])) {
          // ******** поиск по у/д ******** //
          
          if (!empty($_POST['year']) and is_numeric($_POST['year']))
            $pars[] = 'YEAR(f1.`d06_f10`) = '.$_POST['year'];
          
          if (!empty($_POST['n'])) {
            $n = preg_replace('/[^\d\.,]/', '', $_POST['n']);
            if (is_numeric($n)) {
              $pars[] = 'f1.`d3n_f10` = '.$n;
            } else {
              $arr = array();
              foreach (explode(',', $n) as $i => $v) {
                $arr[] = 'f1.`d3n_f10` = '.$v;
              }
              $pars[] = '('.implode(' OR ', $arr).')';
            }
          }
          
          if (!empty($_POST['ovd']) and is_numeric($_POST['ovd'])) {
            $pars[] = 'f1.`d01_f10` = '.$_POST['ovd'];
          }
          
          if (!empty($_POST['organ']) and is_numeric($_POST['organ'])) {
            $pars[] = 'f1.`d01o_f1` = '.$_POST['organ'];
          }
          
          if (!empty($_POST['ngasps']) and is_numeric($_POST['ngasps'])) {
            $pars[] = "f1.`N_GASPS` = '".trim($_POST['ngasps'])."'";
          }
          
          if (!empty($_POST['statya']) and !empty($_POST['chast']) and empty($_POST['punkt'])) {
            $pars[] = 'f1.`d13_f10` = \''.$_POST['statya'].' ч.'.$_POST['chast'].'\'';
          }
          elseif (!empty($_POST['statya']) and !empty($_POST['chast']) and !empty($_POST['punkt']))
            {$pars[] = 'f1.`d13_f10` = \''.$_POST['statya'].' ч.'.$_POST['chast'].'\''; 
              $pars[] = 'f1.`d13p_f10` like \'%'.$_POST['punkt'].'%\'';}
          else $pars[] = 'f1.`d13_f10` like \'%'.$_POST['statya'].'%\'';
          
          set_time_limit(0);
          require(KERNEL.'connect.php');
          $db->query('SET group_concat_max_len = 10000');
          
          $query = '
            SELECT SQL_NO_CACHE
              f1.`id`,
              CONCAT(ifnull(f1.`vd3o_f10`,""), " № ", f1.`d3n_f10`, " от ", ifnull(DATE_FORMAT(f1.`d11_f10`, "%d.%m.%Y"),""), " ", ifnull(f1.`vd01_f10`,""), ", ", f1.vd01o_f1) as `string`,
			  
              GROUP_CONCAT(
                DISTINCT f1.`vp25_f11`, " ", DATE_FORMAT(f1.`d25_f11`, "%d.%m.%Y")
                SEPARATOR ","
              ) as `decision`,
              GROUP_CONCAT(
                DISTINCT f1.`vp251_f11`, " ", DATE_FORMAT(f1.`d251_f11`, "%d.%m.%Y")
                SEPARATOR ","
              ) as `jud_decision`,
              CONCAT("ст.", f1.`d13_f10`, IF(f1.`d13p_f10` IS NOT NULL, CONCAT(" п.", f1.`d13p_f10`), ""), " УК РФ") as `qual`,
              f1.`kusp`,
              CONCAT("КУСП №", f1.`n05_f10`, " от ", DATE_FORMAT(f1.`d05_f10`, "%d.%m.%Y")) as `kusp_str`,
			  f1.N_GASPS,
			  concat(ifnull(f1.vd33_f11,"")," ",ifnull(f1.fam_svr,"")) as line
			             
            FROM
              `ic_f1_f11` as f1
            WHERE
              '.implode(' AND ', $pars).'
            GROUP BY
              f1.N_GASPS
            ORDER BY
              f1.`d06_f10` DESC, f1.`d04_f10` ASC
          ';
		  //f1.`d3n_f10`, f1.`d3g_f10`, f1.`d01_f10`
          $mcrt_b = microtime();
          $result = $db->query($query);
          $mcrt_e = microtime();
          $n = 1;
          if ($result->num_rows > 0) {
            $json['.search_result_block'] = '<dl>';
            while ($row = $result->fetch_object()) {
              $json['.search_result_block'] .= '
                <dt class="gray">'.$n++.'.</dt>
                <dd>
                  <div class="result_item"><span class="string"><a href=case.php?id='.$row->id.'>'.$row->string.'</a></span></div>
                  <div class="bottomTextBlock gray">
				    '.((!empty($row->N_GASPS)) ? '<span class="smallTextItem">N_GASPS: '.$row->N_GASPS.'</span>' : null).'
                    '.((!empty($row->qual)) ? '<span class="smallTextItem">Квалификация: '.$row->qual.'</span>' : null).'
                    '.(
                       (!empty($row->kusp_str)) 
                                      ? ((!empty($row->kusp))
                                                    ? '<a href="/wonc/ek.php?id='.$row->kusp.'" target="_blank">'.$row->kusp_str.'</a>' 
                                                    : $row->kusp_str).'<br />' 
                                      : null).'
                    '.((!empty($row->decision)) ? '<span class="smallTextItem">'.$row->decision.'</span>' : null).'
                    '.((!empty($row->jud_decision)) ? '<span class="smallTextItem">'.$row->jud_decision.'</span>' : null).'
					'.((!empty($row->line)) ? '<span class="smallTextItem">'.$row->line.'</span>' : null).'
					
                  </div>
                </dd>';
            }
            $json['.search_result_block'] .= '</dl>';
            $json['.result_count'] = 'Найдено '.$result->num_rows.' ('.number_format(abs($mcrt_b - $mcrt_e), 2).' сек.)';
            
          } else {
            $json['.search_result_block'] = '<div class="nothing_show">Ничего не найдено ('.number_format(abs($mcrt_b - $mcrt_e), 2).' сек.)</div>';
          }
          $result->close();
          
          // ^^^^^^^^ поиск по у/д ^^^^^^^^ //
        } elseif (empty($_POST['surname']) and empty($_POST['name']) and empty($_POST['fname']) and empty($_POST['borth'])) {
         
          $json['.search_result_block'] = '<div class="nothing_show">Пустой поисковый запрос. Укажите № дела либо год</div>';
        
        } else {
          
          // ******** поиск по лицу ******** //
          require(KERNEL.'connect.php');
          if (empty($_POST['forms'])) {
            $_POST['forms'] = array('f2' => 1, 'f5' => 1, 'f6' => 1, 'ref' => 1);
          }
          
          if (!empty($_POST['surname'])) {
            $sn = $db->real_escape_string(preg_replace('/[\*]+/', '%', trim($_POST['surname']), -1, $cnt));
            $sn = (($cnt > 0) ? ' LIKE ' : ' = ').'UPPER("'.$sn.'")';
          }
          
          if (!empty($_POST['name'])) {
            $n = $db->real_escape_string(preg_replace('/[\*]+/', '%', trim($_POST['name']), -1, $cnt));
            $n = (($cnt > 0) ? ' LIKE ' : ' = ').'UPPER("'.$n.'")';
          }
          
          if (!empty($_POST['fname'])) {
            $fn = $db->real_escape_string(preg_replace('/[\*]+/', '%', trim($_POST['fname']), -1, $cnt));
            $fn = (($cnt > 0) ? ' LIKE ' : ' = ').'UPPER("'.$fn.'")';
          }
          
          if (!empty($_POST['borth'])) {
            if (strlen($_POST['borth']) == 4 and is_numeric($_POST['borth'])) {
              $year = ' = '.$_POST['borth'];
            } elseif (strlen($_POST['borth']) == 10 and preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['borth'])) {
              $b = ' = "'.date('Y-m-d', strtotime($_POST['borth'])).'"';
            }
          }
          
          
          $queries = array();
          if (isset($_POST['forms']['f2'])) {
            $p = array();
            if (isset($sn))   $p[] = 'l.`surname`'.$sn;
            if (isset($n))    $p[] = 'l.`name`'.$n;
            if (isset($fn))   $p[] = 'l.`fath_name`'.$fn;
            if (isset($year)) $p[] = 'YEAR(l.`borth`)'.$year;
            if (isset($b))    $p[] = 'l.`borth`'.$b;
            
            $queries['f2'] = '
              SELECT
                "Ф2" as `form`,
                f2.`vd01o_f2` as `dep`,
                f2.`vd01_f2` as `ovd`, f2.`d3n_f2` as `number`, YEAR(f2.`d06_f2`) as `year`,
                CONCAT(
                  IFNULL(CONCAT("<a target=\"_blank\" href=\"case.php?id=", f1.`id`, "\">"), ""),
                  f2.`vd3o_f2`, " №", f2.`d3n_f2`, "/", YEAR(f2.`d031_f2`),
                  IF(f1.`id` IS NOT NULL, "</a>", "")
                ) as `link`,
                TRIM(CONCAT(l.`surname`, " ", l.`name`, " ", IFNULL(l.`fath_name`, ""), " ", IFNULL(DATE_FORMAT(l.`borth`, "%d.%m.%Y"), ""))) as `str`,
                1 as `ord`
              FROM
                `o_lico` as l USE INDEX (`composite_key`)
              JOIN
                `ic_f2` as f2 ON
                  f2.`face` = l.`id`
                LEFT JOIN
                  `ic_f1_f11` as f1 ON f1.N_GASPS = F2.N_GASPS  AND
                    f1.`d04_f10` = 1
              WHERE
                '.implode(' AND ', $p).'
            ';
          }
          
          if (isset($_POST['forms']['f5'])) {
            $p = array();
            if (isset($sn)) $p[] = 'f5.`d081fam_pot_naz_ooo_f5`'.$sn;
            if (isset($n))  $p[] = 'f5.`d081imya_f5`'.$n;
            if (isset($fn)) $p[] = 'f5.`d081otch_f5`'.$fn;
            
            $queries['f5_1'] = '
              SELECT
                "Ф5" as `form`,
                f5.`vd01o_f5` as `dep`,
                f5.`vd01_f5` as `ovd`, f5.`d03n_f5` as `number`, YEAR(f5.`d032_f5`) as `year`,
                CONCAT(
                  IFNULL(CONCAT("<a target=\"_blank\" href=\"case.php?id=", f1.`id`, "\">"), ""),
                  f5.`vd03o_f5`, " №", f5.`d03n_f5`, "/", YEAR(f5.`d032_f5`),
                  IF(f1.`id` IS NOT NULL, "</a>", "")
                ) as `link`,
                TRIM(CONCAT(f5.`d081fam_pot_naz_ooo_f5`, " ", IFNULL(f5.`d081imya_f5`, ""), " ", IFNULL(f5.`d081otch_f5`, ""))) as `str`,
                1 as `ord`
              FROM
                `ic_f5` as f5
              LEFT JOIN
                `ic_f1_f11` as f1 ON f1.N_GASPS = F5.N_GASPS  AND
                  f1.`d04_f10` = 1
              WHERE
                '.implode(' AND ', $p).'
            ';
            
            /*$_sn = preg_replace('/( = )|( LIKE )/', '', $sn, 1);
            $queries['f5_2'] = '
              SELECT
                "Ф5" as `form`,
                f5.`vd01o_f5` as `dep`,
                f5.`vd01_f5` as `ovd`, f5.`d03n_f5` as `number`, YEAR(f5.`d032_f5`) as `year`,
                CONCAT(
                  IFNULL(CONCAT("<a target=\"_blank\" href=\"case.php?id=", f1.`id`, "\">"), ""),
                  f5.`vd03o_f5`, " №", f5.`d03n_f5`, "/", YEAR(f5.`d032_f5`),
                  IF(f1.`id` IS NOT NULL, "</a>", "")
                ) as `link`,
                TRIM(CONCAT(f5.`d081fam_pot_naz_ooo_f5`, " ", IFNULL(f5.`d081imya_f5`, ""), " ", IFNULL(f5.`d081otch_f5`, ""))) as `str`,
                99 as `ord`
              FROM
                `ic_f5` as f5
              LEFT JOIN
                `ic_f1_f11` as f1 ON f1.N_GASPS = F5.N_GASPS  AND
                  f1.`d04_f10` = 1
              WHERE
                f5.`d081fam_pot_naz_ooo_f5` LIKE CONCAT('.$_sn.', "%")
            ';*/
          }
          
          if (isset($_POST['forms']['f6'])) {
            $p = array();
            if (isset($sn))   $p[] = 'l.`surname`'.$sn;
            if (isset($n))    $p[] = 'l.`name`'.$n;
            if (isset($fn))   $p[] = 'l.`fath_name`'.$fn;
            if (isset($year)) $p[] = 'YEAR(l.`borth`)'.$year;
            if (isset($b))    $p[] = 'l.`borth`'.$b;
            
            $queries['f6'] = '
              SELECT
                "Ф6" as `form`,
                f6.`vd01o_f6` as `dep`,
                f6.`vd01_f6` as `ovd`, f6.`d03n_f6` as `number`, f6.`d03g_f6` as `year`,
                CONCAT(
                  IFNULL(CONCAT("<a target=\"_blank\" href=\"case.php?id=", f1.`id`, "\">"), ""),
                  "Уголовное дело №", f6.`d03n_f6`, "/", f6.`d03g_f6`,
                  IF(f1.`id` IS NOT NULL, "</a>", "")
                ) as `link`,
                TRIM(CONCAT(l.`surname`, " ", l.`name`, " ", IFNULL(l.`fath_name`, ""), " ", IFNULL(DATE_FORMAT(l.`borth`, "%d.%m.%Y"), ""))) as `str`,
                1 as `ord`
              FROM
                `o_lico` as l USE INDEX (`composite_key`)
              JOIN
                `ic_f6` as f6 ON
                  f6.`face` = l.`id`
                LEFT JOIN 
                  `ic_f1_f11` as f1 ON f1.N_GASPS = F6.N_GASPS  AND
                    f1.`d04_f10` = 1
              WHERE
                '.implode(' AND ', $p).'
            ';
          }
          
          if (isset($_POST['forms']['ref'])) {
            $p = array();
            if (isset($sn))   $p[] = 'l.`surname`'.$sn;
            if (isset($n))    $p[] = 'l.`name`'.$n;
            if (isset($fn))   $p[] = 'l.`fath_name`'.$fn;
            if (isset($year)) $p[] = 'YEAR(l.`borth`)'.$year;
            if (isset($b))    $p[] = 'l.`borth`'.$b;
            
            $queries['ref'] = '
              SELECT
                NULL as `form`,
                "Орган внутренних дел" as `dep`,
                ovd.`ovd` as `ovd`, NULL as `number`, YEAR(d.`date`) as `year`,
                CONCAT(
                  "<a target=\"_blank\" href=\"/decisions/search.php?reg=", d.`reg`, "&ovd=", d.`ovd`, "&date_f=", DATE_FORMAT(d.`date`, "%d.%m.%Y"), "&obj=1\">",
                  "Отказной материал ст.10 от ", DATE_FORMAT(d.`date`, "%d.%m.%Y"),
                  "</a>"
                ) as `link`,
                TRIM(CONCAT(l.`surname`, " ", l.`name`, " ", IFNULL(l.`fath_name`, ""), " ", IFNULL(DATE_FORMAT(l.`borth`, "%d.%m.%Y"), ""))) as `str`,
                1 as `ord`
              FROM
                `o_lico` as l
              JOIN
                `l_dec_lico` as dl ON
                  dl.`face` = l.`id` AND
                  dl.`deleted` = 0
                JOIN
                  `l_decisions` as d ON
                    d.`id` = dl.`decision` AND
                    d.`deleted` = 0
                  JOIN
                    `spr_ovd` as ovd ON
                      ovd.`id_ovd` = d.`ovd`
              WHERE
                '.implode(' AND ', $p).'
            ';
          }
          
          $query = implode(' UNION ', $queries).' ORDER BY `ord`, `year` DESC, `number` DESC';
          
          //echo $query;
          
          //print_r($db->query($query));
          //die();
          $mcrt_b = microtime();
          if (!$result = $db->query($query))
            throw new Exception($db->error);
          $mcrt_e = microtime();
          $n = 1;
          if ($result->num_rows > 0) {
            $json['.search_result_block'] = '<dl>';
            while ($row = $result->fetch_object()) {
              $json['.search_result_block'] .= '
                <dt class="gray">'.$n++.'.</dt>
                <dd>
                  <div class="result_item"><span class="string">'.$row->link.'<span></div>
                  <div class="bottomTextBlock gray">
                    '.((!empty($row->form)) ? '<span class="smallTextItem">'.$row->form.'</span>' : null).'
                    <span class="smallTextItem">'.$row->dep.'</span>
                    <span class="smallTextItem">'.$row->ovd.'</span>
                    <span class="smallTextItem">'.$row->str.'</span>
                  </div>
                </dd>';
            }
            $json['.search_result_block'] .= '</dl>';
            $json['.result_count'] = 'Найдено '.$result->num_rows.' ('.number_format(abs($mcrt_b - $mcrt_e), 2).' сек.)';
            
          } else {
            $json['.search_result_block'] = '<div class="nothing_show">Ничего не найдено ('.number_format(abs($mcrt_b - $mcrt_e), 2).' сек.)</div>';
          }
          $result->close();
          
          // ^^^^^^^^ поиск по лицу ^^^^^^^^ //
        }
        
        
        break;
        
      case 'registration':
        if (empty($_SESSION['indictment']['current']))
          throw new Exception('Неизвестный объект.');
        
        require(KERNEL.'connect.php');
        // проверяем были ли ранее загружены файлы
        $query = '
          SELECT
            COUNT(DISTINCT rel.`file`) as `cnt`
          FROM
            `ic_f1_files` as rel
          WHERE
            rel.`deleted` = 0 AND
            rel.`f1` = '.$_SESSION['indictment']['current'];
        $result = $db->query($query);
        $row = $result->fetch_object();
        if ($row->cnt == 0 and empty($_SESSION['indictment']['files'])) {
          throw new Exception('Не загружено ни одного файла.');
        }
        
        if (!empty($_POST['united']))
          $_SESSION['indictment'] = array_merge($_SESSION['indictment'], $_POST['united']);
        
        
        
        $cc = array();  // id новых Ф1
        $cc[0] = $_SESSION['indictment']['current'];
        if (!empty($_SESSION['indictment']['f3'])) $cc = array_merge($cc, $_SESSION['indictment']['f3']);
        if (!empty($_SESSION['indictment']['possible'])) $cc = array_merge($cc, $_SESSION['indictment']['possible']);
        
        $query = '
          SELECT SQL_NO_CACHE
            DISTINCT
            f1ep.`id`, f1ep.`d11_f10` as `date`
          FROM
            `ic_f1_f11` as f1
          JOIN
            `ic_f1_f11` as f1ep ON f1.N_GASPS = f1ep.N_GASPS 
          WHERE
            f1.`id` IN ('.implode(', ', $cc).')
        ';
        $result = $db->query($query);
        if ($result->num_rows == 0)
          throw new Exception('Что-то пошло не так...');
        
        $rel = array();
        while ($row = $result->fetch_object()) {
          $rel[$row->id]['f1'] = $row->id;
          $rel[$row->id]['date'] = $row->date;
        }
        $result->close();
        
        $db->autocommit(false);
        
        // ******** сохраняем файлы ******** //
        $ff = array();
        
        if (empty($_SESSION['indictment']['files'])) {  // редактирование у/д с ранее загруженным файлом без добавления нового
          $query = '
            SELECT
              f.`file`
            FROM
              `ic_f1_files` as f
            WHERE
              f.`deleted` = 0 AND
              f.`f1` = '.$_SESSION['indictment']['current'];
          $result = $db->query($query);
          while ($row = $result->fetch_object()) {
            $ff[] = $row->file;
          }
          
        } else {                // сохранение нового файла
          foreach ($_SESSION['indictment']['files'] as $id => $file) {
            if (!is_file(mb_convert_encoding($file['path'], 'Windows-1251', 'UTF-8'))) {
              throw new Exception('Файл "'.$file['basename'].'" недоступен на сервере! Повторите загрузку.');
            }
            
            $currFile = new ElFile();
            $currFile->save(6);
            
            $ff[] = $currFile->get_id();

            $tmp_dir = 'f:/Site_storage/_tmp/Indictments/';
            $fileNameParts[0] = 'Indictment';
            $fileNameParts[1] = date('Y', strtotime($rel[$_SESSION['indictment']['current']]['date']));
            $fileNameParts[2] = date('m', strtotime($rel[$_SESSION['indictment']['current']]['date']));
            $fileNameParts[3] = $_SESSION['indictment']['current'];
            $fileNameParts[4] = $currFile->get_id();
            $new_name = implode('_', $fileNameParts).'.'.$file['extension'];
            if (!$currFile->move($_SESSION['dir_session'].$file['basename'], $tmp_dir, $new_name)) {
              $db->rollback();
              if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
                throw new Exception('<b>Сохранение файла (перемещение)</b>');
              } else {
                throw new Exception('<center><b>Ошибка сохранения №3!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
              }
            }

            unset($_SESSION['indictment']['files'][$id]);
          }
        }
        unset($_SESSION['indictment']['files']);
        // ^^^^^^^^ сохраняем файлы ^^^^^^^^ //
        
        
        
        // ******** id с Ф1 на удаление и добавление связи ******** //
        $old_f1 = $on_del = $on_add = array();
        $query = '
          SELECT
            un.`f1`
          FROM
            `ic_f1_files` as rel
          JOIN
            `ic_f1_files` as un ON
              un.`file` = rel.`file` AND
              un.`deleted` = 0
          WHERE
            rel.`deleted` = 0 AND
            rel.`f1` = '.$_SESSION['indictment']['current'];
        $result = $db->query($query);
        while ($row = $result->fetch_object()) {
          $old_f1[] = $row->f1;
        }
        $on_del = array_diff($old_f1, $cc);
        $on_add = array_diff($cc, $old_f1);
        // ^^^^^^^^ id с Ф1 на удаление и добавление связи ^^^^^^^^ //
        
        
        
        // ******** разрываем связи Ф1 с файлами ******** //
        if (count($on_del) > 0) {
          $query = '
            UPDATE 
              `ic_f1_files` 
            SET 
              `deleted` = 1
            WHERE 
              `file` IN ('.implode(', ', $ff).') AND 
              `f1` IN ('.implode(', ', $on_del).')
          ';
          $db->query($query);
          if (!$db->query($query)) {
            $db->rollback();
            if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
              throw new Exception('<b>Сохранение файла (обновление ссылки) ('.$db->errno.')</b>: '.$db->error.' .Query string: '.$query);
            } else {
              throw new Exception('<center><b>Ошибка сохранения №4!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
            }
          }
        }
        // ^^^^^^^^ разрываем связи Ф1 с файлами ^^^^^^^^ //
        
        
        // ******** сохраняем связи Ф1 с файлами ******** //        
        foreach ($ff as $file) {
          
          $rows = [];
        
          foreach ($rel as $n => $data) {
              $rows[] = '('.$data['f1'].', '.$file.')';
          }
          $query1 = 'INSERT INTO 
                     `ic_f1_files`(`f1`, `file`)
                    VALUES 
                      '.implode(', ', $rows);
          //$db->query($query1);
              
          if (!$db->query($query1)) {
			  
			
              $db->rollback();
              if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
                  throw new Exception('<b>Сохранение файла (обновление ссылки) ('.$db->errno.')</b>: '.$db->error.' .Query string: '.$query);
              } else {
                  throw new Exception('<center><b>Ошибка сохранения №4!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
              }
          }
          
          /*
          foreach ($rel as $n => $data) {
            $query = 'INSERT INTO 
                       `ic_f1_files`(`f1`, `file`)
                      VALUES 
                        ('.$data['f1'].', '.$file.')';
            $db->query($query);
                
            if (!$db->query($query)) {
              $db->rollback();
              if (isset($_SESSION['user']['admin']) and $_SESSION['user']['admin'] == 1) {
                throw new Exception('<b>Сохранение файла (обновление ссылки) ('.$db->errno.')</b>: '.$db->error.' .Query string: '.$query);
              } else {
                throw new Exception('<center><b>Ошибка сохранения №4!</b></center><br />Обратитесь к администратору по телефонам, расположенным внизу страницы или в разделе "Контакты".');
              }
            }
          }
          */
        }
        // ^^^^^^^^ сохраняем связи Ф1 с файлами ^^^^^^^^ //
        $db->commit();
        // die();
        $json['.registration_block .response_place'] = '<div class="opacity_back" href="index.php"></div><div class="info_box">
          <div class="block_header">Обвинительное заключение / акт</div>
          <div class="message">Успешно сохранено!</div>
          <div class="popup_window_button_block">
            <div class="add_button_box" href="case.php?id='.$_SESSION['indictment']['current'].'">
              <div class="button_block"><span class="button_name">Ok</span></div>
            </div>
          </div>
          </div>';
        unset($_SESSION['indictment']);
        break;
        
      case 'file_preview':
        $json['#file_preview'] = null;
        if (empty($_POST['file']))
          throw new Exception('Wrong parameters.');
        
        $file = new ElFile($_POST['file']);
        $json['#file_preview'] = '
          <div class="opacity_back"></div>
          <div class="info_box">
            <div class="block_header">Предпросмотр (неформатированный текст):</div>
            <div class="message"><pre>'.$file->get_content().'</pre></div>
            <div class="popup_window_button_block">
              <div class="add_button_box">
                <div class="button_block"><span class="button_name">Закрыть</span></div>
              </div>
            </div>
          </div>';
        break;
    }
  }
  
  if (isset($_POST['del_relation'])) {
    if (empty($_POST['group']))
      throw new Exception('Ошибка параметров');
    
    $file = mb_convert_encoding($_SESSION['indictment']['files'][$_POST['del_relation']]['basename'], 'Windows-1251', 'UTF-8');
    if (is_file($_SESSION['dir_session'].$file) and !unlink($_SESSION['dir_session'].$file)) {
      throw new Exception('Ошибка удаления файла.');
    }
    unset($_SESSION['indictment']['files'][$_POST['del_relation']]);
    if (empty($_SESSION['indictment']['files']))
      unset($_SESSION['indictment']['files']);
    $json['.response_place'] = (!empty($_SESSION['indictment']['files'])) ? get_added_files_list_with_info($_SESSION['indictment']['files']) : null;
  
  }

} catch(Exception $exc) {
  if ($exc->getMessage() != '')
    $jsonERR[] = $exc->getMessage();
}


// -------- вывод данных -------- //
$res = array_diff($jsonERR, array()); // убираем пустые строки массива ошибок

if (count($res) > 0)
  $resp['error'] = implode(', ', $jsonERR);
  
 
if (!empty($json)) $resp['html'] = $json;
if (!empty($jsonMSG)) $resp['msg'] = implode(', ', $jsonMSG);
if (!empty($group_result)) $resp['group_result'] = $group_result;
if (!empty($updates)) $resp['updates'] = $updates;

if ($jsonData) {  // если есть данные на JSON
  if (!empty($resp)) echo json_encode($resp);
} else {
  if (!empty($resp))
    echo '<script type="text/javascript">window.parent.json_response_handling('.json_encode($resp).')</script>';
  if (!empty($add_function)) echo $add_function;
}
if (!empty($add_function_req)) echo $add_function_req;

// ^^^^^^^^ вывод данных ^^^^^^^^ //
?>
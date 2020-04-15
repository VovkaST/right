<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

$jsonERR = array();
$jsonData = true;
$json = $jsonMSG = $updates = '';
require_once('require.php');

/*
$file = fopen('post.log', 'w');
foreach($_POST as $k => $v) {
  fwrite($file, '$_POST["'.$k.'"] = '.$v."\n");
}
if (!empty($_FILES['upload_file'])) {
  foreach($_FILES['upload_file'] as $k => $v) {
    fwrite($file, '$_FILES["upload_file"]["'.$k.'"] = '.$v."\n");
  }
}
fclose($file);
*/

// ******** Объявляем количество записей на страницу ******** //
if (empty($_REQUEST['limit']) or !is_numeric($_REQUEST['limit'])) {
  define('RECORDS', 30);
} else {
  define('RECORDS', $_REQUEST['limit']);
}
// ******** Объявляем количество записей на страницу ******** //

class ScriptError {
  private $errors_list = array(
      1 => 'Ошибка метода',
      2 => 'Неизвестный метод',
      3 => 'Недопустимый тип файла',
      4 => 'Не удалось найти файл на сервере',
      5 => 'Ошибка загрузки файла',
      6 => 'Загружаемый файл имеет нулевой размер',
      7 => 'Не уникализированный запрос',
      8 => 'Вводимая дата не может быть больше текущей',
      9 => 'Дату необходимо вводить в формате "01.01.2000"',
      10 => 'Вводимое значение должно быть числовым',
      11 => 'Заполнены не все обязательные поля',
      12 => 'Ошибка удаления файла',
      13 => 'Недостаточно параметров'
    );
  public function getMessage($id) {
    return 'Ошибка (#'.$id.'): '.$this->errors_list[$id].'!';
  }
  
  public function getErrorsList() {
    foreach ($this->errors_list as $k => $v) {
      echo "  #".$k.' &ndash; '.$v.";\n";
    }
  }
}

function simple_error($id = 0) {
  return '<div class="handling_result handling_errors">Ошибка'.(($id) ? ' (#'.$id.')' : '').'!</div>';
}

$error = new ScriptError;

// -------- загрузка файла -------- //
if (!empty($_FILES['upload_file'])) {
  try {
    
    if ($_FILES['upload_file']['error'] != 0)
      throw new Exception($error->getMessage(6));

    if (empty($_POST['method']))
      throw new Exception($error->getMessage(1));
    
    if (empty($_POST['item_id']))
      throw new Exception($error->getMessage(7));
      
    $file = $_FILES['upload_file'];
    
    if (!empty($_POST['browser']) and $_POST['browser'] == 'shitty') {
      $json['.response_place .item#'.$_POST['item_id'].' .file_info_block .name'] = '<b>Файл:</b> '.$file['name'];
      $json['.response_place .item#'.$_POST['item_id'].' .file_info_block .size'] = '<b>Размер:</b> '.number_format($file['size'], 0, ',', '&thinsp;').' байт';
      $json['.response_place .item#'.$_POST['item_id'].' .progress_block'] = '<div class="progress_bar"><div class="progress" style="width: 100%;"></div><span class="proc">100%</span></div>';
    }
    
    switch($_POST['method']) {
      case 'orientation':
        if (!preg_match('/(msword|wordprocessingml)/is', $file['type'])) {
          $json[] = simple_error();
          throw new Exception($error->getMessage(3)."<br />Доступны для загрузки файлы типа \"doc\", \"docx\", \"rtf\".");
        }
        $type = 1;
        break;
        
      case 'video_orientation':
        if (!preg_match('/(video)/is', $file['type'])) {
          $json[] = simple_error();
          throw new Exception($error->getMessage(3));
        }
        $type = 2;
        break;
      
      case 'addon_orientation':
        if (!preg_match('/(msword|wordprocessingml)/is', $file['type'])) {
          $json[] = simple_error();
          throw new Exception($error->getMessage(3)."<br />Доступны для загрузки файлы типа \"doc\", \"docx\", \"rtf\".");
        }
        $type = 3;
        break;
        
      case 'recall_orientation':
        if (!preg_match('/(msword|wordprocessingml)/is', $file['type'])) {
          $json[] = simple_error();
          throw new Exception($error->getMessage(3)."<br />Доступны для загрузки файлы типа \"doc\", \"docx\", \"rtf\".");
        }
        $type = 4;
        break;
      
      case 'reference':
        if (!preg_match('/(msword|wordprocessingml)/is', $file['type'])) {
          $json[] = simple_error();
          throw new Exception($error->getMessage(3)."<br />Доступны для загрузки файлы типа \"doc\", \"docx\", \"rtf\".");
        }
        $type = 5;
        break;
        
      default:
        throw new Exception($error->getMessage(2));
        break;
    }
    $section = null;
    if (in_array($type, array(1, 2, 3, 4))) {
      $section = 'orientation';
    } elseif ($type == 5) {
      $section = 'reference';
    }
    if (!empty($_SESSION[$section]['files'][$type])) {
      foreach ($_SESSION[$section]['files'][$type] as $k => $f) {
        if ($file['name'] == $f['basename']) {
          if (empty($_POST['browser']) or $_POST['browser'] != 'shitty')
            $json[] = simple_error();
          throw new Exception('Такой файл уже загружен.');
        }
      }
    }
    
    if (
        ($_POST['method'] == 'orientation') and ($type = 1) and (!empty($_SESSION['orientation']['files'][1])) or
        ($_POST['method'] == 'recall_orientation') and ($type = 4) and (!empty($_SESSION['orientation']['files'][4]))
       ) {
      $json[] = simple_error();
      throw new Exception('Возможно загрузить только один файл.');
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
          $json[] = '<i>Загружен<br />В обработке...</i><span class="delete_relation" id="'.$_POST['item_id'].'" group="files_'.$_POST['method'].'">&times;</span>';
        }
        $_SESSION[$section]['files'][$type][$_POST['item_id']] = pathinfo($_SESSION['dir_session'].$file['name']);
        $_SESSION[$section]['files'][$type][$_POST['item_id']]['path'] = $_SESSION['dir_session'].$file['name'];
        $_SESSION[$section]['files'][$type][$_POST['item_id']]['size'] = filesize($_SESSION['dir_session'].mb_convert_encoding($file['name'], 'Windows-1251', 'UTF-8'));
        
      } else {
        $json[] = simple_error(4);
        throw new Exception($error->getMessage(4));
      }
    } else {
      $json[] = simple_error(3);
      throw new Exception($error->getMessage(5));
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



if (!empty($_POST['method'])) {
  try {
    switch($_POST['method']) {
      case 'orientation_continue':
        $jsonMSG[] = 'ready';
        break;
      
      case 'orientation_add':
        if (isset($_SESSION['orientation']))
          unset($_SESSION['orientation']);
        $jsonMSG[] = 'ready';
        break;
      
      case 'orientation':
      
        if (!empty($_POST['form_name'])) {
          if (empty($_SESSION['orientation']['object'])) {
            $ornt = new Orientation();
          } else {
            $ornt = unserialize($_SESSION['orientation']['object']);
          }
        
          switch($_POST['form_name']) {
            case 'main_form':
              if (!empty($_POST['number']) and !is_numeric($_POST['number'])) {
                throw new Exception('Номер ориентировки: '.$error->getMessage(10));
              }

              if (!empty($_POST['date'])) {
                if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['date'])) { //если формат даты допустим
                  if (strtotime($_POST['date']) > strtotime('now'))  // дата больше текущей
                    throw new Exception('Дата ориентировки: '.$error->getMessage(8));
                } else {
                  throw new Exception('Дата ориентировки: '.$error->getMessage(9));
                }
              }
              
              if (!empty($_POST['crim_case_number']) and !is_numeric($_POST['crim_case_number'])) {
                throw new Exception('У/д: '.$error->getMessage(10));
              }
              
              if (!empty($_POST['crim_case_date'])) {
                if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['crim_case_date'])) { //если формат даты допустим
                  if (strtotime($_POST['crim_case_date']) > strtotime('now'))  // дата больше текущей
                    throw new Exception('У/д: '.$error->getMessage(8));
                } else {
                  throw new Exception('У/д: '.$error->getMessage(9));
                }
              }
              
              if (!empty($_POST['marking'])) {
                $_POST['uk'] = '0';
              }
              
              if (
                  (!empty($_POST['ovd']) and !is_numeric($_POST['ovd'])) or 
                  (!empty($_POST['uk']) and !is_numeric($_POST['uk'])) or 
                  (!empty($_POST['marking']) and !is_numeric($_POST['marking']))
                )
                throw new Exception('Че за нах...!?');
              
              if (!empty($_POST['ovd']))
                $ornt->set_ovd($_POST['ovd']);
              
              if (!empty($_POST['date']))
                $ornt->set_date($_POST['date']);
                
              if (!empty($_POST['number'])) {
                $ornt->set_number($_POST['number']);
              } else {
                $ornt->set_wonumber($_POST['wonumber']);
              }
              
              if (!empty($_POST['wonumber']))
                $ornt->set_wonumber($_POST['wonumber']);
               
              if (!empty($_POST['uk']))
                $ornt->set_uk($_POST['uk']);
              
              if (!empty($_POST['marking']))
                $ornt->set_marking($_POST['marking']);
                
              $_SESSION['orientation']['object'] = serialize($ornt);
              break;
            
            case 'kusp_form':
              if (empty($_POST['ovd']) or empty($_POST['kusp']) or empty($_POST['date'])) {
                throw new Exception($error->getMessage(11));
              }
              if (!is_numeric($_POST['kusp'])) {
                throw new Exception($error->getMessage(10).'<br />Каждый номер КУСП вводится отдельно!');
              }
              
              if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['date'])) { //если формат даты допустим
                if (strtotime($_POST['date']) > strtotime('now')) { // дата больше текущей
                  throw new Exception('Дата КУСП: '.$error->getMessage(8));
                }
                /*if (!empty($_SESSION['orientation']['date']) and strtotime($_POST['date']) > strtotime($_SESSION['orientation']['date'])) {
                  throw new Exception('Дата КУСП: Вводимая дата не может быть больше даты ориентировки!');
                }
                if (!empty($_SESSION['orientation']['crim_case_date']) and strtotime($_POST['date']) > strtotime($_SESSION['orientation']['crim_case_date'])) {
                  throw new Exception('Дата КУСП: Вводимая дата не может быть больше даты возбуждения уголовного дела!');
                }
                $kusp_date = new DateTime($_POST['date']);
                $cur_date = new DateTime(date('d.m.Y'));
                $interval = $kusp_date->diff($cur_date);
                if ($interval->days > 30) {
                  $jsonMSG[] = 'Внимание! <br /> Вы указали дату, которая больше текущей на '.$interval->days.' '.case_of_word($interval->days, 'день').'. В случае, если она является ошибочной, возможно неверное выставление долгов по вводу.';
                }*/
              } else {
                throw new Exception('Дата КУСП: '.$error->getMessage(9));
              }
              
              $kusp = new Kusp();
              $kusp->set_kusp($_POST['kusp'], $_POST['date'], $_POST['ovd']);
              
              if ($ornt->add_kusp($kusp) === false)
                throw new Exception($ornt->get_last_error());
              
              $json['.messages_block .added_row'] = related_kusp($ornt->get_kusp_array(), 'orientation');
              $_SESSION['orientation']['object'] = serialize($ornt);
              break;
            
            case 'crime_case_form':
              $cc = new CrimeCase();
              if (!empty($_POST['ovd']))
                $cc->set_ovd($_POST['ovd']);
              if (!empty($_POST['crim_case_number']))
                $cc->set_number($_POST['crim_case_number']);
              if (!empty($_POST['crim_case_date']))
                $cc->set_date($_POST['crim_case_date']);
              if ($cc->is_empty()) {
                $ornt->unset_crime_case();
              } else {
                $ornt->set_crime_case($cc);
              }
              $_SESSION['orientation']['object'] = serialize($ornt);
              break;
            
            case 'recall_form':  /* запрос с формы редактирования */
              if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['recall'])) { //если формат даты допустим
                if (strtotime($_POST['recall']) > strtotime('now')) // дата больше текущей
                  throw new Exception('Дата отбоя: '.$error->getMessage(8));
              } else {
                throw new Exception('Дата отбоя: '.$error->getMessage(9));
              }
              $ornt->set_recall($_POST['recall']);
              $_SESSION['orientation']['object'] = serialize($ornt);
              break;
            
            case 'pre-registration':
              if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['ornt_create'])) {
                throw new Exception('Недостаточно прав.');
              }
              
              if (empty($_POST['ovd']) or empty($_POST['date'])) {
                throw new Exception($error->getMessage(11));
              }
              
              $ornt = new Orientation();
              if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['date'])) { //если формат даты допустим
                if (strtotime($_POST['date']) > strtotime('now'))  // дата больше текущей
                  throw new Exception('Дата ориентировки: '.$error->getMessage(8));
              } else {
                throw new Exception('Дата ориентировки: '.$error->getMessage(9));
              }
              $ornt->set_date($_POST['date']);
              
              if (!empty($_POST['uk'])) {
                $ornt->set_uk($_POST['uk']);
              } elseif (!empty($_POST['marking'])) {
                if (!is_numeric($_POST['marking']))
                  throw new Exception('Вид происшествия: '. $error->getMessage(10));
                $ornt->set_marking($_POST['marking']);
              } else {
                throw new Exception('Не указана статья УК или вид происшествия!');
              }
              
              $ornt->set_ovd($_POST['ovd']);
              
              require_once(KERNEL.'connection.php');
              $query = '
                SELECT
                  o.`number` + 1 as `number`
                FROM
                  `l_orientations` as o
                WHERE
                  o.`number` IS NOT NULL AND
                  o.`year` = YEAR(CURRENT_DATE)
                ORDER BY
                  o.`number` DESC
                LIMIT 1
              ';
              $result = mysql_query($query);
              $row = mysql_fetch_assoc($result);
              $ornt->set_number($row['number']);
              if ($ornt->save()) {
                $json['.registration_block .response_place'] = '
                  <div class="opacity_back" href="orientations.php"></div><div class="info_box">
                    <div class="block_header">Регистрационный номер ориентировки:</div>
                    <div class="message"><div class="reg_number"><span>'.$ornt->get_number().'</span></div></div>
                    <div class="popup_window_button_block">
                      <div class="add_button_box" href="orientations.php">
                        <div class="button_block"><span class="button_name">Ok</span></div>
                      </div>
                    </div>
                  </div>';
              } else {
                throw new Exception($ornt->get_last_error());
              }
              //<div class="reg_number"><span>'.$decision->data['reg'].'</span></div>
              break;

            case 'registration':   /* запрос с формы сохранения ориентировки */
            case 'recall':         /* запрос с формы отбоя 'ornt_recall.php' */
            case 'addon':          /* запрос с формы отбоя 'ornt_addon.php' */
              
              if ($_POST['form_name'] == 'registration') {
                if (is_null($ornt->get_number()) and is_null($ornt->get_wonumber()))
                  $valid[] = 'Номер ориентировки';
                if (is_null($ornt->get_date()))
                  $valid[] = 'Дата ориентировки';
                if (is_null($ornt->get_ovd()))
                  $valid[] = 'ОВД-инициатор';
                if (is_null($ornt->get_marking()) and is_null($ornt->get_uk()))
                  $valid[] = 'Вид происшествия';
                if ($ornt->get_kusp_count() == 0)
                  $valid[] = 'КУСП';
                if (($ornt->get_crime_case() !== false) and (
                    is_null($ornt->get_crime_case_number()) or
                    is_null($ornt->get_crime_case_date()) or
                    is_null($ornt->get_crime_case_ovd())
                   ))
                     $valid[] = 'Реквизиты У/д';
                if ((empty($_SESSION['orientation']['files'][1])) and ($ornt->get_files_array(1) === false))
                  $valid[] = 'Электронный файл ориентировки';
                if (!empty($valid))
                  throw new Exception('Не заполнены обязательные поля: '.implode(', ', $valid).'.');
                
                require_once(KERNEL.'connection.php');
                $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
                
                if (!is_null($ornt->get_crime_case())) {
                  $ornt->save_crime_case();
                }
                
                if (is_null($ornt->get_id())) {
                  if ($ornt->save() === false)
                    throw new Exception($ornt->get_last_error());
                } else {
                  if ($ornt->update() === false)
                    throw new Exception($ornt->get_last_error());
                }
                
                
                // ******** сохраняем связь с КУСП ******** //         
                foreach($ornt->get_kusp_list() as $n => $kusp) {
                  if (is_null($kusp->get_id()))
                    $kusp->save();
                  $query = '
                    INSERT INTO
                      `l_orient_kusp`(`kusp`, `orientation`, `deleted`, 
                                      `create_date`, `create_time`, `active_id`, 
                                      `update_date`, `update_time`, `update_active_id`)
                    VALUES
                                     ('.$kusp->get_id().', '.$ornt->get_id().', 0,
                                      CURRENT_DATE, CURRENT_TIME, '.$kusp->active_id.', 
                                      CURRENT_DATE, CURRENT_TIME, '.$kusp->active_id.')
                    ON DUPLICATE KEY UPDATE
                      `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$kusp->active_id;
                  if (!mysql_query($query))
                    throw new Exception('<b>KUSP insert error</b>: '.mysql_error().' .Query string: <pre>'.$query.'</pre>');
                }
                // ^^^^^^^^ сохраняем КУСП ^^^^^^^^ //
                
              } elseif ($_POST['form_name'] == 'recall') {
                if (empty($_POST['id']))
                  throw new Exception($error->getMessage(13));
                if (empty($_POST['recall']))
                  throw new Exception('Не указана дата отбоя.');
                if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['recall'])) { //если формат даты допустим
                  if (strtotime($_POST['recall']) > strtotime('now')) // дата больше текущей
                    throw new Exception('Дата отбоя: '.$error->getMessage(8));
                } else {
                  throw new Exception('Дата отбоя: '.$error->getMessage(9));
                }
                if (empty($_SESSION['orientation']['files'][4]))
                  throw new Exception('Не загружен электронный образ.');

                $ornt = new Orientation($_POST['id']);
                $ornt->full_data();
                $ornt->set_recall($_POST['recall']);
                
                if ($ornt->update() === false)
                  throw new Exception($ornt->get_last_error());
                  
              } elseif ($_POST['form_name'] == 'addon') {
                if (empty($_SESSION['orientation']['files'][3]))
                  throw new Exception('Не загружен электронный образ.');
                $ornt = new Orientation($_POST['id']);
              }
              // ******** сохраняем файлы ******** //     
              if (!empty($_SESSION['orientation']['files'])) {
                foreach ($_SESSION['orientation']['files'] as $type => $files) {
                  foreach ($files as $n => $file) {
                    if (!is_file(mb_convert_encoding($file['path'], 'Windows-1251', 'UTF-8'))) {
                      throw new Exception('Файл "'.$file['path'].'" недоступен на сервере! Повторите загрузку.');
                    }
                    $currFile = new ElFile();

                    switch ($type) {
                      case 1:
                        $currFile->save($type, 'orientation', $ornt->get_id());
                        $fileNameParts[0] = 'Orient';
                        $tmp_dir = 'f:/Site_storage/_tmp/Orientations/';
                        break;
                      case 3:
                        $currFile->save($type, 'orientation', $ornt->get_id());
                        $fileNameParts[0] = 'Addon';
                        $tmp_dir = 'f:/Site_storage/_tmp/Orientations/';
                        break;
                      case 4:
                        $currFile->save($type, 'orientation', $ornt->get_id());
                        $fileNameParts[0] = 'Recall';
                        $tmp_dir = 'f:/Site_storage/_tmp/Orientations/';
                        break;
                      default:
                        //throw new Exception('<b>Unknown file type!</b>');
                        break;
                    }

                    $fileNameParts[1] = $ornt->get_year();
                    $fileNameParts[2] = date('m', strtotime($ornt->get_date()));
                    $fileNameParts[3] = $ornt->get_id();
                    $fileNameParts[4] = $currFile->get_id();
                    $new_name = implode('_', $fileNameParts).'.'.$file['extension'];

                    $currFile->move($_SESSION['dir_session'].$file['basename'], $tmp_dir, $new_name);

                    unset($_SESSION['orientation']['files'][$type][$n]);
                    if (empty($_SESSION['orientation']['files'][$type]))
                      unset($_SESSION['orientation']['files'][$type]);
                  }
                }
                unset($_SESSION['orientation']['files']);
              }
              // ^^^^^^^^ сохраняем файлы ^^^^^^^^ //
              $json['.registration_block .response_place'] = '<div class="opacity_back" href="ornt_view.php?id='.$ornt->get_id().'"></div><div class="info_box">
                <div class="block_header">Ориентировка:</div>
                <div class="message">Успешно сохранено!</div>
                <div class="popup_window_button_block">
                  <div class="add_button_box" href="ornt_view.php?id='.$ornt->get_id().'">
                    <div class="button_block"><span class="button_name">Ok</span></div>
                  </div>
                  <div class="add_button_box" href="mailing.php?id='.$ornt->get_id().'">
                    <div class="button_block"><span class="button_name">Рассылка</span></div>
                  </div>
                </div>
                </div>';
              unset($_SESSION['orientation']);
              break;
          }
        }
        
        break;
        
      case 'reference':
        if (empty($_SESSION['reference']['object'])) {
          $ref = new Reference();
        } else {
          $ref = unserialize($_SESSION['reference']['object']);
        }
        if (!empty($_POST['form_name'])) {
          switch ($_POST['form_name']) {
            case 'kusp_form':
              if (empty($_POST['ovd']) or empty($_POST['kusp']) or empty($_POST['date'])) {
                throw new Exception($error->getMessage(11));
              }
              if (!is_numeric($_POST['kusp'])) {
                throw new Exception($error->getMessage(10).'<br />Каждый номер КУСП вводится отдельно!');
              }
              
              if (preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['date'])) { //если формат даты допустим
                if (strtotime($_POST['date']) > strtotime('now')) { // дата больше текущей
                  throw new Exception('Дата КУСП: '.$error->getMessage(8));
                }
              } else {
                throw new Exception('Дата КУСП: '.$error->getMessage(9));
              }
              
              $kusp = new Kusp();
              $kusp->set_kusp($_POST['kusp'], $_POST['date'], $_POST['ovd']);
              
              if ($ref->add_kusp($kusp) === false)
                throw new Exception($ref->get_last_error());
              
              $json['.messages_block .added_row'] = related_kusp($ref->get_kusp_array(), 'reference');
              $_SESSION['reference']['object'] = serialize($ref);
              break;
            
            case 'crime_case_form':
              $cc = new CrimeCase();
              if (!empty($_POST['ovd']))
                $cc->set_ovd($_POST['ovd']);
              if (!empty($_POST['crim_case_number']))
                $cc->set_number($_POST['crim_case_number']);
              if (!empty($_POST['crim_case_date']))
                $cc->set_date($_POST['crim_case_date']);

              if ($ref->set_crime_case($cc) === false)
                throw new Exception($ref->get_last_error());
              $_SESSION['reference']['object'] = serialize($ref);
              break;
            
            case 'registration':
              if ($ref->get_kusp_count() == 0)
                $valid[] = 'КУСП';
              if (($ref->get_crime_case() !== false) and (
                  is_null($ref->get_crime_case_number()) or
                  is_null($ref->get_crime_case_date()) or
                  is_null($ref->get_crime_case_ovd())
                 ))
                   $valid[] = 'Реквизиты У/д';
              if ((empty($_SESSION['reference']['files'][5])) and ($ref->get_files_array() === false))
                $valid[] = 'Электронный файл справки';
              if (!empty($valid))
                throw new Exception('Не заполнены обязательные поля: '.implode(', ', $valid).'.');
              
              
              require_once(KERNEL.'connection.php');
              $active_id = (isset($_SESSION['activity_id'])) ? $_SESSION['activity_id'] : 0;
              
              if (!is_null($ref->get_crime_case())) {
                $cc = $ref->get_crime_case();
                if (is_object($cc)) {
                  if (!$cc->save())
                    throw new Exception($cc->get_last_error());
                }
              }
              
              if (is_null($ref->get_id())) {
                if ($ref->save() === false)
                  throw new Exception($ref->get_last_error());
              } else {
                if ($ref->update() === false)
                  throw new Exception($ref->get_last_error());
              }
              
              
              // ******** сохраняем связь с КУСП ******** //         
              foreach($ref->get_kusp_list() as $n => $kusp) {
                if (is_null($kusp->get_id())) {
                  if (!$kusp->save())
                    throw new Exception($kusp->get_last_error());
                }
                $query = '
                  INSERT INTO
                    `l_reference_kusp`(`kusp`, `reference`, `deleted`, 
                                       `create_date`, `create_time`, `active_id`, 
                                       `update_date`, `update_time`, `update_active_id`)
                  VALUES
                                      ('.$kusp->get_id().', '.$ref->get_id().', 0,
                                       CURRENT_DATE, CURRENT_TIME, '.$kusp->active_id.', 
                                       CURRENT_DATE, CURRENT_TIME, '.$kusp->active_id.')
                  ON DUPLICATE KEY UPDATE
                    `deleted` = 0, `update_date` = CURRENT_DATE, `update_time` = CURRENT_TIME, `update_active_id` = '.$kusp->active_id;
                if (!mysql_query($query))
                  throw new Exception('<b>KUSP insert error</b>: '.mysql_error().' .Query string: <pre>'.$query.'</pre>');
              }
              // ^^^^^^^^ сохраняем КУСП ^^^^^^^^ //
              
              // ******** сохраняем файлы ******** //     
              if (!empty($_SESSION['reference']['files'])) {
                foreach ($_SESSION['reference']['files'] as $type => $files) {
                  foreach ($files as $n => $file) {
                    $currFile = new ElFile();
                    
                    switch ($type) {
                      case 5:
                        $currFile->save($type, 'reference', $ref->get_id());
                        $fileNameParts[0] = 'Reference';
                        $tmp_dir = 'f:/Site_storage/_tmp/References/';
                        break;
                      default:
                        //throw new Exception('<b>Unknown file type!</b>');
                        break;
                    }
                    
                    $fileNameParts[1] = date('Y', strtotime($ref->get_create_date()));
                    $fileNameParts[2] = date('m', strtotime($ref->get_create_date()));
                    $fileNameParts[3] = $ref->get_id();
                    $fileNameParts[4] = $currFile->get_id();
                    $new_name = implode('_', $fileNameParts).'.'.$file['extension'];

                    $currFile->move($_SESSION['dir_session'].$file['basename'], $tmp_dir, $new_name);
                    
                    unset($_SESSION['reference']['files'][$type][$n]);
                    if (empty($_SESSION['reference']['files'][$type]))
                      unset($_SESSION['reference']['files'][$type]);
                  }
                }
                unset($_SESSION['reference']['files']);
              }
              // ^^^^^^^^ сохраняем файлы ^^^^^^^^ //
              $json['.registration_block .response_place'] = '<div class="opacity_back" href="ref_view.php?id='.$ref->get_id().'"></div><div class="info_box">
                <div class="block_header">Обзорная справка по преступлению</div>
                <div class="message">Успешно сохранено!</div>
                <div class="popup_window_button_block">
                  <div class="add_button_box" href="ref_view.php?id='.$ref->get_id().'">
                    <div class="button_block"><span class="button_name">Ok</span></div>
                  </div>
                </div>
                </div>';
              unset($_SESSION['reference']);
              break;
          }
        }
        break;
        
      case 'reference_add':
        if (isset($_SESSION['reference']))
          unset($_SESSION['reference']);
        $jsonMSG[] = 'ready';
        break;
      
      case 'reference_continue':
        $jsonMSG[] = 'ready';
        break;
      
      case 'search':
        if (mb_strlen($_POST['query']) < 2)
          break;
        
        $rec = 15;
        $total = $pages = 0;
        $p = 1;
        $where = $sect = $query = null;
        
        if (empty($_POST['query']))
          throw new Exception('Какая-то херня...');
        if (empty($_POST['types'])) {
          $json['.search_result_block'] = null;
          $json['.result_count'] = 'Не выбраны поисковые разделы!';
          throw new Exception();
        }
        
        /* -------- поиск по разделу ориентировок -------- */
        if (!empty($_POST['types']['ornt']) or !empty($_POST['types']['addon']) or !empty($_POST['types']['recall'])) {
          $types = null;
          $sect[0] = 'f.`orientation` IS NOT NULL';
          if (!empty($_POST['types']['ornt']))
            $types[] = '1';
          if (!empty($_POST['types']['addon']))
            $types[] = '3';
          if (!empty($_POST['types']['recall']))
            $types[] = '4';
          if (!empty($types))
            $sect[0] = 'f.`type` IN ('.implode(',', $types).')';
        }
        /* -------- поиск по разделу ориентировок -------- */
        
        /* -------- поиск по разделу справок -------- */
        if (!empty($_POST['types']['ref'])) {
          $sect[1] = 'f.`reference` IS NOT NULL';
        }
        /* -------- поиск по разделу справок -------- */
        
        if (!empty($sect)) {
          $where[] = '('.implode(' OR ', $sect).')';
        }
        set_time_limit(0);
        
        require_once(KERNEL.'connection.php');
        $str = mysql_real_escape_string(trim($_POST['query']));
        
        $where[] = 'MATCH(fc.`FileContent`) AGAINST (\''.$str.'\' IN BOOLEAN MODE)';
        
        /*
        if (!empty($_POST['types']['ek']))      // поиск по электронному КУСП
          $query[] = '(SELECT COUNT(DISTINCT ek.`id`) as cnt FROM `ek_kusp` as ek WHERE ek.`story` LIKE "%'.$str.'%")';
        
        */
        
        // ******** вычисляем общее кол-во результатов ******** //
        if (!empty($_POST['types']['ornt']) or !empty($_POST['types']['addon']) or !empty($_POST['types']['recall']) or !empty($_POST['types']['ref'])) 
          $query[] = ' (SELECT
                          COUNT(DISTINCT fc.`id`) as cnt 
                        FROM 
                          `l_files_content` as fc
                        JOIN
                          `l_files` as f ON
                            f.`id` = fc.`file`
                        WHERE 
                          '.implode(' AND ', $where).') ';
        
        $query = 'SELECT '.implode(' + ', $query). ' as `cnt` ';
        if (!$result = mysql_query($query))
          throw new Exception(mysql_error().' Query: <pre>'.$query.'</pre>');
        
        $row = mysql_fetch_assoc($result);
        $total = $row['cnt'];
        $pages = ceil($total/$rec);
        
        if (!empty($_POST['p'])) { 
          $p = to_integer($_POST['p']);
          
          if ($p > $pages) $p = $pages;
        }
        
        $listing = array(1, 2, $p - 1, $p, $p + 1, $pages - 1, $pages);
        foreach($listing as $k => $v) {
          if ($v > $pages) unset($listing[$k]);
        }
        $listing = array_diff(array_unique($listing), array(0));
        
        if ($p == 1) {
          $limit = $rec;
        } else {
          $limit = ($p * $rec - $rec).', '.$rec;
        }
        // ******** вычисляем общее кол-во результатов ******** //
        
        $query = null;
        if (!empty($_POST['types']['ornt']) or !empty($_POST['types']['addon']) or !empty($_POST['types']['recall']) or !empty($_POST['types']['ref']))
          $query[] = '
            (SELECT
              f.`type` as `order`, NULL as `reg_date`, NULL as `reg_number`,
              f.`id`, fc.`FileContent`, f.`FilePath`, f.`link` as `download`,
              CASE
                WHEN f.`orientation` IS NOT NULL THEN CONCAT("ornt_view.php?id=", f.`orientation`)
                WHEN f.`reference` IS NOT NULL THEN CONCAT("ref_view.php?id=", f.`reference`)
              END as `link`,
              CASE
                WHEN f.`orientation` IS NOT NULL THEN so.`type`
                WHEN f.`reference` IS NOT NULL THEN "Обзорная справка"
              END as `file_type`,
              CASE
                WHEN f.`orientation` IS NOT NULL THEN oovd.`ovd`
                WHEN f.`reference` IS NOT NULL THEN rovd.`ovd`
              END as `ovd`,
              CASE
                WHEN f.`orientation` IS NOT NULL THEN
                  CONCAT("№", o.`number`, " от ", DATE_FORMAT(o.`date`, "%d.%m.%Y"))
              END as `target`,
              CASE
                WHEN f.`orientation` IS NOT NULL THEN
                  IF(ot.`type` IS NULL, CONCAT("Розыск преступника", " (ст.", ouk.`st`, " УК РФ)"), ot.`type`)
              END as `type`,
              CASE
                WHEN f.`orientation` IS NOT NULL THEN
                  IF(o.`crime_case` IS NULL, NULL,
                    CONCAT("У/д №", occ.`crime_case_number`, " от ", DATE_FORMAT(occ.`crim_case_date`, "%d.%m.%Y"))
                  )
                WHEN f.`reference` IS NOT NULL THEN
                  IF(r.`crime_case` IS NULL, NULL,
                    CONCAT("У/д №", rcc.`crime_case_number`, " от ", DATE_FORMAT(rcc.`crim_case_date`, "%d.%m.%Y"))
                  )
              END as `crime_case`,
              TRUNCATE(MATCH(fc.`FileContent`) AGAINST (\''.$str.'\'), 2) as `match`
            FROM
              `l_files_content` as fc
            JOIN
              `l_files` as f ON
                fc.`file` = f.`id`
            LEFT JOIN
              `l_orientations` as o ON
                o.`id` = f.`orientation` AND
                o.`deleted` = 0
              LEFT JOIN
                `spr_orientation_types` as ot ON
                  ot.`id` = o.`marking`
              LEFT JOIN
                `spr_uk` as ouk ON
                  ouk.`id_uk` = o.`uk`
              LEFT JOIN
                `l_crime_cases` as occ ON
                  occ.`id` = o.`crime_case`
              LEFT JOIN
                `spr_ovd` as oovd ON
                  oovd.`id_ovd` = o.`ovd`
            LEFT JOIN
              `l_references` as r ON
                r.`id` = f.`reference` AND
                r.`deleted` = 0
              LEFT JOIN
                `l_crime_cases` as rcc ON
                  rcc.`id` = r.`crime_case`
              LEFT JOIN
                `spr_ovd` as rovd ON
                  rovd.`id_ovd` = r.`ovd`
            LEFT JOIN
              `spr_orientation` as so ON
                so.`id` = f.`type`
            WHERE
              '.implode(' AND ', $where).'
            ORDER BY
              `match` DESC
            )
          ';
        
        /*
        if (!empty($_POST['types']['ek']))
          $query[] = '
            (SELECT
              5 as `order`, ek.`reg_date`, ek.`reg_number`,
              ek.`id`,
              CONCAT(
                IF(ek.`declarer_person` IS NULL, "", CONCAT("Заявитель: ", ek.`declarer_person`, "\n")),
                IF(ek.`declarer_org` IS NULL, "", CONCAT("Заявитель (организация): ", ek.`declarer_org`, "\n")),
                ek.`story`
              ) as `FileContent`,
              NULL as `FilePath`, NULL as `download`,
              CONCAT("ek.php?id=", ek.`id`) as `link`,
              NULL as `file_type`, ovd.`ovd`,
              CONCAT(ovd.`ovd`, ", КУСП №", ek.`reg_number`, " от ", DATE_FORMAT(ek.`reg_date`, "%d.%m.%Y")) as `target`,
              NULL as `type`,
              NULL as `crime_case`,
              TRUNCATE(MATCH(ek.`declarer_person`, ek.`declarer_org`, ek.`story`) AGAINST (\''.$str.'\'), 2) as `match`
            FROM
              `ek_kusp` as ek
            JOIN
              `spr_ovd` as ovd ON
                ovd.`id_ovd` = ek.`ovd`
            WHERE
              ek.`story` LIKE "%'.$str.'%"
            )
          ';
          */
          #TRUNCATE(MATCH(ek.`declarer_person`, ek.`declarer_org`, ek.`story`) AGAINST (\''.$str.'*\'), 2)
          #MATCH(ek.`declarer_person`, ek.`declarer_org`, ek.`story`) AGAINST (\''.$str.'*\' IN BOOLEAN MODE)
        $query = implode(' UNION ', $query).'ORDER BY `order`, `reg_date` DESC, `reg_number` DESC LIMIT '.$limit;
        
        $mcrt_s = microtime();
        if (!$result = mysql_query($query))
          throw new Exception(mysql_error().' Query: <pre>'.$query.'</pre>');
        $mcrt_f = microtime();
                
        if (mysql_num_rows($result)) {
          $n = ($p - 1) * $rec + 1;
          $json['.search_result_block'] = '<dl>';
          while ($row = mysql_fetch_assoc($result)) {
            $lighting = preg_replace('/[^a-zA-Zа-яА-Я0-9_ ]/iu', '', $str);                                // строка для подсветки без лишних символов
            
            $parts = explode(' ', $lighting);  // достаем части поискового запроса, разделитель "пробел"
            $substrsFull = array();
            $cntr = 0;
            foreach ($parts as $part) {
              preg_match_all('/.{50}('.$part.').{50}/iu', $row['FileContent'], $substrs);                // вырезаем кусочки текста вокруг совпадения
              foreach ($substrs as $x => $s) {
                $substrs[$x] = preg_replace('/('.$part.')/iu', '<b>\\0</b>', $s);    // подсвечиваем совпадения
              }
              preg_match_all('/('.$part.')/iu', $row['FileContent'], $matches);                            // считаем кол-во совпадений
              $cntr += count($matches[0]);
            }
            
            $text = implode(' ... ', array_slice($substrs[0], 0, 5));
            $ext = pathinfo($row['FilePath'], PATHINFO_EXTENSION);
            
            $json['.search_result_block'] .= '
              <dt class="light-gray">'.$n++.'.</dt>
              <dd>
                <div class="result_item"><a href="'.$row['link'].'">'.$row['file_type'].' '.$row['target'].'</a></div>
                <div class="cuted_text">'.((empty($text) ? $row['FileContent'] : $text)).'</div>
                <div class="file_block">
                  <div class="type_image">'.((is_file($row['FilePath'])) ? '<a href="download.php?file='.$row['download'].'"><img src="/images/FileTypes/'.$ext.'.png" alt="'.$ext.'"/></a>' : '').'</div>
                  <div class="file_size">'.((is_file($row['FilePath'])) ? readable_bytes(filesize($row['FilePath'])) : '').'</div>
                </div>
                <div class="info_block">
                  <i>Индекс релевантности: '.$row['match'].'</i><br />
                  <i>Встречается в тексте: '.$cntr.' раз</i>
                </div>
                <div class="bottomTextBlock smallTextBlock">
                  <span class="smallTextItem">'.$row['ovd'].'</span>
                  <span class="smallTextItem">'.$row['type'].'</span>
                  <span class="smallTextItem">'.$row['crime_case'].'</span>
                </div>
                <div class="buttonsBlock">
                </div>
              </dd>
            ';
          }
          $json['.search_result_block'] .= '</dl>';
          if ($pages > 1) {
            $json['.search_result_block'] .= '<div class="result_listing">';
            if ($p > 1) {
              $_POST['p'] = $p - 1;
              $json['.search_result_block'] .= '<span class="list_dir"><a href="search.php?'.http_build_query($_POST).'">&larr; Предыдущая</a></span>';
            } else {
              $json['.search_result_block'] .= '<span class="list_dir">&larr; Предыдущая</span>';
            }
            if ($p < $pages) {
              $_POST['p'] = $p + 1;
              $json['.search_result_block'] .= '<span class="list_dir"><a href="search.php?'.http_build_query($_POST).'">Следующая &rarr;</a></span>';
            } else {
              $json['.search_result_block'] .= '<span class="list_dir">Следующая &rarr;</span>';
            }
            $json['.search_result_block'] .= '<span class="list_dir">';
            $n = 1;
            foreach($listing as $page) {
              if ($page == $n) {
                $n++; 
              } else {
                $json['.search_result_block'] .= '<span class="page">...</span>';
                $n = $page + 1;
              }
              if ($p == $page) {
                $json['.search_result_block'] .= '<span class="page current"><b>'.$page.'</b></span>';
              } else {
                $_POST['p'] = $page;
                $json['.search_result_block'] .= '<span class="page"><a href="search.php?'.http_build_query($_POST).'">'.$page.'</a></span>';
              }
            }
            $json['.search_result_block'] .= '</span>';
            $json['.search_result_block'] .= '</div>';
          }
          $json['.result_count'] = 'Найдено '.$total.' ('.number_format(abs($mcrt_f - $mcrt_s), 2).' сек.)';
        } else {
          $json['.result_count'] = 'Ничего не найдено ('.number_format(abs($mcrt_f - $mcrt_s), 2).' сек.)';
          $json['.search_result_block'] = null;
        }
        $where = null;
        break;
        
      case 'search_ek':
        set_time_limit(0);
        $where = null;
        unset($_POST['method']);
        require_once(KERNEL.'connect.php');        
        
        if (!empty($_POST['ovd']) and is_numeric($_POST['ovd'])) {
          $where[] = 'ek.`ovd` = '.$_POST['ovd'];
        }
        if (!empty($_POST['kusp']) and is_numeric($_POST['kusp'])) {
          $where[] = 'ek.`reg_number` = '.$_POST['kusp'];
        }
        if (!empty($_POST['r_s']) and preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['r_s'])) {
          if (!empty($_POST['r_t']) and preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['r_t']))
            $where[] = 'ek.`reg_date` BETWEEN STR_TO_DATE("'.$_POST['r_s'].'", "%d.%m.%Y") AND STR_TO_DATE("'.$_POST['r_t'].'", "%d.%m.%Y")';
          else
            $where[] = 'ek.`reg_date` = STR_TO_DATE("'.$_POST['r_s'].'", "%d.%m.%Y")';
        }
        if (!empty($_POST['fabula'])) {
          //$where[] = 'MATCH(ek.`declarer_person`, ek.`declarer_org`, ek.`story`) AGAINST ("'.$db->real_escape_string($_POST['fabula']).'" IN BOOLEAN MODE)';
        }
        if (!empty($_POST['declarer'])) {
          $where[] = 'ek.`declarer_person` LIKE "'.$db->real_escape_string($_POST['declarer']).'%"';
        }
        if (!empty($_POST['story'])) {
          $where[] = 'ek.`story` LIKE "%'.$db->real_escape_string($_POST['story']).'%"';
        }
        // ^^^^^^^^ КУСП ^^^^^^^^ //
        
        // ******** Решения ******** //
        if (!empty($_POST['decision']) and is_numeric($_POST['decision'])) {
          $where[] = 'kh.`dec_code` = '.$_POST['decision'];
        }
        if (!empty($_POST['dec_num'])) {
          //$where[] = 'MATCH(kh.`dec_number`) AGAINST ("'.$db->real_escape_string($_POST['dec_num']).'" IN BOOLEAN MODE)';
          $where[] = 'kh.`dec_number` LIKE "'.$db->real_escape_string($_POST['dec_num']).'%"';
        }
        if (!empty($_POST['dec_s']) and preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['dec_s'])) {
          if (!empty($_POST['dec_t']) and preg_match("|^[0-3]\d\.[0-1]\d\.[1-2]\d{3}$|", $_POST['dec_t']))
            $where[] = 'kh.`dec_date` BETWEEN STR_TO_DATE("'.$_POST['dec_s'].'", "%d.%m.%Y") AND STR_TO_DATE("'.$_POST['dec_t'].'", "%d.%m.%Y")';
          else
            $where[] = 'kh.`dec_date` = STR_TO_DATE("'.$_POST['dec_s'].'", "%d.%m.%Y")';
        }
        if (!empty($_POST['article'])) {
          //$where[] = 'MATCH(kh.`qualification`) AGAINST (\''.$db->real_escape_string($_POST['article']).'\' IN BOOLEAN MODE)';
          $where[] = 'kh.`qualification` LIKE "'.$db->real_escape_string($_POST['article']).'%"';
        }
        // ^^^^^^^^ Решения ^^^^^^^^ //
        
        if (empty($where)) {
          $json['.result_count'] = 'Пустой поисковый запрос.';
          $json['.search_result_block'] = null;
          break;
        }
        
        // ******** вычисляем общее кол-во результатов ******** //
        $query = '
          SELECT DISTINCT
            COUNT(ek.`id`) as `cnt`
          FROM
            `ek_kusp` as ek
          JOIN
            `spr_ovd` as ovd ON
              ovd.`id_ovd` = ek.`ovd`
          LEFT JOIN
            `ek_dec_history` as kh ON
              kh.`kusp` = ek.`id` AND
              kh.`error_rec` = 0
          WHERE
            '.implode(' AND ', $where).'
        ';
        
        if (!$result = $db->query($query)) {
          if (!empty($_SESSION['user']['admin'])) {
            throw new Exception($db->error.' Query: <pre>'.$query.'</pre>');
          } else {
            throw new Exception('Внутренняя ошибка сервера.');
          }
        }
        
        $row = $result->fetch_object();
        $total = $row->cnt;
        $pages = ceil($total/RECORDS);
        
        if (!empty($_POST['p'])) { 
          $p = to_integer($_POST['p']);
          
          if ($p > $pages) $p = $pages;
        } else {
          $p = 1;
        }
        
        $listing = array(1, 2, $p - 1, $p, $p + 1, $pages - 1, $pages);
        foreach($listing as $k => $v) {
          if ($v > $pages) unset($listing[$k]);
        }
        $listing = array_diff(array_unique($listing), array(0));
        
        if ($p == 1) {
          $limit = RECORDS;
        } else {
          $limit = ($p * RECORDS - RECORDS).', '.RECORDS;
        }
        // ^^^^^^^^ вычисляем общее кол-во результатов ^^^^^^^^ //
        
        $query = '
          SELECT DISTINCT
            ek.`id`, ovd.`ovd`, 
            CONCAT(ek.`reg_number`, ", ", 
                   DATE_FORMAT(ek.`reg_date`, "%d.%m.%Y"), " ", 
                   DATE_FORMAT(ek.`reg_time`, "%H:%i")
                   ) as `reg`,
            ek.`story`, ek.`marking`, 
            CONCAT(IFNULL(ek.`declarer_person`, ""), IF(ek.`declarer_org` IS NOT NULL, CONCAT(", ", ek.`declarer_org`), "")) as `declarer`,
            IF(ek.`event_code` IS NOT NULL, "ПК \"Легенда\"", "СОДЧ") as `source`,
            GROUP_CONCAT(
              DISTINCT
                IF(kh.`dec_date` IS NOT NULL AND kh.`dec_date` <> "0000-00-00", DATE_FORMAT(kh.`dec_date`, "%d.%m.%Y"), ""), 
                IF(kh.`decision` IS NOT NULL, CONCAT(" ", kh.`decision`), ""),
                IF(kh.`term` IS NOT NULL AND kh.`term` <> "0000-00-00" AND kh.`dec_code` = 23, CONCAT(" до ", DATE_FORMAT(kh.`term`, "%d.%m.%Y")), ""),
                IF(kh.`dec_number` IS NOT NULL AND kh.`dec_number` <> "", CONCAT(", №", kh.`dec_number`), ""),
                IF(kh.`qualification` IS NOT NULL AND kh.`qualification` <> "", CONCAT(", ", kh.`qualification`), "")
              ORDER BY kh.`dec_date` DESC
              SEPARATOR "<br />"
            ) as `decisions`
          FROM
            `ek_kusp` as ek
          JOIN
            `spr_ovd` as ovd ON
              ovd.`id_ovd` = ek.`ovd`
          LEFT JOIN
            `ek_dec_history` as kh ON
              kh.`kusp` = ek.`id` AND
              kh.`error_rec` = 0
          WHERE
            '.implode(' AND ', $where).'
          GROUP BY
            ek.`id`
          ORDER BY
            ek.`reg_date` DESC, ek.`reg_number` DESC, ek.`ovd`
          LIMIT
            '.$limit.'
        ';
        $mcrt_s = microtime();
        $result = $db->query($query);
        $mcrt_f = microtime();
        
        if (empty($db->error)) {
          if ($result->num_rows == 0) {
            $json['.result_count'] = 'Ничего не найдено ('.number_format(abs($mcrt_f - $mcrt_s), 2).' сек.)';
            $json['.search_result_block'] = null;
            break;
          }
          
          $json['.result_count'] = 'Найдено '.$total.' ('.number_format(abs($mcrt_f - $mcrt_s), 2).' сек.)';
          
          $json['.search_result_block'] = null;
          
          if ($pages > 1) {
            $json['.search_result_block'] .= '<div class="result_listing">';
            if ($p > 1) {
              $_POST['p'] = $p - 1;
              $json['.search_result_block'] .= '<span class="list_dir"><a href="search_ek.php?'.http_build_query($_POST).'">&larr; Предыдущая</a></span>';
            } else {
              $json['.search_result_block'] .= '<span class="list_dir">&larr; Предыдущая</span>';
            }
            if ($p < $pages) {
              $_POST['p'] = $p + 1;
              $json['.search_result_block'] .= '<span class="list_dir"><a href="search_ek.php?'.http_build_query($_POST).'">Следующая &rarr;</a></span>';
            } else {
              $json['.search_result_block'] .= '<span class="list_dir">Следующая &rarr;</span>';
            }
            $json['.search_result_block'] .= '<span class="list_dir">';
            $n = 1;
            foreach($listing as $page) {
              if ($page == $n) {
                $n++; 
              } else {
                $json['.search_result_block'] .= '<span class="page">...</span>';
                $n = $page + 1;
              }
              if ($p == $page) {
                $json['.search_result_block'] .= '<span class="page current"><b>'.$page.'</b></span>';
              } else {
                $_POST['p'] = $page;
                $json['.search_result_block'] .= '<span class="page"><a href="search_ek.php?'.http_build_query($_POST).'">'.$page.'</a></span>';
              }
            }
            $json['.search_result_block'] .= '</span>';
            $json['.search_result_block'] .= '</div>';
          }
          
          $json['.search_result_block'] .= '
            <div class="result_headers">
              <div class="result_cell shorttext">Рег.данные</div>
              <div class="result_cell gianttext">Фабула</div>
              <div class="result_cell longtext">Решения</div>
              <div class="result_cell shorttext">Источник</div>
            </div>
          ';
          while ($row = $result->fetch_object()) {
            $json['.search_result_block'] .= '
              <div class="result_row">
                <div class="result_cell shorttext">
                  <a href="ek.php?id='.$row->id.'">'.$row->ovd.'<br />'.$row->reg.'</a>
                </div>
                <div class="result_cell gianttext">
                  '.((!empty($row->marking)) ? '<i><b>Окраска</b> &ndash; '.$row->marking.'</i><br />' : null).'
                  '.((!empty($row->declarer)) ? '<i><b>Заявитель</b> &ndash; '.$row->declarer.'</i><br />' : null).'
                  '.$row->story.'
                </div>
                <div class="result_cell longtext left-align">'.$row->decisions.'</div>
                <div class="result_cell shorttext">'.$row->source.'</div>
              </div>';
          }
          
          if ($pages > 1) {
            $json['.search_result_block'] .= '<div class="result_listing">';
            if ($p > 1) {
              $_POST['p'] = $p - 1;
              $json['.search_result_block'] .= '<span class="list_dir"><a href="search_ek.php?'.http_build_query($_POST).'">&larr; Предыдущая</a></span>';
            } else {
              $json['.search_result_block'] .= '<span class="list_dir">&larr; Предыдущая</span>';
            }
            if ($p < $pages) {
              $_POST['p'] = $p + 1;
              $json['.search_result_block'] .= '<span class="list_dir"><a href="search_ek.php?'.http_build_query($_POST).'">Следующая &rarr;</a></span>';
            } else {
              $json['.search_result_block'] .= '<span class="list_dir">Следующая &rarr;</span>';
            }
            $json['.search_result_block'] .= '<span class="list_dir">';
            $n = 1;
            foreach($listing as $page) {
              if ($page == $n) {
                $n++; 
              } else {
                $json['.search_result_block'] .= '<span class="page">...</span>';
                $n = $page + 1;
              }
              if ($p == $page) {
                $json['.search_result_block'] .= '<span class="page current"><b>'.$page.'</b></span>';
              } else {
                $_POST['p'] = $page;
                $json['.search_result_block'] .= '<span class="page"><a href="search_ek.php?'.http_build_query($_POST).'">'.$page.'</a></span>';
              }
            }
            $json['.search_result_block'] .= '</span>';
            $json['.search_result_block'] .= '</div>';
          }
        } else {
          if (!empty($_SESSION['user']['admin'])) {
            $jsonERR[] = $db->error;
          } else {
            $jsonERR[] = 'Внутренняя ошибка сервера.';
          }
          $json['.result_count'] = 'Ничего не найдено ('.number_format(abs($mcrt_f - $mcrt_s), 2).' сек.)';
          $json['.search_result_block'] = null;
        }
        break;
       
      case 'ek-list':
        $loaded = (!empty($_POST['t']) and is_numeric($_POST['t'])) ? (integer)$_POST['t'] : 0;
        $limit = (!empty($_POST['r']) and is_numeric($_POST['r'])) ? (integer)$_POST['r'] : 15;
        $ovd = false;
        if (!empty($_POST['ovd']) and is_numeric($_POST['ovd'])) {
          $ovd = true;
          $where[] = 'ek.`ovd` = '.$_POST['ovd'];
        }
        if (!empty($_POST['year']) and is_numeric($_POST['year']) and $_POST['year'] >= 2012) {
          if (!empty($_POST['month']) and is_numeric($_POST['month']) and $_POST['month'] >= 1 and $_POST['month'] <= 12) {
            $where[] = 'ek.`reg_date` BETWEEN STR_TO_DATE("01.'.$_POST['month'].'.'.$_POST['year'].'", "%d.%m.%Y") AND STR_TO_DATE("31.'.$_POST['month'].'.'.$_POST['year'].'", "%d.%m.%Y")';
          } else {
            $where[] = 'ek.`reg_date` BETWEEN STR_TO_DATE("01.01.'.$_POST['year'].'", "%d.%m.%Y") AND STR_TO_DATE("31.12.'.$_POST['year'].'", "%d.%m.%Y")';
          }
        }
        $par_str = null;
        foreach ($_POST as $k => $v) {
          $par_str[$k] = $k.'="'.$v.'"';
        }

        require(KERNEL.'connect.php');
        $query = '
          SELECT DISTINCT
            ek.`id`, ovd.`ovd`,
            CONCAT(ek.`reg_number`, ", ", 
                   DATE_FORMAT(ek.`reg_date`, "%d.%m.%Y"), " ", 
                   DATE_FORMAT(ek.`reg_time`, "%H.%i")
                   ) as `reg`,
            ek.`story`, ek.`marking`,
            CONCAT(IFNULL(ek.`declarer_person`, ""), IF(ek.`declarer_org` IS NOT NULL, CONCAT(", ", ek.`declarer_org`), "")) as `declarer`,
            IF(ek.`event_code` IS NOT NULL, "ПК \"Легенда\"", "СОДЧ") as `source`,
            GROUP_CONCAT(
              DISTINCT
                IF(kh.`dec_date` IS NOT NULL AND kh.`dec_date` <> "0000-00-00", DATE_FORMAT(kh.`dec_date`, "%d.%m.%Y"), ""), 
                IF(kh.`decision` IS NOT NULL, CONCAT(" ", kh.`decision`), ""),
                IF(kh.`term` IS NOT NULL AND kh.`term` <> "0000-00-00" AND kh.`dec_code` = 23, CONCAT(" до ", DATE_FORMAT(kh.`term`, "%d.%m.%Y")), ""),
                IF(kh.`dec_number` IS NOT NULL AND kh.`dec_number` <> "", CONCAT(", №", kh.`dec_number`), ""),
                IF(kh.`qualification` IS NOT NULL AND kh.`qualification` <> "", CONCAT(", ", kh.`qualification`), "")
              ORDER BY kh.`dec_date` DESC
              SEPARATOR "<br />"
            ) as `decisions`
          FROM
            `ek_kusp` as ek
          JOIN
            `spr_ovd` as ovd ON
              ovd.`id_ovd` = ek.`ovd`
          LEFT JOIN
            `ek_dec_history` as kh ON
              kh.`kusp` = ek.`id` AND
              kh.`error_rec` = 0
          WHERE
            '.implode(' AND ', $where).'
          GROUP BY
            ek.`id`
          ORDER BY
            ek.`reg_date` DESC, ek.`reg_number` DESC, ek.`ovd`
          LIMIT
            '.$loaded.', '.$limit.'
        ';

        $result = $db->query($query);
        
        //$updates['remove'][] = '#add-ready';
        $updates['replaceWith']['.responce_place'] = null;
        if ($result->num_rows > 0) {
          $par_str['t'] = 't="'.($limit + $loaded).'"';           // всего загружено записей
          $par_str['r'] = 'r="'.$limit.'"';                     // по какую запись нужно выгрузить
          while ($row = $result->fetch_object()) {
            $updates['replaceWith']['.responce_place'] .= '
                <div class="result_row">
                  <div class="result_cell shorttext">
                    <a href="ek.php?id='.$row->id.'">'.((!$ovd) ? $row->ovd.'<br />' : null).$row->reg.'</a>
                  </div>
                    <div class="result_cell gianttext">
                    '.((!empty($row->marking)) ? '<i><b>Окраска</b> &ndash; '.$row->marking.'</i><br />' : null).'
                    '.((!empty($row->declarer)) ? '<i><b>Заявитель</b> &ndash; '.$row->declarer.'</i><br />' : null).'
                    '.$row->story.'
                  </div>
                  <div class="result_cell longtext left-align">'.$row->decisions.'</div>
                  <div class="result_cell shorttext">'.$row->source.'</div>
                </div>
            ';
          }
          $updates['replaceWith']['.responce_place'] .= '<div class="responce_place"><div '.implode(' ', $par_str).'></div></div>';
        }
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
      
      case 'file_send_story':
        $json['#file_preview'] = null;
        if (empty($_POST['file']))
          throw new Exception('Wrong parameters.');
         
        require_once(KERNEL.'connection.php');
        $query = 'SELECT `dch_mail`, `ovd` FROM `spr_ovd` WHERE `dch_mail` IS NOT NULL';
        if (!$result = mysql_query($query))
          throw new Exception(mysql_error().' Query: <pre>'.$query.'</pre>');
        $ovd = null;
        while ($row = mysql_fetch_assoc($result)) {
          $ovd[$row['dch_mail']] = $row['ovd'];
        }
        
        $file = new ElFile($_POST['file']);
        
        $json['#file_preview'] = '
          <div class="opacity_back"></div>
          <div class="info_box">
            <div class="block_header">История рассылки:</div>
            <div class="message" style="width: 400px;"><ul>';
          foreach ($file->get_mail_history() as $n => $m) {
            if (array_key_exists($m, $ovd)) {
              $json['#file_preview'] .= '<li>'.$ovd[$m].' (<i>'.$m.'</i>)</li>';
            } else {
              $json['#file_preview'] .= '<li>'.$m.'</li>';
            }
          }
        $json['#file_preview'] .= '</ul></div>
            <div class="popup_window_button_block">
              <div class="add_button_box">
                <div class="button_block"><span class="button_name">Закрыть</span></div>
              </div>
            </div>
          </div>';
        break;
        
      case 'mailing':
        if (!empty($_POST['id']))
          $id = to_integer($_POST['id']);
        if (empty($id))
          throw new Exception('Wrong parameters.');
        
        if (empty($_POST['files']))
          throw new Exception('Не выбран ни один файл.');
        if (empty($_POST['mails']))
          throw new Exception('Не выбран ни один адресат.');
          
        $files = $_POST['files'];
        $mails = $_POST['mails'];
        
        $app = null;
        $ornt = new Orientation($id);
        
        foreach ($files as $n => $file) {
          $files[$n] = new ElFile($file);
          $app[] = $files[$n]->get_path();
          $types[] = $files[$n]->get_type_string();
        }
        
        /* -------- дополнительные адреса рассылки -------- */
        $mails[] = 'centrori@kir.mvd.ru';
        $mails[] = 'ic_uvd@kir.mvd.ru';
        /* -------- дополнительные адреса рассылки -------- */
        
        $to = implode(',', $mails);
        
        
        if (send_mail($to, $ornt->get_number(), null, $app)) { // заменить на отправку:   send_mail($to, $ornt->get_number(), null, $app)
          foreach ($files as $n => $file) {
            $file->set_history($to);
          }
        }
        $json['.registration_block .response_place'] = '
          <div class="opacity_back" href="ornt_view.php?id='.$ornt->get_id().'"></div>
          <div class="info_box">
            <div class="block_header">Рассылка:</div>
            <div class="message">Файлы успешно направлены!</div>
            <div class="popup_window_button_block"><div class="add_button_box" href="ornt_view.php?id='.$ornt->get_id().'">
              <div class="button_block"><span class="button_name">Ok</span></div>
            </div></div>
          </div>
        ';
        break;
        
      case 'online-further-query':
        if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['ornt_reconcil']))
          throw new Exception('Недостаточно прав.');
        $where[] = 'o.`reconciled` = 0';
      
      case 'online-further-query_public':
        if (!empty($_POST['lqt']))
          $lq_time = to_integer($_POST['lqt']);
        if (empty($lq_time))
          throw new Exception('Wrong parameters.');
        $lq_time = substr($lq_time, 0, -3);  // обрезаем микросекунды
        
        if (!empty($_POST['ovd']))
          $where[] = 'o.`ovd` = '.to_integer($_POST['ovd']);
        
        $where[] = '((o.`create_date` = "'.date('Y-m-d', $lq_time).'" AND o.`create_time` > "'.date('G:i:s', $lq_time).'") OR
                     (o.`create_date` > "'.date('Y-m-d', $lq_time).'"))';
        
        require_once(KERNEL.'connection.php');
        $query = '
          SELECT
            o.`id`, o.`number`, DATE_FORMAT(o.`date`, "%d.%m.%Y") as `date`,
            IF(o.`wonumber` = 0, "false", "true") as `wonumber`,
            uk.`st`, ot.`type`,
            GROUP_CONCAT(
              DISTINCT
              kovd.`ovd`, ", №<b>", k.`kusp`, "</b> от <b>", DATE_FORMAT(k.`date`, "%d.%m.%Y"), "</b>"
              SEPARATOR "<br />"
            ) as `kusp`,
            o.`crime_case` as `crime_case_id`,
            IF(o.`crime_case` IS NULL, NULL,
              CONCAT("У/д №<b>", cc.`crime_case_number`, "</b> от ", DATE_FORMAT(cc.`crim_case_date`, "%d.%m.%Y"))
            ) as `crime_case`,
            IF(f.`FileContent` IS NULL, "false", "true") as `indexed`,
            f.`link`,
            DATE_FORMAT(o.`create_date`, "%d.%m.%Y") as `create_date`,
            TIME_FORMAT(o.`create_time`, "%H:%i") as `create_time`
          FROM
            `l_orientations` as o
          LEFT JOIN
            `spr_uk` as uk ON
              uk.`id_uk` = o.`uk`
          LEFT JOIN
            `spr_orientation_types` as ot ON
              ot.`id` = o.`marking`
          LEFT JOIN
            `l_crime_cases` as cc ON
              cc.`id` = o.`crime_case`
          LEFT JOIN
            `l_orient_kusp` as ok ON
              ok.`orientation` = o.id AND
              ok.`deleted` = 0
            LEFT JOIN
              `l_kusp` as k ON
                k.`id` = ok.`kusp`
            LEFT JOIN
              `spr_ovd` as kovd ON
                kovd.`id_ovd` = k.`ovd`
              LEFT JOIN
                `ek_kusp` as ek ON
                  ek.`reg_number` = k.`kusp` AND
                  ek.`reg_date` = k.`date` AND
                  ek.`ovd` = k.`ovd`
                LEFT JOIN
                  `ek_dec_history` as dh ON
                    dh.`id` = ek.`last_decision`
          LEFT JOIN
            `l_files` as f ON
              f.`orientation` = o.`id` AND
              f.`type` = 1
          WHERE
            '.implode(' AND ', $where).'
          GROUP BY
            o.`id`
          ORDER BY
            o.`number` DESC
        ';
        //echo $query;
        $result = mysql_query($query);
        $json['updates'] = '';
        while ($row = mysql_fetch_assoc($result)) {
          foreach ($row as $k => $v)
            $row[$k] = get_var_in_data_type($v);
          $json['updates'] .= '
            <div class="result_row new" id="'.$row['id'].'">
              <div class="result_cell number"><a href="ornt_view.php?id='.$row['id'].'" target="_blank">'.(($row['wonumber']) ? 'б/н' : $row['number']).'</a></div>
              <div class="result_cell date">'.$row['date'].'</div>
              <div class="result_cell text">'.
                (($row['type']) ? $row['type'].'<br />' : '').
                (($row['st']) ? 'Розыск преступника (ст.'.$row['st'].' УК РФ)<br />' : '').
                $row['crime_case'].'
              </div>
              <div class="result_cell text">
                '.$row['kusp'].'
              </div>
              <div class="result_cell text">
                <span class="actions_list">
                  <p>Введено: '.$row['create_time'].' '.$row['create_date'].'</p>
                  '.(($row['indexed']) ? '<a href="#" method="file_preview" file="'.$row['link'].'" title="Предпросмотр">Предпросмотр</a>' : '<i>В обработке...</i>').'
                  '.(($_POST['method'] == 'online-further-query') ? '<a href="#" method="ornt_reconcile" id="'.$row['id'].'" title="Отметить как проверенную">Проверено</a>' : null).'
                </span>
              </div>
            </div>';
        }
        break;
        
      case 'ornt_reconcile':
        if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['ornt_reconcil']))
          throw new Exception('Недостаточно прав.');
          
        if (!empty($_POST['id']))
          $id = to_integer($_POST['id']);
        if (empty($id))
          throw new Exception('Wrong parameters.');
        
        require_once(KERNEL.'connection.php');
        $query = '
          UPDATE
            `l_orientations`
          SET
            `reconciled` = 1
          WHERE
            `id` = '.$id;
        if (!mysql_query($query))
          throw new Exception(mysql_error().' Query: <pre>'.$query.'</pre>');
        
        $updates['fadeOut'][] = '.result_row#'.$_POST['id'];
        break;
    }
  } catch(Exception $exc) {
    $jsonData = true;
    if ($exc->getMessage() != '')
      $jsonERR[] = $exc->getMessage();
  }
}


if (isset($_POST['del_relation'])) {
  try {
    if (empty($_POST['group']))
      throw new Exception($error->getMessage(1));

    switch ($_POST['group']) {
      case 'files_orientation':
        $group = array(1, '.orientation_block');
        $section = 'orientation';
        
      case 'files_video_orientation':
        if (empty($group)) $group = array(2, '.video_block');
        if (empty($section)) $section = 'orientation';
      
      case 'files_recall_orientation':
        if (empty($group)) $group = array(4, '.recall_block');
        if (empty($section)) $section = 'orientation';
      
      case 'files_reference':
        if (empty($group)) $group = array(5, '.reference_block');
        if (empty($section)) $section = 'reference';
        
        if (empty($_SESSION[$section]['files'][$group[0]][$_POST['del_relation']]))
          throw new Exception('Неизвестный файл...');

        if (unlink($_SESSION['dir_session'].mb_convert_encoding($_SESSION[$section]['files'][$group[0]][$_POST['del_relation']]['basename'], 'Windows-1251', 'UTF-8'))) {
          unset($_SESSION[$section]['files'][$group[0]][$_POST['del_relation']]);
          if (empty($_SESSION[$section]['files'][$group[0]]))
            unset($_SESSION[$section]['files'][$group[0]]);
          $json[$group[1].' .response_place'] = (!empty($_SESSION[$section]['files'][$group[0]])) ? get_added_files_list_with_info($_SESSION[$section]['files'][$group[0]], '_'.$_POST['group']) : null;
        } else {
          throw new Exception($error->getMessage(12));
        }
        break;
        
      case 'kusp':
        if (!empty($_POST['section']) and in_array($_POST['section'], array('orientation', 'reference'))) {
          $json['.messages_block .added_row'] = '';
          if (isset($_SESSION[$_POST['section']]['object'])) {
            $ornt = unserialize($_SESSION[$_POST['section']]['object']);
            $ornt->del_kusp($_POST['del_relation']);
            $json['.messages_block .added_row'] = related_kusp($ornt->get_kusp_array(), $_POST['section']);
            $_SESSION[$_POST['section']]['object'] = serialize($ornt);
          }
        }
        break;
    }
  } catch(Exception $exc) {
    $jsonData = true;
    if ($exc->getMessage() != '')
      $jsonERR[] = $exc->getMessage();
  }
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
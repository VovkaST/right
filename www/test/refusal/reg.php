<?php
$db = mysql_connect('localhost', '...', '...', "set names 'utf8'");
if(!$db) {
  echo '<center><p><b>���������� ������������ � ������� ���� ������ !</b></p></center>';
  exit();
}
//��������� ����������� ������ ��
if(!mysql_select_db('obv_otk', $db)) {
  echo '<center><p><b>���� ������ obv_otk ����������!</b></p></center>';
  exit();
}
mysql_query("set names 'cp1251'");
$sql_ovd_query = mysql_query('
  SELECT
    name_dir_otk
  FROM
    spr_ovd
  WHERE
    id_ovd = "'.$_SESSION["refusal"]["ovd"].'"
');
$ovd = mysql_fetch_row($sql_ovd_query)[0]; // ��� �������� ���
mysql_close($db);
$year_otkaz = date("Y", strtotime($_SESSION['refusal']['otk_date'])); // ��� ���������
$dir_year = $dir_refuse.$year_otkaz."���"; // ������ ���� �������� ����
$dir_ovd = $dir_year."/".$ovd; // ������ ���� �������� ���
$dir_month = $dir_ovd."/".$direction[date("n", strtotime($_SESSION['refusal']['otk_date']))]; // ������ ���� �������� ������
$file_old = $_SESSION['dir_session'].$_SESSION['refusal']['uploaded_file']; // ������ ���� ������� �����
if (isset($_SESSION['refusal']['bp'])) {$bp = 1;} else {$bp = 0;} // �� ���
$anonymous = $declEmp = 0;
if (isset($_SESSION['refusal']["anonymous"])) $anonymous = 1;
if (isset($_SESSION['refusal']["decl_emp"])) $declEmp = 1;
$error = array();
$otkaz = insert_otkaz($bp, $year_otkaz, $anonymous, $declEmp); // ��������� � �� ��������, �������� ��� id � ��� ��������� �����
if (!$otkaz) {
  $error[] = 'Refusal add error';
  updateSessionString($otkazLog, '', $error);
  die('Refusal add error');
};
@updateSessionString($otkazLog, $otkaz[0]);
//-------- ��������� � ������� ������ ��������� --------//
  //���� ��� �������� ����
  if (!is_dir($dir_year)) {
    mkdir($dir_year);
    mkdir($dir_ovd);
    mkdir($dir_month);
  }
  //���� ���� ������� ����, �� ��� �������� ���
  if (is_dir($dir_year)) {
    if (!is_dir($dir_ovd)) {
      mkdir($dir_ovd);
      mkdir($dir_month);
    }
  }
  //���� ���� ������� ����, ���, �� ��� �������� ������
  if (is_dir($dir_year)) {
    if (is_dir($dir_ovd)) {
      if (!is_dir($dir_month)) {
        mkdir($dir_month);
      }
    }
  }
//-------- ��������� � ������� ������ ��������� --------//

if (is_file($file_old)) { //��������� ������� �����
  if (copy($file_old, $dir_month."/".iconv("UTF-8", "windows-1251", $otkaz[2]))) { //�������� � ������� �������, ��������������� ����
    if (!unlink($file_old)) $error[] = 'Delete file error';//������� ������ ����
  } else {
    $error[] = 'Copying file error';
  }
} else {
  $error[] = 'File is not available';
}
//-------- �������� ������ ��� ���� --------//
  $kusp_array = $criminal_array = array();
  $array = $_SESSION['refusal']['kusp'];
  foreach ($array as $key => $value) {
    $kusp_array[] = ' (
      "'.$array[$key]['kusp_num'].'",
      "'.date('Y-m-d', strtotime($array[$key]['kusp_date'])).'",
      "'.$array[$key]['kusp_ovd'].'",
      "'.$otkaz[0].'",
      current_date, current_time, "'.$_SESSION['activity_id'].'"
    )';
  }
  $query_kusp = ('
    INSERT INTO
      kusp (kusp, data, ovd, otkaz_id, create_date, create_time, active_id) VALUES
      '.implode(', ', $kusp_array).'
    ');
//-------- �������� ������ ��� ���� --------//

//-------- �������� ������ ��� ������ --------//
  if (isset($_SESSION['refusal']['uk'])) {
      $array = $_SESSION['refusal']['uk'];
      foreach ($array as $key => $value) {
        $criminal_array[] = ' (
          "'.$otkaz[0].'",
          "'.$array[$key]['criminal_st'].'",
          current_date, current_time, "'.$_SESSION['activity_id'].'"
        )';
      }
      $query_criminal = ('
        INSERT INTO
          relatives_uk_otk (id_otkaz, id_st, create_date, create_time, active_id) VALUES
          '.implode(', ', $criminal_array).'
        ');
  }
//-------- �������� ������ ��� ������ --------//
require($kernel.'connection.php');
mysql_query($query_kusp) or $error[] = 'Message add error: '.mysql_error(); // ��������� ���� � ��
if (!empty($query_criminal)) mysql_query($query_criminal) or $error[] = 'Criminal add error: '.mysql_error(); // ��������� ������ � ��

//-------- ��������� ��� � �� --------//
  unset($array);
  $array = array();
  if (isset($_SESSION['refusal']['offender']) && isset($_SESSION['refusal']['av'])) {
    $array = array_merge($_SESSION['refusal']['av'], $_SESSION['refusal']['offender']);
  } 
  if (isset($_SESSION['refusal']['offender']) && !isset($_SESSION['refusal']['av'])) {
    $array = $_SESSION['refusal']['offender'];
  }
  if (!isset($_SESSION['refusal']['offender']) && isset($_SESSION['refusal']['av'])) {
    $array = $_SESSION['refusal']['av'];
  }
  if (count($array)) {
    foreach ($array as $key => $value) {
      if ($value['id'] == 'NULL') {
        $id = search_men($value['surname'], $value['name'], $value['fath_name'], $value['borth']); // �������� ��������� ���� �� �������
        if (!$id) {
          $query = ('
            INSERT INTO
              lico (surname, name, fath_name, borth, create_date, create_time, active_id)
            VALUES (
              "'.mysql_real_escape_string(mb_convert_case($value['surname'], MB_CASE_UPPER, "UTF-8")).'",
              "'.mysql_real_escape_string(mb_convert_case($value['name'], MB_CASE_UPPER, "UTF-8")).'",
              "'.mysql_real_escape_string(mb_convert_case($value['fath_name'], MB_CASE_UPPER, "UTF-8")).'",
              "'.date('Y-m-d', strtotime($value['borth'])).'",
              current_date, current_time, "'.$_SESSION['activity_id'].'"
            )
          ');
          if (mysql_query($query)) {
            $id = mysql_insert_id();
          } else {
            $error[] = 'Face insert error: '.mysql_error();
          }
        }
      } else {
        $id = $value['id'];
      }    
      $query = ('
        INSERT INTO
          relatives (type, id_otkaz, id_lico, create_date, create_time, active_id)
        VALUES (
          "'.$value['relative_id'].'",
          "'.$otkaz[0].'",
          "'.$id.'",
          current_date, current_time, "'.$_SESSION['activity_id'].'"
        )
      ');
      if (!mysql_query($query)) {
        $error[] = 'Face relative insert error: '.mysql_error();
      }
    }
  }
  if (isset($_SESSION['refusal']['org'])) {
    foreach ($_SESSION['refusal']['org'] as $key => $value) {
      $query = ('
        INSERT INTO
          organisations (org_name, create_date, create_time, active_id)
        VALUES ("'.mysql_real_escape_string(mb_convert_case($value['org_name'], MB_CASE_UPPER, "UTF-8")).'",
		  current_date, current_time, "'.$_SESSION['activity_id'].'"
		)
      ');
      if (mysql_query($query)) {
        $relQuery = '
          INSERT INTO
            relatives (type, id_otkaz, id_org, create_date, create_time, active_id)
          VALUES ("'.$value['relative_id'].'", "'.$otkaz[0].'", "'.mysql_insert_id().'", current_date, current_time, "'.$_SESSION['activity_id'].'")';
        if (!mysql_query($relQuery)) {
          $error[] = 'Organisation relative insert error ('.$value['relative_id'].'): '.mysql_error();
        }
      } else {
        $error[] = 'Organisation insert error ('.$key.'): '.mysql_error();
      }
    }
  }
updateSessionString($otkazLog, '', $error);
activity($_SESSION['activity_id']);
//-------- ��������� ��� � �� --------//
if (isset($_COOKIE['none_auth'])) { // ���� ������������ ��� �� �����������
  if (isset($_SESSION['dir_session'])) {
    dir_del($_SESSION["dir_session"]); // ������� ��������� �������
  }
  session_destroy(); // ���������� ������
  if (isset($_COOKIE['sess_id'])) {
    setcookie("sess_id", $sess_id, 1, "/"); // ���������� ����
  }
  if (isset($_COOKIE['PHPSESSID'])) {
    setcookie("PHPSESSID", $sess_id, 1, "/"); // ���������� ����
  }
  if (isset($_COOKIE['none_auth'])) {
    setcookie("none_auth", 1, 1, "/"); // ���������� ����
  }
}
unset($_SESSION['refusal']);
echo $otkaz[1];
?>
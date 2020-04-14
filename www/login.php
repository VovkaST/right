<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
if (isset($_POST['user']) && isset($_POST['password'])) {
  if (empty($_POST['user']) || empty($_POST['password'])) {
    $json['.row.others.resp'] = '<span class="error">Поля не могут быть пустыми.</span>';
  } else {
    require_once(KERNEL.'connection.php');
    $query = mysql_query('
      SELECT
        u.`id`, u.`user`, 
        CONCAT(
          IF(u.`emp_name` IS NOT NULL, CONCAT(SUBSTRING(u.`emp_name`, 1, 1), "."), NULL),
          IF(u.`emp_f_name` IS NOT NULL, CONCAT(SUBSTRING(u.`emp_f_name`, 1, 1), "."), NULL),
          u.`emp_surname`
        ) as `employeer`,
        u.`emp_surname`, u.`emp_name`, u.`emp_f_name`,
        ovd.`id` as `ovd_id`, ovd.`ovd`, u.`ibd_login`, u.`active`, u.`admin`
      FROM
        `users` as u
      LEFT JOIN
        `spr_ovd` as ovd ON
          ovd.`id` = u.`ovd`
      WHERE
        u.`user` = "'.$_POST['user'].'" AND
        u.`password` = "'.md5($_POST['password']).'"
      LIMIT 1
    ');
    $json = '';
    $result = mysql_fetch_assoc($query);
    if (!empty($result['id'])) {
      new_session($result);
      $user = ($result['employeer'] != '') ? $result['employeer'] : $result['user'];
      $ovd = ($result['ovd'] != '') ? $result['ovd'] : '&nbsp;';
      $json['.auth_form_block'] = '';
      $json['.auth_form_block'] .= '<div class="block_header">Добро пожаловать</div>';
      $json['.auth_form_block'] .= '<div class="auth_block">';
      $json['.auth_form_block'] .= '<div class="authorized">';
      $json['.auth_form_block'] .= 'Вы вошли как';
      $json['.auth_form_block'] .= '<div class="user"><b>'.$user.'</b><br/>'.$ovd.'</div>';
      $json['.auth_form_block'] .= '<div class="ip">ip: '.$_SERVER['REMOTE_ADDR'].'</div>';
      $json['.auth_form_block'] .= '</div>';
      $json['.auth_form_block'] .= '<div class="row others">';
      $json['.auth_form_block'] .= '<a href="/cabinet.php">Личный кабинет</a>';
      $json['.auth_form_block'] .= '</div>';
      $json['.auth_form_block'] .= '<div class="row others">';
      $json['.auth_form_block'] .= '<a href="/exit.php">Выход</a>';
      $json['.auth_form_block'] .= '</div></div>';
      //$_SESSION['user'] = $result['id'];
    } else {
      $json['.row.others.resp'] = '<span class="error">Пользователь с указанными логином и паролем не найден.</span>';
    }
  }
}
if ($json != '') $resp['html'] = $json;
if (isset($resp)) echo json_encode($resp);
?>
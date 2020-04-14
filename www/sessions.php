<?php
require('d:/www.sites/const.php');
$oori = false;
if (substr($_SERVER["REMOTE_ADDR"], 0, 10) == '...') $oori = true;

require(KERNEL.'functions.php');
if (isset($_SERVER['HTTP_REFERER']) && ($_SERVER['HTTP_REFERER'] == 'http://www.test.ru/test.php') && isset($_GET['auth'])) { // если пришли с ИБД-Р
  if (isset($_COOKIE['PHPSESSID'])) {
    session_start();
    new_session($_GET['auth']); // начинаем новую сессию и записываем все в БД
  } else {
    if (strtolower(substr($_GET['auth'], 0, 1)) == "a") { // если пользователь с МВД (начинается на 'a')
      session_start();
      new_session($_GET['auth']); // начинаем новую сессию и записываем все в БД
    } else {
      header("location: /auth.php"); // иначе отправляем на авторизацию
      die();
    }
  }
} else { // если пришли НЕ с ИБД-Р
  session_start();
  // если пользователь авторизован
  if (isset($_SESSION['user'])) {
    if (($_SESSION['last_activity_time'] + 60*60*24) < time()) { // если с последней активности прошло больше 24 часов 
      exit_session();
      if ($need_auth) {
        header("location: /auth.php");
        die();
      }
    } else {
      $_SESSION['last_activity_time'] = time();
      activity($_SESSION['activity_id']);
    }
  } else {
    if ($need_auth) {
      header("location: /auth.php");
      die();
    }
  }
}
//phpinfo(32);
?>
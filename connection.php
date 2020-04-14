<?php
// Пытаемся соединиться с сервером базы данных MySQL
$db = mysql_connect('localhost', '...', '...', "set names 'utf8'");
//Проверяем, удачно ли прошло подключение
if(!$db) {
  echo( '<center><p><b>Невозможно подключиться к серверу базы данных !</b></p></center>');
  exit();
}
//Проверяем доступность нужной БД
if(!mysql_select_db('obv_otk', $db)) {
  echo( '<center><p><b>База данных obv_otk недоступна!</b></p></center>');
  exit();
}
//Вывод сообщения об удачном выполнении подключения
//echo( '<center><p><b>Подключение к базе данных work выполнено.</b></p></center>');
mysql_query("set names 'utf8'");
?>
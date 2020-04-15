<?php
if (isset($_SESSION["admin"])) {
  $adm = '<a href="'.ADM_UTIL.'">Администрирование сайта</a>';
} else {
  $adm = "";
}
if (isset($_SESSION['user'])) {
	$auth = 'Вы вошли как <b>'.$_SESSION['user']['user'].'</b>.&nbsp;&nbsp;&nbsp;<a href="'.EXIT_SCRIPT.'">Выход</a> &nbsp;&nbsp;&nbsp; '.$adm;
} else {
	$auth = 'Вы не авторизованы. Доступ к разделам с персональными данными и документам будет недоступен.&nbsp;&nbsp;&nbsp;<a href="'.$enter.'">Авторизация (ИБД-Р)</a>';
}
?>
<div id="head">
 <div id="gerb">
  <a href="<?= INDEX ?>"><img src="<?= IMG ?>gerb_kir.gif" height="100"></a>
 </div>
 <div id="OORI">
  ОТДЕЛ ОПЕРАТИВНО-РАЗЫСКНОЙ ИНФОРМАЦИИ<br>УМВД России по Кировской области
 </div>
 <div id="kir_map">
  <img src="<?= IMG ?>kirov.gif" height="100">
 </div>
</div>
<div class="menu">
 <ul id="head_menu">
  <a href="<?= INDEX ?>"><li class="head_menu_item">Главная</li></a>
  <a href="<?= ACCOUNTING ?>"><li class="head_menu_item">Формирование учетов</li></a>
  <a href="<?= DOCUMENTS ?>"><li class="head_menu_item">Документы</li></a>
  <a href=""><li class="head_menu_item">Поиск</li></a>
  <a href="<?= CONTACTS ?>"><li class="head_menu_item" id="last-item">Контакты</li></a>
 </ul>
 <div class="auth">
  <span><?= $auth ?></span>
 </div>
</div>
<div id="main">
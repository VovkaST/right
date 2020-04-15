<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php',
  'Текстовый массив' => ''
);
$page_title = 'Текстовый массив';
$lastSwap = lastSwap('legenda');
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
?>
<style>

</style>
<center><span style="font-size: 1.2em;"><strong>Текстовый массив</strong></span></center>
<hr color="#C6C6C6" size="0px"/>
<ul class="styled-list">
  <li>
    <a href="e-kusp.php">Электронный КУСП </a>
    <span class="sub gray">Сведения на <?= date('G:i d.m.Y', strtotime($lastSwap['LAST_SWAP_DATE'].' '.$lastSwap['LAST_SWAP_TIME'])) ?></span>
  </li>
  <li><a href="references.php">Обзорные справки по преступлениям, БВП</a></li>
  <li><a href="orientations.php">Ориентировки</a></li>
  <li><a href="search.php">Поиск</a></li>
</ul>
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => ''
);
require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
require_once('require.php');

/*
<video width="400" height="220" controls="controls">
  <source src="/Orientations/2016/08/39/Video/Qiwi.mp4" type="video/mp4">
  <a href="/Orientations/2016/08/39/Video/Qiwi.mp4">Скачать</a>
</video>
<video width="400" height="220" controls="controls">
  <source src="/Orientations/2016/08/39/Video/Qiwi.mp4" type="video/mp4">
  <a href="/Orientations/2016/08/39/Video/Qiwi.mp4">Скачать</a>
</video>
<video width="400" height="220" controls="controls">
  <source src="/Orientations/2016/08/39/Video/Qiwi.mp4" type="video/mp4">
  <a href="/Orientations/2016/08/39/Video/Qiwi.mp4">Скачать</a>
</video>
*/

$n = '11701330042022776';
if ($n > 2147483647) echo '!!!';
?>
<pre>
 <?= var_dump($ed); ?>
</pre>



<?php require_once($_SERVER['DOCUMENT_ROOT'].'/footer.php'); ?>
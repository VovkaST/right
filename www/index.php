<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$breadcrumbs = array(
  'Главная' => '/index.php'
);
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<center><p style=" margin: 1em 0; "><b>Добро пожаловать на информационный портал ...</b></p></center>
<div id="description">
  ...
</div>
<div class="guide">
<h3>Руководство:</h3>
<ul>
 <li><b>Начальник:</b><br>
  ...<br>
  &nbsp;&nbsp;&nbsp;Телефон: ...
 </li>
 <li><b>Заместители:</b><br>
  ...<br>
  &nbsp;&nbsp;&nbsp;...<br>
  ...<br>
  &nbsp;&nbsp;&nbsp;...
 </li>
</ul>
</div>
<div class="browser">
<b>Для корректной работы с порталом рекомендуем использовать следующие браузеры:</b>
<ul>
 <li>Internet Explorer 8.0 (или более поздние версии)* (<a href="browser/IE8-WindowsXP-x86-RUS.zip">скачать</a>)</li>
 <li>Google Chrome (<a href="browser/Chrome.zip">скачать</a>)</li>
 <li>Yandex browser (<a href="browser/Yandex.zip">скачать</a>)</li>
 <li>Opera (<a href="browser/Opera_setup.zip">скачать</a>)</li>
</ul>
<span class="FTP">Так же браузеры доступны для скачивания на нашем FTP-сервере ... (учетная запись 'anonymous') из каталога 'Browser'</span>
<span class="prim">* поддержка более ранних версий IE не осуществляется</span>
</div>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>
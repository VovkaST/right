<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <title>Контакты</title>
 <link rel="shortcut icon" href="<?= IMG ?>favicon.ico">
 <link rel="icon" href="<?= IMG ?>favicon.ico" type="image/vnd.microsoft.icon">
 <link rel="stylesheet" href="<?= CSS ?>head.css">
 <link rel="stylesheet" href="<?= CSS ?>main.css">
 <link rel="stylesheet" href="<?= CSS ?>new.css">
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Контакты
</div>

<p class="contact_str"><b>Полное наименование:</b> ...</p>
<p class="contact_str"><b>Сокращенное наименование:</b> ...</p>
<p class="contact_str"><b>Адрес:</b> ...</p>
<p class="contact_str"><b>Электронный почтовый ящик:</b> <a href="mailto:...@kir.mvd.ru">...</a></p>
<table id="contacts_table" bordercolor="#E0E0E0" border="1px" rules="all">
  <tr class="head">
    <td width="275px"></td><td width="275px"></td><td width="50px"></td><td width="210px"></td>
  </tr>
  <?php
  //выбираем отделение
  require (KERNEL.'connection.php');
  $sql_squad_query = mysql_query("
    SELECT
      id, squad
    FROM
      squad_contacts
    ORDER BY
      id
  ") or die("Ошибка SQL: ".mysql_error());
  while ($sql_squad = mysql_fetch_array($sql_squad_query)):
  ?>
    <tr>
      <td colspan="4" align="center" class="squad"><b><?=$sql_squad["squad"]?></b></td>
    </tr>
    <?php
    //выбираем сотрудников по отделению
    $sql_officer_query = mysql_query('
      SELECT
        position, officer, tel_number, tel_number_inside, fax_number, mail, curator
      FROM
        officer_contacts
      WHERE
        squad = "'.$sql_squad["id"].'"
      ORDER BY
        `order`
    ') or die("Ошибка SQL: ".mysql_error());
    while ($sql_officer = mysql_fetch_array($sql_officer_query)):
      $fax = $curator = $mail = "";      if ($sql_officer["fax_number"]) $fax = "<br>".$sql_officer["fax_number"]." (факс)";
      if ($sql_officer["curator"]) $curator = "<br><i>Курируемая зона: ".$sql_officer["curator"]."</i>";
      if ($sql_officer["mail"]) $mail = '<br>Эл.почта: <a href="mailto:'.$sql_officer["mail"].'" class="hint_mail">'.$sql_officer["mail"].'<span>Написать письмо</span></a>'?>      <tr>
        <td><b><?=$sql_officer["position"]?></b><?=$curator?></td>
        <td><?=$sql_officer["officer"]?></td>
        <td align="center"><?=$sql_officer["tel_number_inside"]?></td>
        <td align="center"><?=$sql_officer["tel_number"].$fax.$mail?></td>
      </tr>    <?php endwhile;
  endwhile;?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
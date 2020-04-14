<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
?>
<!DOCTYPE html>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <title>Документы</title>
 <link rel="shortcut icon" href="<?= IMG ?>images/favicon.ico">
  <link rel="icon" href="<?=IMG?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?= CSS ?>main.css">
  <link rel="stylesheet" href="<?= CSS ?>new.css">
  <link rel="stylesheet" href="<?=CSS?>head.css">
  <link rel="stylesheet" href="<?= CSS ?>redmond/jquery-ui-1.10.4.custom.css">
  <script src="<?= JS ?>jquery-1.10.2.js"></script>
  <script src="<?= JS ?>jquery-ui-1.10.4.custom.js"></script>
  <script>
    $(function(){
      $(".document").hover(function(){
        var elem = $(this);
        var document = elem.children(".document");
        if (document.css("display") === "none") {
          document.slideDown(200);
        } else {
          document.slideUp(200);
        }
      });
    });
  </script>
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?= INDEX ?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Документы
</div>
<?php if (isset($_SESSION['user'])) {
  echo document(true);
} else {
  echo document();
}
?>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require (KERNEL."connection_uii.php");
$result = mysql_query('
  SELECT
    DATE_FORMAT(r.`data_vvoda`, "%d.%m.%y") as datpr,
    r.id as rap_id
  FROM
    journal as j
  LEFT JOIN 
    raport as r ON 
      j.id = r.journal_id
  WHERE
    j.id = '.$_GET["men_id"].'
  ORDER BY 
    r.datpr DESC
') or die("Query failed: ".mysql_error());
$n = 0;
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="stylesheet" href="css/men_date.css">
  <link rel="stylesheet" href="css/link.css">
</head>
<body>
<table rules="all" cellpadding="all">
  <tr class="head">
    <td>№</td>
    <td colspan="2">Справки от:</td>
  </tr>
  <?php 
  while ($row = mysql_fetch_array($result)): ?>
  <tr>
    <form action="download.php" method="GET">
      <input type="hidden" name="raport_id" value="<?= $row["rap_id"] ?>">
      <input type="hidden" name="men_id" value="<?= $_GET["men_id"] ?>">
      <td><?= ++$n ?>.</td>
      <td><a href="men_txt.php?rap_id=<?= $row['rap_id'] ?>" target="men_txt"><?= $row["datpr"] ?></a></td>
      <td align="center"><input type="image" src="<?= IMG ?>printer.png" height="20px" width="20px"></td>
    </form>
  </tr>
  <?php endwhile; ?>
</table>
</body>
</html>
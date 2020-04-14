<?php
$need_auth = 0;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
require ($kernel."connection_ukr.php");
$stmt = mysql_query('
  SELECT
    e.Район, count(distinct d.faceId) as cnt
  FROM (
    SELECT
      MAX(a.id) as id,
      a.faceId,
      MAX(a.ДатаПостановкиНаУчет) as ДатаПостановкиНаУчет
    FROM 
      notice a
    WHERE 
      a.ДатаУбытия is null
    GROUP BY 
      a.faceId
  ) as a 
  JOIN 
    notice as b ON 
      a.id = b.id 
  JOIN face as c ON 
    b.faceId = c.id AND 
    c.Гражданство = "UKR"
  JOIN address as e ON 
    b.addrPrebId = e.id
  LEFT JOIN 
    report as d ON 
      b.faceId = d.faceId
  GROUP BY 
    e.Район
  ORDER BY 
    orderId
');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Проверка граждан прибывших с Украины</title>
  <link rel="icon" href="<?=$img?>favicon.ico" type="vnd.microsoft.icon">
  <link rel="stylesheet" href="<?=$css?>main.css">
  <link rel="stylesheet" href="<?= $css ?>new.css">
  <link rel="stylesheet" href="<?=$css?>head.css">
</head>
<body>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/head.php');
?>
<div class="breadcrumbs">
  <a href="<?=$index?>">Главная</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$accounting?>">Формирование учетов</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;<a href="<?=$ukr?>">Проверка граждан, прибывших с Украины</a>&nbsp;&nbsp;&nbsp;>&nbsp;&nbsp;&nbsp;Проверено лиц, состоящих на учете УФМС
</div>
<table cellpadding="3" border="1" rules="all" align="center" class="result_table">
  <tr class="table_head">
    <th width="300px">Район</th>
    <th width="60px">Кол-во<br/>проверок</th>
  </tr>
<?php while($row = mysql_fetch_assoc($stmt)): ?>
	<tr>
    <td><?=$row['Район']?></td>
    <?php if ($row['cnt'] == 0): ?>
      <td align="center"><?=$row['cnt']?></td>
    <?php else : ?>
      <td align="center"><a href="<?=$addr?>ukr/addressFaceList.php?district=<?=$row['Район']?>"><?=$row['cnt']?></a></td>
    <?php endif; ?>
  </tr>
<?php endwhile; ?>
</table>
<?php
require ($_SERVER['DOCUMENT_ROOT'].'/footer.php');
?>

</body>
</html>
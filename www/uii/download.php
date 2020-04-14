<?php
$need_auth = 1;
require($_SERVER['DOCUMENT_ROOT'].'/sessions.php');
$raportId = isset($_GET['raport_id']) ? $_GET['raport_id'] : 0;
$men_id = isset($_GET['men_id']) ? $_GET['men_id'] : 0;
// получаем рапорт
require (KERNEL."connection_uii.php");
$result = mysql_query('
  SELECT
    r.*,
    GROUP_CONCAT(
      DISTINCT
        DATE_FORMAT(rd.`check_date`, "%d.%m.%y"), " около ", rd.`check_time`, ".00"
      ORDER BY
        rd.`check_date`
      SEPARATOR
        ", "
    ) as `check_date`
  FROM
    `raport` as r
  LEFT JOIN
    `raport_date` as rd ON
      rd.`raport_id` = r.`id`
  WHERE
    r.`id` = '.$raportId
) or die("Query failed : " . mysql_error());
$raport = mysql_fetch_assoc($result);
mysql_free_result($result);

// получаем информацию о человеке
$result = mysql_query('
  SELECT
    *
  FROM 
    journal		   
  WHERE 
    id = '.$men_id
) or die("Query failed : " . mysql_error());
$man = mysql_fetch_assoc($result);
mysql_free_result($result);

$fam = trim($man['fam']);
$im = trim($man['im']); 
$otch = trim($man['otch']); 
$datroj = trim($man['datroj']); 
//$datroj=DATE("d.m.Y", strtotime($man['$datroj'])); 
//$dat_post_uch = DATE("d.m.Y", strtotime($man['$dat_post_uch'])); 
$dat_post_uch = $man['dat_post_uch'] ? date("d.m.Y", strtotime($man['dat_post_uch'])) : 'нет данных' ;
$datpr = $raport['check_date'] ? $raport['check_date'] : 'нет данных' ;
$data_vvoda = $raport['data_vvoda'] ? DATE("d.m.Y", strtotime($raport['data_vvoda'])) : 'нет данных' ;
//die($dat_post_uch);
$obazannost = strlen($man['obazannost'])>2 ? $man['obazannost'] : 'нет' ;
$ogranichenia = strlen($man['ogranichenia'])>2 ? $man['ogranichenia'] : 'нет' ;
$obazannost =str_ireplace(', ,', ', ', $obazannost);
$ogranichenia =str_ireplace(', ,', ', ', $ogranichenia);
//die($obazannost);
// читаем шаблон
$tmpl = file_get_contents('raport.XML');
$content = str_replace(
array('[%uin]', '[%ovd]', '[%fam]', '[%im]', '[%otch]', '[%datroj]', '[%dat_post_uch]', '[%num_delo]', '[%gor_rai_reg]', '[%nas_p_reg]', '[%kat_uch]', '[%osnov_uch]', '[%st_uk]', '[%obazannost]', '[%ogranichenia]', '[%ADR_REG]', '[%ADR_FAKT]', '[%datpr]', '[%mobila]', '[%faktproj]', '[%skemproj]', '[%uslovij_proj]', '[%mrab]', '[%m_rab_xar]', '[%v_vid]', '[%sosedi]', '[%mproj_sosedi]', '[%harakteristika]', '[%svazi]', '[%avto]', '[%gos_num]', '[%zvan_ruk]', '[%rukovoditel]', '[%data_vvoda]', '[%v_ogran]', '[%sotr]', '[%zvan]', '[%dolj]', '[%Inoe]'),
array($man['uin'], 
      $man['ovd'], 
	  $fam, 
	  $im, 
	  $otch, 
	  $datroj, 
	  $dat_post_uch, 
	  $man['num_delo'],  
	  $man['gor_rai_reg'], 
	  $man['nas_p_reg'], 
	  $man['kat_uch'], 
	  $man['osnov_uch'], 
	  $man['st_uk'], 
	  $obazannost, 
	  $ogranichenia, 
	  $man['ADR_REG'], 
	  $man['ADR_FAKT'], 
	  $datpr, 
	  //$raport['vrpr'], 
	  $raport['mobila'], 
	  $raport['faktproj'], 
	  $raport['skemproj'], 
	  $raport['uslovij_proj'], 
	  $raport['mrab'], 
	  $raport['m_rab_xar'], 
	  $raport['v_vid'], 
	  $raport['sosedi'], 
	  $raport['mproj_sosedi'], 
	  $raport['harakteristika'], 
	  $raport['svazi'], 
	  $raport['avto'], 
	  $raport['gos_num'], 
	  $raport['zvan_ruk'], 
	  $raport['rukovoditel'], 
	  $data_vvoda, 
	  $raport['v_ogran'], 
	  $raport['sotr'], 
	  $raport['zvan'], 
	  $raport['dolj'], 
	  $raport['Inoe']),
//array('[%uin]'),
//array($man['uin']),
$tmpl
);
$fileName = "{$raport['id']}";
// записываем данные в зип-архив
$zip = new ZipArchive;
$zipName = "reports/$fileName.zip";
if ($zip->open($zipName, ZIPARCHIVE::CREATE) === true){
	$zip->addFromString("$fileName.doc", $content);
	$zip->close();
}
//ob_end_clean();
//die(var_dump(@basename($zipName)));
/*	
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"" . @basename($zipName) . "\"");
	header("Content-Transfer-Encoding: binary");	
*/
header("Cache-Control: no-store, must-revalidate, post-check=0, pre-check=0");
	header('Expires: ' . date('r'));
	header("Content-Description: File Transfer");
 header("Content-Type: application/octet-stream");
 
 //header("Content-Length: " . @filesize($zipName));
 header("Content-Transfer-Encoding: binary");
 header("Content-Disposition: attachment; filename=\"" . @basename($zipName) . "\"");
 $zipdata = file_get_contents("reports/" . @basename($zipName) . "") ;
 die($zipdata);

//ob_start();	
//readfile($zipName);
//$content = ob_get_clean();
//file_put_contents('debug.zip', $content);
exit();
//phpinfo(INFO_VARIABLES);
?>
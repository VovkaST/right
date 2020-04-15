<?php
$need_auth = 0;
require ($_SERVER['DOCUMENT_ROOT'].'/sessions.php');

if (empty($_SESSION['user']['admin']) and empty($_SESSION['user']['weapon'])) {
  define('ERROR', 'Недостаточно прав.');
  require_once($_SERVER['DOCUMENT_ROOT'].'/error/error.php');
}

$breadcrumbs = array(
  'Главная' => '/index.php',
  'Учет "Оружие"' => 'index.php',
  'Единицы оружия' => ''
);
$page_title = 'Учет "Оружие" &ndash; Список единиц оружия';

if ($_SESSION['user']['ovd_id'] != 59) {
  $_GET['ovd'] = $_SESSION['user']['ovd_id'];
};

// --accounts
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'accounts') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.'
			group by k.id';
			
  $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} else {
 // $clause[] = 'Область';
}

if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'accounts_kusp') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and k.kusp is not null and k.crime_case is null
			group by k.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 

if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'accounts_ud') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and k.crime_case is not null
			group by k.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 

//-weapons
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'weapons') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' 
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'weapons_kusp') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.'  and k.kusp is not null and k.crime_case is null
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'weapons_ud') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.'  and k.crime_case is not null
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 

//-firearms
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'firearms') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and e.weapon_type = 1
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'firearms_kusp') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.'  and k.kusp is not null and e.weapon_type = 1 and k.crime_case is null
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'firearms_ud') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and k.crime_case is not null and e.weapon_type = 1 
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 

//-ammunition
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'ammunition') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and e.weapon_type = 2
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'ammunition_kusp') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.'  and k.kusp is not null and e.weapon_type = 2 and k.crime_case is null
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'ammunition_ud') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and k.crime_case is not null and e.weapon_type = 2 
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
// explosives
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'explosives') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and e.weapon_type = 3
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'explosives_kusp') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.'  and k.kusp is not null and e.weapon_type = 3 and k.crime_case is null
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'explosives_ud') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and k.crime_case is not null and e.weapon_type = 3 
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
// steelarms
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'steelarms') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and e.weapon_type = 4
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'steelarms_kusp') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.'  and k.kusp is not null and e.weapon_type = 4 and k.crime_case is null
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
} 
if (isset($_GET['ovd']) and is_numeric($_GET['ovd']) and $_GET['clause'] == 'steelarms_ud') {
  $_t = floor(abs($_GET['ovd']));
  $where = 'where (k.kusp is not null or k.crime_case is not null) and
			       e.quantity_total > 0 and e.deleted = 0 and k.deleted = 0 and 
				   k.ovd = '.$_t.' and k.crime_case is not null and e.weapon_type = 4 
			group by e.id';
			 $clause[] = get_meaning_from_spr('spr_ovd', $_t, 'ovd', 'id_ovd');
}
$query = '
		 select 
				distinct
				e.id as e_id,
				k.id,
				date_format(k.reg_date, "%d.%m.%Y") as reg_date,
				k.reg_number,
				os.name as osnov,
				r.name as sell_p,
				kusp.kusp as kusp_n,
				date_format(kusp.`date`, "%d.%m.%Y") as dat_kusp,
				ud.crime_case_number as ud_n,
				date_format(ud.crim_case_date, "%d.%m.%Y") as dat_ud,
				k.incoming_number as dir_n,
                k.incoming_date as  dat_dir
				from l_weapons_account as k join l_weapons as e on e.weapons_account = k.id  
											join spr_ovd as o on o.id_ovd=k.ovd 
											join spr_base_receiving_weapons as os on os.id = k.base_receiving
													 join spr_purpose_placing as r on k.purpose_placing = r.id
													 left join l_kusp as kusp on kusp.id = k.kusp
											left join l_crime_cases as ud on ud.id = k.crime_case
                '.$where.'											
				order by k.reg_date, k.reg_number
';

require(KERNEL.'connect.php');

if (!$result = $db->query($query))
  die($db->error.' .Query string: '.$query);

require_once($_SERVER['DOCUMENT_ROOT'].'/header.php');
$n = 1;
?>
<div class="header_row"><?= implode(', ', $clause) ?></div>
<div class="result_table">
  <div class="result_headers">
    <div class="result_cell" style='width: 4%'>№<br />п/п</div>
    <div class="result_cell" style='width: 8%'>Дата приема</div>
	<div class="result_cell" style='width: 8%'>№ квитанции</div>
    <div class="result_cell">Основание</div>
	<div class="result_cell" style='width: 20%'>КУСП/УД</div>
  </div>
 
 <?php while ($row = $result->fetch_object()) : ?>
  <div class="result_row">
    <div class="result_cell" style='width: 4%'>
      <?= $n++ ?>
    </div>
	<div class="result_cell" style='width: 8%'>
	    <?= $row->reg_date ?>
	</div>
    <div class="result_cell" style='width: 8%'>
       <a href="receipt.php?id=<?= $row->id ?>">№<?= $row->reg_number ?></a>
    </div>
    <div class="result_cell" > 
	   <?= $row->osnov ?> - <?= $row->sell_p ?> 
	</div>
	<div class="result_cell" style='width: 20%'> 
	  <?= (empty($row->kusp_n)) ? '' : '<em style="color: red">КУСП-'.$row->kusp_n.' от '.$row->dat_kusp.'</em>'?>
	  <?php if(!empty($row->kusp_n) and !empty($row->ud_n)) : ?> </br> <?php endif; ?>
	  <?= (empty($row->ud_n)) ? '' : '<em style="color: green">уг.д.№ '.$row->ud_n.' от '.$row->dat_ud.'</em>'?>
	</div>
	
 </div>

 <?php endwhile; ?>
</div>
<?php
date_default_timezone_set('Europe/Minsk');
$kol = 15; // ���������� ������� �� �������� ��������� �������
define('REC_ON_PAGE', 15);
$addr = "http://.../";
define("ADDR", "http://.../");
$kernel = "d:/www.sites/";
define("KERNEL", "d:/www.sites/");
$dir_files = "d:/www.sites/files";
define('DIR_FILES', 'd:/www.sites/files');
$dir_refuse = DIR_FILES."/��������/";
define('DIR_REFUSE', DIR_FILES.'/��������/');
$dir_uii = DIR_FILES."/���/";
$dir_migration = DIR_FILES."/��������/";
$dir_ukraine = DIR_FILES."/��������/";
$dir_ethnic = DIR_FILES."/������/";
define('REQUESTS', DIR_FILES.'/Requests/');
$direction = array(1 => "01_������", "02_�������", "03_����", "04_������", "05_���", "06_����", "07_����", "08_������", "09_��������", "10_�������", "11_������", "12_�������");
$file_type_array = array("doc", "docx", "rtf", "DOC", "DOCX", "RTF");
$doc_type_array = array("doc", "docx", "rtf", "DOC", "DOCX", "RTF", "pdf", "PDF");
$dir_docs = "d:/www.sites/documents";
define('DIR_DOCS', 'd:/www.sites/documents/');
$dir_session = "d:/www.sites/sessions/";
define('DIR_SESSION', 'd:/www.sites/sessions/');
$img = ADDR."images/";
define('IMG', '/images/');
$js = ADDR."js/";
define('JS', '/js/');
$css = ADDR."css/";
define('CSS', '/css/');
$location = "location: http://.../refusal/upload.php?tempID=";
$loc_acc_ind = "location: http://.../accounting/index.php";
$index = ADDR."index.php";
define('INDEX', '/index.php');
$enter = "http://...:.../";
define('IBD', 'http://...:.../');//���-�
$exit = ADDR."exit.php";//���������� ������ �������
define('EXIT_SCRIPT', '/exit.php');
$accounting = ADDR."accounting.php";//������������ ������
define('ACCOUNTING', '/accounting.php');
$refusal_view_upload = ADDR."refusal/refusal_view_upload.php"; //��������/���� ������ ���������
define('REFUSAL_VIEW_UPLOAD', ADDR.'refusal/refusal_view_upload.php');
$search = ADDR."refusal/search.php?";//�����
$refusal_view_upload_loc = "location: http://.../refusal/refusal_view_upload.php";
$uii = ADDR."uii/index.php"; //���
define('UII', ADDR.'uii/index.php'); //���������
$migration = ADDR."migration/migration.php"; //��������
define('MIGRATION', ADDR.'migration/migration.php'); //���������
$ukr = ADDR."ukr/index.php"; //�������
define('UKR', ADDR.'ukr/index.php'); //���������
$refusal = ADDR."refusal/index.php";//��������
$indictment = ADDR."indictment/index.php";//�������������
$debt = ADDR."debt/obv.php";//�����
define('DEBT', ADDR.'debt/obv.php'); //���������
define('DOCUMENTS', '/documents/index.php'); //���������
define('CONTACTS', '/contacts.php'); //��������
define('ADM_UTIL', '/admin/index.php'); //�������
define('CRIMES', '/crimes/'); //������������
$cpe_mail = "cextr@kir.mvd.ru";//�������� ���� ���
?>
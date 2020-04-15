<?php
date_default_timezone_set('Europe/Minsk');
$kol = 15; // количество записей на страницу просмотра выборки
define('REC_ON_PAGE', 15);
$addr = "http://.../";
define("ADDR", "http://.../");
$kernel = "d:/www.sites/";
define("KERNEL", "d:/www.sites/");
$dir_files = "d:/www.sites/files";
define('DIR_FILES', 'd:/www.sites/files');
$dir_refuse = DIR_FILES."/Отказные/";
define('DIR_REFUSE', DIR_FILES.'/Отказные/');
$dir_uii = DIR_FILES."/УИИ/";
$dir_migration = DIR_FILES."/Миграция/";
$dir_ukraine = DIR_FILES."/Украинцы/";
$dir_ethnic = DIR_FILES."/Этника/";
define('REQUESTS', DIR_FILES.'/Requests/');
$direction = array(1 => "01_январь", "02_Февраль", "03_Март", "04_Апрель", "05_Май", "06_Июнь", "07_Июль", "08_Август", "09_Сентябрь", "10_Октябрь", "11_Ноябрь", "12_Декабрь");
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
define('IBD', 'http://...:.../');//ИБД-Р
$exit = ADDR."exit.php";//завершение сессии доступа
define('EXIT_SCRIPT', '/exit.php');
$accounting = ADDR."accounting.php";//формирование учетов
define('ACCOUNTING', '/accounting.php');
$refusal_view_upload = ADDR."refusal/refusal_view_upload.php"; //просмотр/ввод нового отказного
define('REFUSAL_VIEW_UPLOAD', ADDR.'refusal/refusal_view_upload.php');
$search = ADDR."refusal/search.php?";//поиск
$refusal_view_upload_loc = "location: http://.../refusal/refusal_view_upload.php";
$uii = ADDR."uii/index.php"; //УИИ
define('UII', ADDR.'uii/index.php'); //документы
$migration = ADDR."migration/migration.php"; //миграция
define('MIGRATION', ADDR.'migration/migration.php'); //документы
$ukr = ADDR."ukr/index.php"; //Украина
define('UKR', ADDR.'ukr/index.php'); //документы
$refusal = ADDR."refusal/index.php";//отказные
$indictment = ADDR."indictment/index.php";//обвинительные
$debt = ADDR."debt/obv.php";//долги
define('DEBT', ADDR.'debt/obv.php'); //документы
define('DOCUMENTS', '/documents/index.php'); //документы
define('CONTACTS', '/contacts.php'); //контакты
define('ADM_UTIL', '/admin/index.php'); //админка
define('CRIMES', '/crimes/'); //преступления
$cpe_mail = "cextr@kir.mvd.ru";//почтовый ящик ЦПЭ
?>
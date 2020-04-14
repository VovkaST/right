<?php
$link = @mysql_connect("localhost", "...", "...", "set names 'cp1251'") or die("Could not connect : " . mysql_error());
@mysql_select_db("Work") or die("Could not select database");
@mysql_query("set names 'cp1251'");
?>
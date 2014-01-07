<?php 
include_once('../../../../../wp-config.php');
$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_query('INSERT INTO '.$wpdb->prefix.DATABASE_PREFIX.'tempbookings (data) VALUES (\''.serialize($_POST).'\')');
echo mysql_insert_id();
?>

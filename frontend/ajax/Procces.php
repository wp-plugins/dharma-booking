<?php 
include_once('../../../../../wp-config.php');
global $wpdb;
$wpdb->query( $wpdb->prepare("INSERT INTO ".$wpdb->prefix.DATABASE_PREFIX."tempbookings ( data ) VALUES ( %s)", serialize($_POST)) );
echo $wpdb->insert_id;
?>

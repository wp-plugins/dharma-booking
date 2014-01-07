<?php
    include_once('../../../../../wp-config.php');
$data = array(
   'name' => mysql_real_escape_string($_POST['name']),
   'minimum' => intval($_POST['minimum']),
   'menuorder' => intval($_POST['menuorder']),
   'capacity' => intval($_POST['capacity']),
   'price' => mysql_real_escape_string($_POST['price']),
   'discount' => mysql_real_escape_string($_POST['discount']),
   'discription' => mysql_real_escape_string($_POST['dscription']),
);

global $wpdb;
$wpdb->update($wpdb->prefix.DATABASE_PREFIX.'roomtypes', $data, array('id' => $_POST['id']) );
echo $_POST['name'].': saved';
?>

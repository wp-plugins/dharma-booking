<?php 
include_once('../../../../../../wp-config.php');

$settings = get_option('Dharma_Vars');
echo $settings['paymentAccount']. $settings['gatewayid'];


require_once($_GET['gateway'].'.php');
$res = doIt($_GET['amount'],$_GET['invoice'],$settings['paymentAccount'], $settings['gatewayid']);
echo '<pre>';
var_dump($res);
/*
*/
?>

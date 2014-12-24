<?php 
require_once($_GET['gateway'].'.php');
$res = doIt($_GET['amount'],$_GET['invoice']);
var_dump($res);
?>

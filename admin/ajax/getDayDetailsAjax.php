<?php

include_once('../../../../../wp-config.php');
include_once('../functions.php');

global $wpdb;

$currentday = date('Y-m-d',$_POST['invoice']);

$sql = 'SELECT sum(beds) FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings WHERE checkin <= DATE(%s) AND checkout > DATE(%s)  AND idguest <> 0';
$totalBeds = $wpdb->get_var($wpdb->prepare($sql, $currentday, $currentday) );

$sql = 'SELECT *,G.name as fullname, R.name as roomname FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings B
LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice
LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON G.id = B.idguest
      LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes R ON B.idroomtype = R.id
WHERE checkin <= DATE(%s) AND checkout > DATE(%s)  AND idguest <> 0';
foreach($wpdb->get_results($wpdb->prepare($sql, $currentday, $currentday)) as $dayDetail){
	$guests[$dayDetail->fullname] += $dayDetail->beds;
	$rooms[$dayDetail->roomname]['beds'] += $dayDetail->beds;
	$rooms[$dayDetail->roomname]['capacity'] = $dayDetail->capacity;
//	var_dump($dayDetail);
}
$totalBeds = ($totalBeds?$totalBeds:0);
?>
<h2><?=date('D M, jS Y',$_POST['invoice'])?><br /><?=$totalBeds?> Rentals</h2>
<?php if(!$totalBeds) exit();?>
<h3>guests</h3>
<?php foreach($guests as $room => $beds){echo $room.' x '.$beds.'</br >';}?>

<h3>rooms</h3>
<?php foreach($rooms as $room => $v){
	echo $room.' x '.($v['capacity'] > $v['beds']?$v['beds']:'<strong class="warning">'.$v['beds'].'</strong>').'</br >';
}
?>
